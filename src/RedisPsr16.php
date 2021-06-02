<?php

declare(strict_types=1);

namespace Firehed\Redis;

use Psr\SimpleCache\CacheInterface;
use Redis;
use RedisException;

use function array_map;
use function array_values;
use function is_array;
use function iterator_to_array;

/**
 * TODO:
 * - regularly try to reconnect
 * - auth?
 * - connection config
 */
class RedisPsr16 implements CacheInterface
{
    // Default value on cache miss. This is inherent to the actual Redis driver
    private const MISS_DEFAULT = false;

    public function __construct(private Redis $conn)
    {
        try {
            $this->conn->ping();
        } catch (RedisException $e) {
            throw new Exception(Exception::ERROR_PING, $e);
        }
    }

    public function get($key, $default = null): mixed
    {
        return $this->getMultiple([$key], $default)[$key];
    }

    public function set($key, $value, $ttl = null): bool
    {
        return $this->setMultiple([$key => $value], $ttl);
    }

    public function delete($key): bool
    {
        return $this->deleteMultiple([$key]);
    }

    public function clear(): bool
    {
        return $this->conn->flushAll();
    }

    /**
     * @param iterable<string> $keys
     * @return array<string, mixed>
     */
    public function getMultiple($keys, $default = null)
    {
        $keys = is_array($keys) ? array_values($keys) : iterator_to_array($keys);
        $raw = $this->conn->mget($keys);
        $values = ($default === self::MISS_DEFAULT)
            ? $raw
            : array_map(
                fn ($key, $response) => $response === false ? $default : $response,
                $keys,
                $raw,
            );
        return array_combine($keys, $values);
    }

    public function setMultiple($values, $ttl = null): bool
    {
        if ($ttl === null) {
            return $this->conn->mset($values);
        } elseif (!is_int($ttl)) {
            throw new \Exception('DateInterval not supported');
        }
        $ok = true;
        foreach ($values as $key => $value) {
            if (!$this->conn->setex($key, $ttl, $value)) {
                $ok = false;
            }
        }
        return $ok;
    }

    public function deleteMultiple($keys): bool
    {
        $keys = is_array($keys) ? array_values($keys) : iterator_to_array($keys);
        $result = $this->conn->del($keys);

        return $result === count($keys);
    }

    public function has($key): bool
    {
        return $this->conn->exists($key) > 0;
    }
}
