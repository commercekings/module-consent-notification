<?php
declare(strict_types=1);

namespace CommerceKings\ConsentNotification\Model;

use CommerceKings\Consent\Api\Data\WithdrawalContext;
use CommerceKings\Consent\Api\WithdrawalHandlerInterface;
use CommerceKings\Notification\Api\SubscriptionRemovalInterface;

/**
 * Removes the subscriptions of withdrawn consents, for every type. Registered for the purpose of every subscription
 * type (Plugin\WithdrawalPoolPlugin). Repeating it is harmless. Without it nothing is sent anyway - the consumer
 * gate and claims check the consent - only cleanup waits.
 */
class WithdrawalHandler implements WithdrawalHandlerInterface
{
    public function __construct(private readonly SubscriptionRemovalInterface $removal)
    {
    }

    public function execute(WithdrawalContext $context): void
    {
        $this->removal->removeByPermission($context->consentIds);
    }
}
