<?php

declare(strict_types=1);

namespace Firehed\Redis;

use Redis;

/**
 * @covers Firehed\Redis\RedisPsr16
 */
class RedisPsr16Test extends \PHPUnit\Framework\TestCase
{
    public function testSmoke(): void
    {
        // $cache = new RedisPsr16($this->host, $this->port);
        // self::assertNull($cache->get('foo'), 'Get before set');
        // self::assertTrue($cache->set('foo', 'bar'), 'Set');
        // self::assertSame('bar', $cache->get('foo'), 'Get after set');
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
