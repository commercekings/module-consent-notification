<?php
declare(strict_types=1);

namespace CommerceKings\ConsentNotification\Model;

use CommerceKings\Notification\Api\SubscriptionRemovalInterface;
use CommerceKings\Notification\Model\SubscriptionManagement;
use Magento\Framework\App\ResourceConnection;

/**
 * Removes subscriptions whose consent can never be valid again: withdrawn, expired, or already deleted by Consent's
 * record retention. Withdrawal removes them at once (WithdrawalHandler); expiry has no such moment, so without this
 * the subscription and a guest's contact would wait for the cart to be deleted. Only stored consent rows decide –
 * never the current configuration – so a misconfigured source cannot remove valid subscriptions. A newer consent
 * has a new id: a subscription renewed with it is not touched.
 */
class StaleSubscriptionCleanup
{
    private const BATCH = 1000;

    public function __construct(
        private readonly ResourceConnection $resource,
        private readonly SubscriptionRemovalInterface $removal
    ) {
    }

    /** @return int subscriptions removed */
    public function execute(): int
    {
        $db = $this->resource->getConnection();
        $stale = $db->select()->distinct()
            ->from(['s' => $this->resource->getTableName(SubscriptionManagement::TABLE)], 'permission_ref')
            ->joinLeft(['c' => $this->resource->getTableName('ck_consent')], 'c.consent_id = s.permission_ref', [])
            ->where("s.permission_ref <> ''")
            ->where('c.consent_id IS NULL OR c.withdrawn_at IS NOT NULL OR c.expires_at <= NOW()')
            ->limit(self::BATCH);
        $removed = 0;
        while ($references = $db->fetchCol($stale)) {
            $deleted = $this->removal->removeByPermission($references);
            if ($deleted === 0) {
                break;
            }
            $removed += $deleted;
        }
        return $removed;
    }
}
