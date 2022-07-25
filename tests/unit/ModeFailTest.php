<?php

declare(strict_types=1);

namespace Firehed\Cache;

use PHPUnit\Framework\MockObject\MockObject;
use Psr\SimpleCache\{
    CacheException,
    CacheInterface,
};
use Redis;
use RedisException;

/**
 * @covers Firehed\Cache\RedisPsr16
 */
class ModeFailTest extends \PHPUnit\Framework\TestCase
{
    /** @var Redis&MockObject $redis */
    private Redis $redis;

    private RedisPsr16 $cache;

    public function setUp(): void
    {
        $ex = new RedisException();

        $this->redis = $this->createMock(Redis::class);
        $methods = [
            'get',
            'mget',
            'set',
            'setex',
            'del',
            'mset',
            'exists',
            'flushAll',
            'flushDB',
        ];
        foreach ($methods as $method) {
            $this->redis->method($method)
                ->willThrowException($ex);
        }
        $this->cache = new RedisPsr16($this->redis, ErrorMode::FAIL);
    }

    public function testConstructDoesNotThrow(): void
    {
        // Bypasses normal setup code
        $redis = $this->createMock(Redis::Class);
        $redis->method('ping')
            ->willThrowException(new RedisException());
        $cache = new RedisPsr16($redis, ErrorMode::FAIL);
        self::assertInstanceOf(CacheInterface::class, $cache);
    }

    public function testGetReturnsDefaultNull(): void
    {
        self::assertNull($this->cache->get('key'));
    }

    public function testGetReturnsDefaultValue(): void
    {
        self::assertSame(
            'default',
            $this->cache->get('key', 'default'),
        );
    }

    public function testSetFails(): void
    {
        self::assertFalse($this->cache->set('key', 'value'));
    }

    public function testDeleteFails(): void
    {
        self::assertFalse($this->cache->delete('key'));
    }

    public function testClearFails(): void
    {
        self::assertFalse($this->cache->clear());
    }

    public function testGetMultipleReturnsDefaultNull(): void
    {
        self::assertSame(
            ['key1' => null, 'key2' => null],
            $this->cache->getMultiple(['key1', 'key2']),
        );
    }

    public function testGetMultipleReturnsDefaultValue(): void
    {
        self::assertSame(
            ['key1' => 'default', 'key2' => 'default'],
            $this->cache->getMultiple(['key1', 'key2'], 'default'),
        );
    }

    public function testSetMultipleFails(): void
    {
        self::assertFalse($this->cache->setMultiple(['key1' => 'value1', 'key2' => 'value2']));
    }

    public function testSetMultipleTtlFails(): void
    {
        self::assertFalse($this->cache->setMultiple(['key1' => 'value1', 'key2' => 'value2'], 30));
    }

    public function testDeleteMultipleFails(): void
    {
        self::assertFalse($this->cache->deleteMultiple(['key1', 'key2']));
    }

    public function testHasFails(): void
    {
        self::assertFalse($this->cache->has('key'));
    }
}
