<?php
declare(strict_types=1);

namespace CommerceKings\ConsentNotification\Cron;

use CommerceKings\ConsentNotification\Model\StaleSubscriptionCleanup;

/** Daily: subscriptions of withdrawn, expired or deleted consents (see StaleSubscriptionCleanup). */
class RemoveStaleSubscriptions
{
    public function __construct(private readonly StaleSubscriptionCleanup $cleanup)
    {
    }

    public function execute(): void
    {
        $this->cleanup->execute();
    }
}
