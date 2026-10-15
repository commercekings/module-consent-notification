<?php
declare(strict_types=1);

namespace CommerceKings\ConsentNotification\Test\Unit\Plugin;

use CommerceKings\Consent\Api\Data\WithdrawalContext;
use CommerceKings\Consent\Api\WithdrawalHandlerInterface;
use CommerceKings\Consent\Model\WithdrawalPool;
use CommerceKings\ConsentNotification\Model\WithdrawalHandler;
use CommerceKings\ConsentNotification\Plugin\WithdrawalPoolPlugin;
use CommerceKings\Notification\Api\SubscriptionRemovalInterface;
use CommerceKings\Notification\Api\SubscriptionTypeInterface;
use CommerceKings\Notification\Model\SubscriptionTypePool;
use PHPUnit\Framework\TestCase;

class WithdrawalPoolPluginTest extends TestCase
{
    private array $removed = [];

    private function plugin(): WithdrawalPoolPlugin
    {
        $type = $this->createStub(SubscriptionTypeInterface::class);
        $type->method('getPurpose')->willReturn('cart_reminder');
        $removal = $this->createStub(SubscriptionRemovalInterface::class);
        $removal->method('removeByPermission')->willReturnCallback(function ($references) {
            $this->removed[] = $references;
            return count($references);
        });
        return new WithdrawalPoolPlugin(new SubscriptionTypePool(['cart_reminder' => $type]), new WithdrawalHandler($removal));
    }

    public function testEverySubscriptionPurposeGetsTheHandler(): void
    {
        $pool = new WithdrawalPool();
        $plugin = $this->plugin();
        $this->assertSame(['other', WithdrawalPoolPlugin::NAME], $plugin->afterGetNames($pool, ['other'], 'cart_reminder'));
        $this->assertSame([WithdrawalPoolPlugin::NAME], $plugin->afterGetNames($pool, [WithdrawalPoolPlugin::NAME], 'cart_reminder'),
            'not twice');
        $handler = $plugin->afterGet($pool, null, 'cart_reminder', WithdrawalPoolPlugin::NAME);
        $this->assertInstanceOf(WithdrawalHandler::class, $handler);
        $handler->execute(new WithdrawalContext('cart_reminder', ['a', 'b'], 1, 'browser', 'email'));
        $this->assertSame([['a', 'b']], $this->removed, 'the withdrawn consents\' subscriptions are removed');
    }

    public function testOtherPurposesAndHandlersAreUntouched(): void
    {
        $pool = new WithdrawalPool();
        $plugin = $this->plugin();
        $this->assertSame([], $plugin->afterGetNames($pool, [], 'analytics'));
        $this->assertNull($plugin->afterGet($pool, null, 'analytics', WithdrawalPoolPlugin::NAME));
        $this->assertNull($plugin->afterGet($pool, null, 'cart_reminder', 'another_handler'));
        $own = $this->createStub(WithdrawalHandlerInterface::class);
        $this->assertSame($own, $plugin->afterGet($pool, $own, 'cart_reminder', WithdrawalPoolPlugin::NAME),
            'a handler registered in di.xml wins');
    }
}
