<?php
declare(strict_types=1);

namespace CommerceKings\ConsentNotification\Test\Unit\Model;

use CommerceKings\Consent\Api\ConsentGuardInterface;
use CommerceKings\Consent\Api\ConsentInterface;
use CommerceKings\Consent\Api\Data\Consent;
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

    private ?Consent $stored = null;
    private bool $valid = true;

    private function permission(): ConsentPermission
    {
        $consent = $this->createStub(ConsentInterface::class);
        $consent->method('getCurrent')->willReturnCallback(function ($purpose) {
            $this->reads[] = $purpose;
            return $this->current;
        });
        $consent->method('get')->willReturnCallback(fn () => $this->stored);
        $consent->method('isValid')->willReturnCallback(fn () => $this->valid);
        $guard = $this->createStub(ConsentGuardInterface::class);
        $guard->method('validCondition')->willReturnCallback(fn ($column) => 'VALID(' . $column . ')');
        $store = $this->createStub(StoreInterface::class);
        $store->method('getWebsiteId')->willReturn(1);
        $stores = $this->createStub(StoreManagerInterface::class);
        $stores->method('getStore')->willReturn($store);
        return new ConsentPermission($consent, $guard, $stores);
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

    /** Consent required: Automatic opt-out notices stay hidden. */
    public function testItIsNotOptOut(): void
    {
        $this->assertFalse($this->permission()->isOptOut());
    }

    /** Send-time recheck: valid, and for this purpose and the store's website (moved from abandoned cart). */
    #[\PHPUnit\Framework\Attributes\DataProvider('sendTimeCases')]
    public function testSendTimeRecheckNeedsAValidConsentOfThisPurposeAndWebsite(bool $valid, ?Consent $stored, bool $expected): void
    {
        $this->valid = $valid;
        $this->stored = $stored;
        $this->assertSame($expected, $this->permission()->isValidFor('new-consent', 'test_purpose', 3));
    }

    public static function sendTimeCases(): array
    {
        return [
            'valid, same purpose and website' => [true, self::consent(), true],
            'withdrawn / expired / old revision' => [false, self::consent(), false],
            'another website' => [true, self::consent(2), false],
            'another purpose' => [true, self::consent(1, 'other'), false],
            'deleted' => [true, null, false],
        ];
    }
}
