<?php
declare(strict_types=1);

namespace CommerceKings\ConsentNotification\Test\Unit\Model;

use CommerceKings\ConsentNotification\Model\StaleSubscriptionCleanup;
use CommerceKings\Notification\Api\SubscriptionRemovalInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use PHPUnit\Framework\TestCase;

class StaleSubscriptionCleanupTest extends TestCase
{
    private array $wheres = [];
    private array $removed = [];
    /** types with their own permission */
    private array $own = [];

    private function cleanup(array $batches, ?int $removes = null): StaleSubscriptionCleanup
    {
        $select = $this->createStub(Select::class);
        foreach (['distinct', 'from', 'joinLeft', 'limit'] as $method) {
            $select->method($method)->willReturnSelf();
        }
        $select->method('where')->willReturnCallback(function ($condition) use ($select) {
            $this->wheres[] = $condition;
            return $select;
        });
        $db = $this->createStub(AdapterInterface::class);
        $db->method('select')->willReturn($select);
        $db->method('quoteInto')->willReturnCallback(fn ($text, $value) => str_replace('?', "'" . implode("', '", (array)$value) . "'", $text));
        $db->method('fetchCol')->willReturnCallback(function () use (&$batches) { return array_shift($batches) ?? []; });
        $resource = $this->createStub(ResourceConnection::class);
        $resource->method('getConnection')->willReturn($db);
        $resource->method('getTableName')->willReturnArgument(0);
        $removal = $this->createStub(SubscriptionRemovalInterface::class);
        $removal->method('removeByPermission')->willReturnCallback(function ($references) use ($removes) {
            $this->removed[] = $references;
            return $removes ?? count($references);
        });
        $permissions = $this->createStub(\CommerceKings\Notification\Model\PermissionResolver::class);
        $permissions->method('ownPermissionTypes')->willReturnCallback(fn () => $this->own);
        return new StaleSubscriptionCleanup($resource, $removal, $permissions);
    }

    public function testRemovesSubscriptionsOfWithdrawnExpiredOrDeletedConsentsInBatches(): void
    {
        $this->assertSame(3, $this->cleanup([['c1', 'c2'], ['c3']])->execute());
        $this->assertSame([['c1', 'c2'], ['c3']], $this->removed);
        $this->assertSame(["s.permission_ref <> ''",
            '(c.consent_id IS NULL) OR c.withdrawn_at IS NOT NULL OR c.expires_at <= NOW()'], $this->wheres,
            'only stored consent rows decide; subscriptions without a reference (opt-out) are never touched');
    }

    /** A type with its own permission stores references that are no consent ids: "no consent row" is not stale there. */
    public function testAMissingConsentRowIsStaleOnlyForShopWideTypes(): void
    {
        $this->own = ['sms_offer'];
        $this->cleanup([])->execute();
        $this->assertSame("(c.consent_id IS NULL AND s.type NOT IN ('sms_offer')) OR c.withdrawn_at IS NOT NULL"
            . ' OR c.expires_at <= NOW()', $this->wheres[1]);
    }

    public function testABatchThatRemovesNothingEndsTheLoop(): void
    {
        $this->assertSame(0, $this->cleanup(array_fill(0, 20, ['c1']), 0)->execute());
        $this->assertCount(1, $this->removed);
    }

    public function testNothingStaleChangesNothing(): void
    {
        $this->assertSame(0, $this->cleanup([])->execute());
        $this->assertSame([], $this->removed);
    }
}
