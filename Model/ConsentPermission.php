<?php
declare(strict_types=1);

namespace CommerceKings\ConsentNotification\Model;

use CommerceKings\Consent\Api\BrowserIdentityInterface;
use CommerceKings\Consent\Api\ConsentGuardInterface;
use CommerceKings\Consent\Api\ConsentInterface;
use CommerceKings\Consent\Api\WithdrawalLinkInterface;
use CommerceKings\Consent\Model\ConsentStore;
use CommerceKings\Notification\Api\Data\Permission;
use CommerceKings\Notification\Api\Data\Subscription;
use CommerceKings\Notification\Api\Data\Target;
use CommerceKings\Notification\Api\SubscriptionPermissionInterface;
use CommerceKings\Notification\Api\SubscriptionTypeInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Setup with CommerceKings_Consent: a subscription needs a valid consent for the type's purpose on the target's
 * website; the consent id is the permission reference. The visitor is identified by the browser the consent was given in.
 */
class ConsentPermission implements SubscriptionPermissionInterface
{
    public function __construct(
        private readonly ConsentInterface $consent,
        private readonly ConsentGuardInterface $guard,
        private readonly StoreManagerInterface $stores,
        private readonly BrowserIdentityInterface $browser,
        private readonly ConsentStore $records,
        private readonly WithdrawalLinkInterface $withdrawalLinks
    ) {
    }

    public function check(SubscriptionTypeInterface $type, Target $target): Permission
    {
        $consent = $this->consent->getCurrent($type->getPurpose());
        if ($consent === null) {
            return Permission::absent();
        }
        if ($consent->purpose !== $type->getPurpose()
            || $consent->websiteId !== (int)$this->stores->getStore($target->storeId)->getWebsiteId()
        ) {
            return Permission::refused();
        }
        return Permission::granted($consent->consentId);
    }

    public function validCondition(string $referenceColumn): string
    {
        return $this->guard->validCondition($referenceColumn);
    }

    public function identifiesVisitor(): bool
    {
        return true;
    }

    public function isCurrentVisitor(string $reference): bool
    {
        $consentBrowser = (string)($this->records->load($reference)['browser_hash'] ?? '');
        $browser = $this->browser->hash();
        return $consentBrowser !== '' && $browser !== '' && hash_equals($consentBrowser, $browser);
    }

    /** Valid (not withdrawn, expired or of an older revision), for this purpose and the store's website. */
    public function isValidFor(string $reference, string $purpose, int $storeId): bool
    {
        if (!$this->consent->isValid($reference)) {
            return false;
        }
        $consent = $this->consent->get($reference);
        return $consent !== null && $consent->purpose === $purpose
            && $consent->websiteId === (int)$this->stores->getStore($storeId)->getWebsiteId();
    }

    /** Consent's signed withdrawal link: its confirmation page and RFC 8058 one-click POST. */
    public function getUnsubscribeUrl(Subscription $subscription): ?string
    {
        return $subscription->permissionRef === '' ? null : $this->withdrawalLinks->getUrl($subscription->permissionRef);
    }
}
