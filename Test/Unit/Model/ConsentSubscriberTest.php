<?php
declare(strict_types=1);

namespace CommerceKings\ConsentNotification\Test\Unit\Model;

use CommerceKings\Consent\Api\ConsentOwnershipInterface;
use CommerceKings\Consent\Api\WithdrawalLinkInterface;
use CommerceKings\ConsentNotification\Model\ConsentSubscriber;
use CommerceKings\Notification\Api\Data\Subscription;
use PHPUnit\Framework\TestCase;

class ConsentSubscriberTest extends TestCase
{
    private function subscriber(): ConsentSubscriber
    {
        $ownership = $this->createStub(ConsentOwnershipInterface::class);
        $ownership->method('isCurrentVisitor')->willReturnCallback(fn ($id) => $id === 'mine');
        $links = $this->createStub(WithdrawalLinkInterface::class);
        $links->method('getUrl')->willReturnCallback(fn ($id) => 'https://shop.test/ckconsent/optout?c=' . $id . '&t=sig');
        return new ConsentSubscriber($ownership, $links);
    }

    /** The subscriber is the browser the consent was given in - Consent's public ownership API decides. */
    public function testTheSubscriberIsTheConsentsBrowser(): void
    {
        $subscriber = $this->subscriber();
        $this->assertTrue($subscriber->identifiesVisitor());
        $this->assertTrue($subscriber->isCurrentVisitor('mine'));
        $this->assertFalse($subscriber->isCurrentVisitor('theirs'));
    }

    public function testUnsubscribeIsConsentsSignedWithdrawalLink(): void
    {
        $subscription = fn (string $ref) => new Subscription(7, 'cart_reminder', 42, 1, 5, null, $ref, '2026-01-01 00:00:00');
        $this->assertSame('https://shop.test/ckconsent/optout?c=abc&t=sig',
            $this->subscriber()->getUnsubscribeUrl($subscription('abc')));
        $this->assertNull($this->subscriber()->getUnsubscribeUrl($subscription('')), 'no consent, no link');
    }
}
