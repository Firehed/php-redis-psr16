<?php

declare(strict_types=1);

namespace Firehed\Cache;

use PHPUnit\Framework\MockObject\MockObject;
use Psr\SimpleCache\CacheException;
use Redis;
use RedisException;

use function array_keys;
use function array_map;

/**
 * @covers Firehed\Cache\RedisPsr16
 */
class RedisPsr16Test extends \PHPUnit\Framework\TestCase
{
    /** @var Redis&MockObject $redis */
    private Redis $redis;

    private RedisPsr16 $cache;

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

    public function testGetMultipleUniquesValues(): void
    {
        $data = [
            'key' => 'hit',
            'key2' => false,
        ];
        $this->expectMget($data);
        $results = $this->cache->getMultiple(['key', 'key2', 'key', 'key2']);

        self::assertEqualsCanonicalizing($data, $results);
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

    public function testSet(): void
    {
        $this->redis->expects(self::once())
            ->method('mset')
            ->with(['key' => 'value'])
            ->willReturn(true);

        self::assertTrue($this->cache->set('key', 'value'));
    }

    public function testSetFailure(): void
    {
        $this->redis->expects(self::once())
            ->method('mset')
            ->with(['key' => 'value'])
            ->willReturn(false);

        self::assertFalse($this->cache->set('key', 'value'));
    }

    public function testSetTtl(): void
    {
        $this->redis->expects(self::once())
            ->method('setex')
            ->with('key', 50, 'value')
            ->willReturn(true);

        self::assertTrue($this->cache->set('key', 'value', 50));
    }

    public function testSetTtlFailure(): void
    {
        $this->redis->expects(self::once())
            ->method('setex')
            ->with('key', 50, 'value')
            ->willReturn(false);

        self::assertFalse($this->cache->set('key', 'value', 50));
    }

    public function testSetMultiple(): void
    {
        $this->redis->expects(self::once())
            ->method('mset')
            ->with([
                'key' => 'value',
                'key2' => 'value2',
            ])
            ->willReturn(true);

        self::assertTrue($this->cache->setMultiple([
            'key' => 'value',
            'key2' => 'value2',
        ]));
    }

    public function testSetMultipleFailure(): void
    {
        $this->redis->expects(self::once())
            ->method('mset')
            ->with([
                'key' => 'value',
                'key2' => 'value2',
            ])
            ->willReturn(false);

        self::assertFalse($this->cache->setMultiple([
            'key' => 'value',
            'key2' => 'value2',
        ]));
    }

    public function testSetMultipleTtl(): void
    {
        $this->redis->expects(self::exactly(2))
            ->method('setex')
            ->withConsecutive(
                ['key', 50, 'value'],
                ['key2', 50, 'value2'],
            )
            ->willReturn(true);

        self::assertTrue($this->cache->setMultiple([
            'key' => 'value',
            'key2' => 'value2',
        ], 50));
    }

    public function testSetMultipleTtlFailure(): void
    {
        $this->redis->expects(self::exactly(2))
            ->method('setex')
            ->withConsecutive(
                ['key', 50, 'value'],
                ['key2', 50, 'value2'],
            )
            ->willReturnOnConsecutiveCalls(false, true);

        self::assertFalse($this->cache->setMultiple([
            'key' => 'value',
            'key2' => 'value2',
        ], 50));
    }

    public function testHas(): void
    {
        $this->redis->expects(self::once())
            ->method('exists')
            ->with('key')
            ->willReturn(1);

        self::assertTrue($this->cache->has('key'));
    }

    public function testHasNot(): void
    {
        $this->redis->expects(self::once())
            ->method('exists')
            ->with('key')
            ->willReturn(0);

        self::assertFalse($this->cache->has('key'));
    }

    public function testClear(): void
    {
        // This must clear only the current database.
        $this->redis->expects(self::once())
            ->method('flushDB')
            ->willReturn(true);
        $this->redis->expects(self::never())
            ->method('flushAll');

        self::assertTrue($this->cache->clear());
    }

    public function testDelete(): void
    {
        $this->redis->expects(self::once())
            ->method('del')
            ->with(['key'])
            ->willReturn(1);

        self::assertTrue($this->cache->delete('key'));
    }

    public function testDeleteFailure(): void
    {
        $this->redis->expects(self::once())
            ->method('del')
            ->with(['key'])
            ->willReturn(0);

        self::assertFalse($this->cache->delete('key'));
    }

    public function testDeleteMultiple(): void
    {
        $this->redis->expects(self::once())
            ->method('del')
            ->with(['key', 'key2'])
            ->willReturn(2);

        self::assertTrue($this->cache->deleteMultiple(['key', 'key2']));
    }

    public function testDeleteMultipleUniquesValues(): void
    {
        $this->redis->expects(self::once())
            ->method('del')
            ->with(['key', 'key2'])
            ->willReturn(2);

        self::assertTrue($this->cache->deleteMultiple(['key', 'key2', 'key', 'key2']));
    }

    public function testDeleteMultipleSomeFail(): void
    {
        $this->redis->expects(self::once())
            ->method('del')
            ->with(['key', 'key2'])
            ->willReturn(1);

        self::assertFalse($this->cache->deleteMultiple(['key', 'key2']));
    }

    public function testDeleteMultipleAllFail(): void
    {
        $this->redis->expects(self::once())
            ->method('del')
            ->with(['key', 'key2'])
            ->willReturn(0);

        self::assertFalse($this->cache->deleteMultiple(['key', 'key2']));
    }

    public function testSetMode(): void
    {
        $this->redis->expects(self::exactly(2))
            ->method('mget')
            ->willThrowException(new RedisException());
        try {
            $this->cache->get('key');
            self::fail('First attempt should have thrown');
        } catch (CacheException) {
            // no-op
        }

        $this->cache->setMode(RedisPsr16::MODE_FAIL);
        self::assertNull($this->cache->get('key'));
    }

    public function testEmptyGetMultiple(): void
    {
        $this->redis->expects(self::never())
            ->method('mget');
        $this->cache->getMultiple([]);
    }

    public function testEmptyDeleteMultiple(): void
    {
        $this->redis->expects(self::never())
            ->method('del');
        $this->cache->deleteMultiple([]);
    }

    public function testEmptySetMultiple(): void
    {
        $this->redis->expects(self::never())
            ->method('mset');
        $this->cache->setMultiple([]);
    }

    public function testReconnect(): void
    {
        self::markTestSkipped('Future functionality');
    }

    public function testFallback(): void
    {
        self::markTestSkipped('Future functionality');
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
