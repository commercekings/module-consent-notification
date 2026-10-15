<?php
declare(strict_types=1);

namespace CommerceKings\ConsentNotification\Model;

use CommerceKings\Consent\Api\ConsentOwnershipInterface;
use CommerceKings\Consent\Api\WithdrawalLinkInterface;
use CommerceKings\Notification\Api\Data\Subscription;
use CommerceKings\Notification\Api\SubscriberInterface;

/** The subscriber is the browser the consent was given in; unsubscribing withdraws the consent (Consent's page). */
class ConsentSubscriber implements SubscriberInterface
{
    public function __construct(
        private readonly ConsentOwnershipInterface $ownership,
        private readonly WithdrawalLinkInterface $withdrawalLinks
    ) {
    }

    public function identifiesVisitor(): bool
    {
        return true;
    }

    public function isCurrentVisitor(string $reference): bool
    {
        return $this->ownership->isCurrentVisitor($reference);
    }

    /** Consent's signed withdrawal link: its confirmation page and RFC 8058 one-click POST. */
    public function getUnsubscribeUrl(Subscription $subscription): ?string
    {
        return $subscription->permissionRef === '' ? null : $this->withdrawalLinks->getUrl($subscription->permissionRef);
    }
}
