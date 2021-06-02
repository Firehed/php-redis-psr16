<?php

declare(strict_types=1);

namespace Firehed\Redis;

use Redis;

/**
 * @covers Firehed\Redis\RedisPsr16
 */
class RedisPsr16Test extends \PHPUnit\Framework\TestCase
{
    private string $host;
    private int $port;

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
    }

    public function testSmoke(): void
    {
        $cache = new RedisPsr16($this->host, $this->port);
        self::assertNull($cache->get('foo'), 'Get before set');
        self::assertTrue($cache->set('foo', 'bar'), 'Set');
        self::assertSame('bar', $cache->get('foo'), 'Get after set');
    }

    public function testReconnect(): void
    {
    }

    public function testFallback(): void
    {
        // $cache = new RedisPsr16();
        // $cache->setFallbackCache($mock);
    }
}
