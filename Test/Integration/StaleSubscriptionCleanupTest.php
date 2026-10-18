<?php
declare(strict_types=1);

namespace CommerceKings\ConsentNotification\Test\Integration;

use CommerceKings\ConsentNotification\Model\StaleSubscriptionCleanup;
use CommerceKings\Notification\Model\SubscriptionManagement;
use Magento\Framework\App\Bootstrap;
use Magento\Framework\App\ResourceConnection;
use PHPUnit\Framework\TestCase;

/** Real SQL: only subscriptions of withdrawn, expired or deleted consents go; valid and opt-out ones stay. */
class StaleSubscriptionCleanupTest extends TestCase
{
    private ResourceConnection $resource;
    private StaleSubscriptionCleanup $cleanup;
    private string $run;
    private array $references = [];

    protected function setUp(): void
    {
        $root = getenv('MAGENTO_ROOT') ?: (defined('BP') ? BP : getcwd());
        if (!is_file($root . '/app/bootstrap.php')) {
            throw new \RuntimeException('Run from the Magento root or set MAGENTO_ROOT.');
        }
        require_once $root . '/app/bootstrap.php';
        $om = Bootstrap::create(BP, $_SERVER)->getObjectManager();
        $this->resource = $om->get(ResourceConnection::class);
        $this->cleanup = $om->get(StaleSubscriptionCleanup::class);
        $this->run = bin2hex(random_bytes(4));
    }

    protected function tearDown(): void
    {
        $db = $this->resource->getConnection();
        $db->delete($this->resource->getTableName(SubscriptionManagement::TABLE), ['reference_id IN (?)' => $this->references ?: [0]]);
        $db->delete($this->resource->getTableName('ck_consent'), ['consent_id LIKE ?' => 'ckst' . $this->run . '%']);
    }

    private function consent(string $name, string $expires, ?string $withdrawn = null): string
    {
        $id = 'ckst' . $this->run . $name;
        $this->resource->getConnection()->insert($this->resource->getTableName('ck_consent'), [
            'consent_id' => $id, 'purpose' => 'ck_stale_test', 'browser_hash' => hash('sha256', $id), 'website_id' => 1,
            'source' => 'test', 'reference' => $id, 'banner_revision' => '1', 'terms_revision' => '1',
            'expires_at' => new \Zend_Db_Expr($expires),
            'withdrawn_at' => $withdrawn === null ? null : new \Zend_Db_Expr($withdrawn),
        ]);
        return $id;
    }

    private function subscription(string $reference): int
    {
        $this->references[] = $id = random_int(1000000000, 2000000000);
        $this->resource->getConnection()->insert($this->resource->getTableName(SubscriptionManagement::TABLE), [
            'type' => 'ck_stale_test', 'reference_id' => $id, 'store_id' => 1, 'subject_key' => '',
            'customer_id' => null, 'contact_id' => null, 'permission_ref' => $reference,
        ]);
        return $id;
    }

    public function testRemovesOnlySubscriptionsWhoseConsentCanNeverBeValidAgain(): void
    {
        $valid = $this->subscription($this->consent('valid', 'NOW() + INTERVAL 30 DAY'));
        $expired = $this->subscription($this->consent('expired', 'NOW() - INTERVAL 1 DAY'));
        $withdrawn = $this->subscription($this->consent('withdrawn', 'NOW() + INTERVAL 30 DAY', 'NOW()'));
        $deleted = $this->subscription('ckst' . $this->run . 'gone');           // removed by record retention
        $optOut = $this->subscription('');                                      // no permission reference

        $this->assertGreaterThanOrEqual(3, $this->cleanup->execute());

        $db = $this->resource->getConnection();
        $left = array_map('intval', $db->fetchCol($db->select()
            ->from($this->resource->getTableName(SubscriptionManagement::TABLE), 'reference_id')
            ->where('reference_id IN (?)', $this->references)));
        sort($left);
        $expected = [$valid, $optOut];
        sort($expected);
        $this->assertSame($expected, $left, 'valid and opt-out subscriptions stay');
        $this->assertNotContains($expired, $left);
        $this->assertNotContains($withdrawn, $left);
        $this->assertNotContains($deleted, $left);
    }
}
