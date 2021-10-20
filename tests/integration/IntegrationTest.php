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

    public function testGetMultipleUniquesValues(): void
    {
        $cache = new RedisPsr16($this->redis);
        $data = [
            'foo' => 'bar',
            'foo2' => 'bar2',
        ];
        $results = $cache->getMultiple(['foo', 'foo2', 'foo', 'foo2']);
        self::assertEqualsCanonicalizing(['foo' => null, 'foo2' => null], $results);

        $cache->setMultiple($data);
        $afterSetting = $cache->getMultiple(['foo', 'foo2', 'foo', 'foo2']);
        self::assertEqualsCanonicalizing(['foo' => 'bar', 'foo2' => 'bar2'], $afterSetting);
    }

    public function testDelete(): void
    {
        $cache = new RedisPsr16($this->redis);
        $data = [
            'foo' => 'bar',
            'foo2' => 'bar2',
        ];
        $cache->setMultiple($data);

        $beforeDelete = $cache->getMultiple(['foo', 'foo2']);
        self::assertEqualsCanonicalizing($data, $beforeDelete);

        $result = $cache->deleteMultiple(['foo', 'foo2', 'foo']);
        self::assertTrue($result);
        $afterDelete = $cache->getMultiple(['foo', 'foo2']);
        self::assertEqualsCanonicalizing(['foo' => null, 'foo2' => null], $afterDelete);
    }

    public function testObjectSerialization(): void
    {
        $object = new SampleObject(3, 'three', 3.14, true);
        $cache = new RedisPsr16($this->redis);
        $cache->set(__METHOD__, $object);
        $response = $cache->get(__METHOD__);
        self::assertEquals($object, $response, 'Object changed (loose equality)');
    }

    /**
     * @dataProvider encodingValues
     */
    public function testValueSerialization(mixed $value): void
    {
        $cache = new RedisPsr16($this->redis);
        $cache->set(__METHOD__, $value);
        $response = $cache->get(__METHOD__);
        self::assertSame($value, $response, 'Value changed when returned from cache');
    }

    /**
     * @see https://www.php-fig.org/psr/psr-16/ Section 1.4
     *
     * > If it is not possible to return the exact saved value for any reason,
     * > implementing libraries MUST respond with a cache miss rather than
     * > corrupted data.
     *
     * Note that this behavior isn't _strictly_ true: it's possible to check
     * `->has($key)` on the "miss" and infer the value must be false, but this
     * would require additional roundtrips on all misses, increasing execution
     * time. Given that the utility of caching literal `false` is quite low,
     * the overhead does not seem worth it.
     */
    public function testFalseHandling(): void
    {
        $value = false;
        $cache = new RedisPsr16($this->redis);
        $cache->set(__METHOD__, $value);
        $response = $cache->get(__METHOD__, 'default');
        self::assertSame(
            'default',
            $response,
            '`false` cannot be correctly retreived and must be treated as a cache miss',
        );
    }

    /**
     * @return array{mixed}[]
     */
    public function encodingValues(): array
    {
        return array_map(fn ($v) => [$v], [
            'string',
            0,
            PHP_INT_MIN,
            PHP_INT_MAX,
            -3.14,
            3.14,
            true,
            null,
            [1, 2, 3],
            ['a' => 1, 'b' => 2, 'c' => 3],
            ['a' => ['b' => ['c' => [1, 2, 3]]]],
        ]);
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
