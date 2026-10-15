<?php
declare(strict_types=1);

namespace CommerceKings\ConsentNotification\Plugin;

use CommerceKings\Consent\Api\WithdrawalHandlerInterface;
use CommerceKings\Consent\Model\WithdrawalPool;
use CommerceKings\ConsentNotification\Model\WithdrawalHandler;
use CommerceKings\Notification\Model\SubscriptionTypePool;

/**
 * Registers WithdrawalHandler for the purpose of every Notification subscription type, so a feature module declares
 * only its subscription type: withdrawing its consent removes its subscriptions.
 */
class WithdrawalPoolPlugin
{
    public const NAME = 'notification_subscriptions';

    public function __construct(
        private readonly SubscriptionTypePool $types,
        private readonly WithdrawalHandler $handler
    ) {
    }

    /** @param string[] $result */
    public function afterGetNames(WithdrawalPool $subject, array $result, string $purpose): array
    {
        if ($this->isSubscriptionPurpose($purpose) && !in_array(self::NAME, $result, true)) {
            $result[] = self::NAME;
        }
        return $result;
    }

    public function afterGet(
        WithdrawalPool $subject,
        ?WithdrawalHandlerInterface $result,
        string $purpose,
        string $name
    ): ?WithdrawalHandlerInterface {
        if ($result === null && $name === self::NAME && $this->isSubscriptionPurpose($purpose)) {
            return $this->handler;
        }
        return $result;
    }

    private function isSubscriptionPurpose(string $purpose): bool
    {
        foreach ($this->types->getAll() as $type) {
            if ($type->getPurpose() === $purpose) {
                return true;
            }
        }
        return false;
    }
}
