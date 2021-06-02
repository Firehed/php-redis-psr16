<?php

declare(strict_types=1);

namespace Firehed\Redis;

/**
 * @covers Firehed\Redis\RedisPsr16
 */
class RedisPsr16Test extends \PHPUnit\Framework\TestCase
{
    public function testSmoke(): void
    {
        $cache = new RedisPsr16('localhost', 6379);
        self::assertNull($cache->get('foo'), 'Get before set');
        self::assertTrue($cache->set('foo', 'bar'), 'Set');
        self::assertSame('bar', $cache->get('foo'), 'Get after set');
    }
}
