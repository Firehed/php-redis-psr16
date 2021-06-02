<?php

declare(strict_types=1);

namespace Firehed\Redis;

use PHPUnit\Framework\MockObject\MockObject;
use Redis;

/**
 * @covers Firehed\Redis\RedisPsr16
 */
class RedisPsr16Test extends \PHPUnit\Framework\TestCase
{
    /** @var Redis&MockObject $redis */
    private Redis $redis;

    private RedisPsr16 $cache;

    /**
     * @var array<string, mixed> Simulated cache entries
     */
    private array $entries = [];

    public function setUp(): void
    {
        $this->redis = $this->createMock(Redis::class);
        $this->cache = new RedisPsr16($this->redis);
    }

    public function testConstructPingsServer(): void
    {
        $mock = $this->createMock(Redis::class);
        $mock->expects(self::once())
            ->method('ping')
            ->willReturn(true);
        new RedisPsr16($mock);
    }

    public function testGetReturnsHit(): void
    {
        $value = 'hit';
        $this->expectMget(['key' => $value]);
        self::assertSame($value, $this->cache->get('key'));
    }

    public function testGetMultipleReturnsHits(): void
    {
        $data = [
            'k1' => 'v1',
            'k2' => 'v2',
        ];
        $this->expectMget($data);
        self::assertEqualsCanonicalizing($data, $this->cache->getMultiple(array_keys($data)));
    }

    public function testGetReturnsNullOnMiss(): void
    {
        $this->expectMget(['key' => false]);
        self::assertNull($this->cache->get('key'));
    }

    public function testGetMulitpleReturnsNullOnMisses(): void
    {
        $data = [
            'key' => false,
            'key2' => false,
        ];
        $misses = array_map(fn () => null, $data);
        $this->expectMget($data);
        $results = $this->cache->getMultiple(array_keys($data));
        self::assertEqualsCanonicalizing($misses, $results);
    }

    public function testGetMulitpleReturnsMixedHitsDefault(): void
    {
        $data = [
            'key' => 'hit!',
            'key2' => false,
        ];
        $expected = $data;
        $expected['key2'] = 3;
        $this->expectMget($data);
        $results = $this->cache->getMultiple(array_keys($data), 3);
        self::assertEqualsCanonicalizing($expected, $results);
    }

    public function testGetReturnsDefaultOnMiss(): void
    {
        $this->expectMget(['key' => false]);
        self::assertSame(4, $this->cache->get('key', 4));
    }

    public function testGetReturnsDefaultOnMissFalseHotpath(): void
    {
        $this->expectMget(['key' => false]);
        self::assertFalse($this->cache->get('key', false));
    }

    public function testReconnect(): void
    {
    }

    public function testFallback(): void
    {
        // $cache = new RedisPsr16();
        // $cache->setFallbackCache($mock);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function expectMget(array $data): void
    {
        $this->redis->expects(self::once())
            ->method('mget')
            ->with(array_keys($data))
            ->willReturn($data);
    }
}
