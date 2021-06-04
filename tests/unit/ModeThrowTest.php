<?php

declare(strict_types=1);

namespace Firehed\Cache;

use PHPUnit\Framework\MockObject\MockObject;
use Psr\SimpleCache\CacheException;
use Redis;
use RedisException;

/**
 * @covers Firehed\Cache\RedisPsr16
 */
class ModeThrowTest extends \PHPUnit\Framework\TestCase
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
            'flushDb',
        ];
        foreach ($methods as $method) {
            $this->redis->method($method)
                ->willThrowException($ex);
        }
        $this->cache = new RedisPsr16($this->redis, RedisPsr16::MODE_THROW);
    }

    public function testConstructThrows(): void
    {
        // Bypasses normal setup code
        $redis = $this->createMock(Redis::Class);
        $redis->method('ping')
            ->willThrowException(new RedisException());
        self::expectException(CacheException::class);
        new RedisPsr16($redis, RedisPsr16::MODE_THROW);
    }

    public function testGetThrows(): void
    {
        self::expectException(CacheException::class);
        $this->cache->get('key');
    }

    public function testSetThrows(): void
    {
        self::expectException(CacheException::class);
        $this->cache->set('key', 'value');
    }

    public function testDeleteThrows(): void
    {
        self::expectException(CacheException::class);
        $this->cache->delete('key');
    }

    public function testClearThrows(): void
    {
        self::expectException(CacheException::class);
        $this->cache->clear();
    }

    public function testGetMultipleThrows(): void
    {
        self::expectException(CacheException::class);
        $this->cache->getMultiple(['key1', 'key2']);
    }

    public function testSetMultipleThrows(): void
    {
        self::expectException(CacheException::class);
        $this->cache->setMultiple(['key1' => 'value1', 'key2' => 'value2']);
    }

    public function testDeleteMultipleThrows(): void
    {
        self::expectException(CacheException::class);
        $this->cache->deleteMultiple(['key1', 'key2']);
    }

    public function testHasThrows(): void
    {
        self::expectException(CacheException::class);
        $this->cache->has('key');
    }
}
