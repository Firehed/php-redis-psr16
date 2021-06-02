<?php

declare(strict_types=1);

namespace Firehed\Redis;

use Redis;

/**
 * @covers Firehed\Redis\RedisPsr16
 */
class RedisPsr16Test extends \PHPUnit\Framework\TestCase
{
    public function testConstructPingsServer(): void
    {
        $mock = $this->createMock(Redis::class);
        $mock->expects(self::once())
            ->method('ping')
            ->willReturn(true);
        $cache = new RedisPsr16($mock);
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
