<?php
declare(strict_types=1);

namespace CommerceKings\ConsentNotification\Test\Unit\Model;

use CommerceKings\Consent\Api\BrowserIdentityInterface;
use CommerceKings\Consent\Api\ConsentGuardInterface;
use CommerceKings\Consent\Api\ConsentInterface;
use CommerceKings\Consent\Api\Data\Consent;
use CommerceKings\Consent\Model\ConsentStore;
use CommerceKings\ConsentNotification\Model\ConsentPermission;
use CommerceKings\Notification\Api\Data\Target;
use CommerceKings\Notification\Api\SubscriptionTypeInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\TestCase;

class ConsentPermissionTest extends TestCase
{
    private ?Consent $current = null;
    private array $reads = [];

    private function permission(?BrowserIdentityInterface $browser = null, ?ConsentStore $records = null): ConsentPermission
    {
        $consent = $this->createStub(ConsentInterface::class);
        $consent->method('getCurrent')->willReturnCallback(function ($purpose) {
            $this->reads[] = $purpose;
            return $this->current;
        });
        $guard = $this->createStub(ConsentGuardInterface::class);
        $guard->method('validCondition')->willReturnCallback(fn ($column) => 'VALID(' . $column . ')');
        $store = $this->createStub(StoreInterface::class);
        $store->method('getWebsiteId')->willReturn(1);
        $stores = $this->createStub(StoreManagerInterface::class);
        $stores->method('getStore')->willReturn($store);
        return new ConsentPermission($consent, $guard, $stores,
            $browser ?? $this->createStub(BrowserIdentityInterface::class), $records ?? $this->createStub(ConsentStore::class));
    }

    private static function consent(int $website = 1, string $purpose = 'test_purpose'): Consent
    {
        return new Consent('new-consent', $purpose, $website, 'banner', 'choice', '2026-01-01 00:00:00', '1', '1',
            '2026-01-01 00:00:00', '2027-01-01 00:00:00');
    }

    private function type(): SubscriptionTypeInterface
    {
        $type = $this->createStub(SubscriptionTypeInterface::class);
        $type->method('getPurpose')->willReturn('test_purpose');
        return $type;
    }

    public function testAValidConsentForThePurposeAndWebsiteIsTheReference(): void
    {
        $this->current = self::consent();
        $permission = $this->permission()->check($this->type(), new Target(42, 1, true));
        $this->assertTrue($permission->isGranted());
        $this->assertSame('new-consent', $permission->reference);
        $this->assertSame(['test_purpose'], $this->reads);
    }

    public function testNoConsentIsAbsent(): void
    {
        $permission = $this->permission()->check($this->type(), new Target(42, 1, true));
        $this->assertFalse($permission->isGranted());
        $this->assertTrue($permission->removesExisting, 'a guest without consent is unsubscribed');
    }

    public function testConsentOfAnotherWebsiteOrPurposeIsRefused(): void
    {
        foreach ([self::consent(2), self::consent(1, 'other')] as $consent) {
            $this->current = $consent;
            $permission = $this->permission()->check($this->type(), new Target(42, 1, true));
            $this->assertFalse($permission->isGranted());
            $this->assertFalse($permission->removesExisting, 'nothing changes');
        }
    }

    public function testTheSqlConditionIsConsentsGuard(): void
    {
        $this->assertSame('VALID(s.permission_ref)', $this->permission()->validCondition('s.permission_ref'));
    }

    /** The visitor is the browser the consent was given in. */
    public function testIdentifiesTheVisitorByTheConsentsBrowser(): void
    {
        $browser = $this->createStub(BrowserIdentityInterface::class);
        $browser->method('hash')->willReturnOnConsecutiveCalls('aaa', 'bbb', '', 'aaa', 'aaa');
        $records = $this->createStub(ConsentStore::class);
        $records->method('load')->willReturnCallback(fn ($id) => match ($id) {
            'mine' => ['browser_hash' => 'aaa'], 'blank' => ['browser_hash' => ''], default => null,
        });
        $permission = $this->permission($browser, $records);
        $this->assertTrue($permission->identifiesVisitor());
        $this->assertTrue($permission->isCurrentVisitor('mine'), 'same browser');
        $this->assertFalse($permission->isCurrentVisitor('mine'), 'another browser');
        $this->assertFalse($permission->isCurrentVisitor('mine'), 'a browser without an id');
        $this->assertFalse($permission->isCurrentVisitor('blank'), 'a consent without a browser');
        $this->assertFalse($permission->isCurrentVisitor('gone'), 'a deleted consent');
    }
}
