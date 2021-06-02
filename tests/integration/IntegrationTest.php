<?php

declare(strict_types=1);

namespace Firehed\Cache;

use Redis;

use function array_keys;
use function array_map;
use function assert;
use function getenv;

/**
 * @covers Firehed\Cache\RedisPsr16
 */
class IntegrationTest extends \PHPUnit\Framework\TestCase
{
    private string $host;
    private int $port;

    private Redis $redis;

    public function setUp(): void
    {
        $host = getenv('REDIS_HOST');
        assert($host !== false, 'REDIS_HOST not set');
        $port = (int)getenv('REDIS_PORT');
        assert($port > 0, 'REDIS_PORT not set or invalid');
        $this->host = $host;
        $this->port = $port;
        $redis = new Redis();
        $redis->connect($this->host, $this->port);
        // Clear cache between every test
        $redis->flushAll();
        $this->redis = $redis;
    }

    public function testGetAndSet(): void
    {
        $cache = new RedisPsr16($this->redis);
        self::assertNull($cache->get('foo'), 'Get before set');
        self::assertTrue($cache->set('foo', 'bar'), 'Set');
        self::assertSame('bar', $cache->get('foo'), 'Get after set');
    }

    public function testGetAndSetMultiples(): void
    {
        $cache = new RedisPsr16($this->redis);
        $data = [
            'foo' => 'bar',
            'foo2' => 'bar2',
        ];
        $initial = $cache->getMultiple(array_keys($data));
        self::assertEqualsCanonicalizing(array_map(fn () => null, $data), $initial);

        $cache->setMultiple($data);
        $afterSetting = $cache->getMultiple(array_keys($data));
        self::assertEqualsCanonicalizing($data, $afterSetting);
    }

    public function testReconnect(): void
    {
        self::markTestSkipped('Future functionality');
    }

    public function testFallback(): void
    {
        self::markTestSkipped('Future functionality');
    }
}
