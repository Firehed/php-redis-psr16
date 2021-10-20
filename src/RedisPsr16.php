<?php

declare(strict_types=1);

namespace Firehed\Cache;

use Psr\SimpleCache\CacheInterface;
use Redis;
use RedisException;

use function array_combine;
use function array_fill_keys;
use function array_map;
use function array_values;
use function count;
use function is_array;
use function is_int;
use function iterator_to_array;

class RedisPsr16 implements CacheInterface
{
    // Default value on cache miss. This is inherent to the actual Redis driver
    private const MISS_DEFAULT = false;

    public const MODE_THROW = 0;
    public const MODE_FAIL = 1;

    /**
     * @param self::MODE_* $mode Error handling mode
     */
    public function __construct(private Redis $conn, private int $mode = self::MODE_THROW)
    {
        try {
            $this->conn->ping();
            if ($this->conn->getOption(Redis::OPT_SERIALIZER) === Redis::SERIALIZER_NONE) {
                $this->conn->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_PHP);
            }
        } catch (RedisException $e) {
            // Abusing match slightly here, so if a new mode is added in the
            // future, static analysis will find it.
            match ($this->mode) {
                self::MODE_THROW => throw new Exception(Exception::ERROR_PING, $e),
                self::MODE_FAIL => null,
            };
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
        try {
            return $this->deleteMultiple([$key]);
        } catch (RedisException $e) {
            return $this->handleException($e);
        }
    }

    public function clear(): bool
    {
        try {
            return $this->conn->flushDB();
        } catch (RedisException $e) {
            return $this->handleException($e);
        }
    }

    /**
     * @param iterable<string> $keys
     * @return array<string, mixed>
     */
    public function getMultiple($keys, $default = null)
    {
        $keys = is_array($keys) ? array_values($keys) : iterator_to_array($keys);
        try {
            $raw = $this->conn->mget($keys);
        } catch (RedisException $e) {
            return match ($this->mode) {
                self::MODE_THROW => throw new Exception(Exception::ERROR_GONE, $e),
                self::MODE_FAIL => array_fill_keys($keys, $default),
            };
        }
        $values = ($default === self::MISS_DEFAULT)
            ? $raw
            : array_map(
                fn ($key, $response) => $response === false ? $default : $response,
                $keys,
                $raw,
            );
        return array_combine($keys, $values);
    }

    /**
     * @param iterable<string> $values
     */
    public function setMultiple($values, $ttl = null): bool
    {
        $values = is_array($values) ? $values : iterator_to_array($values);

        if ($ttl === null) {
            try {
                return $this->conn->mset($values);
            } catch (RedisException $e) {
                return $this->handleException($e);
            }
        } elseif (!is_int($ttl)) {
            throw new TypeException('DateInterval not supported TTL');
        }
        $ok = true;
        foreach ($values as $key => $value) {
            try {
                if (!$this->conn->setex($key, $ttl, $value)) {
                    $ok = false;
                }
            } catch (RedisException $e) {
                $ok = $this->handleException($e);
            }
        }
        return $ok;
    }

    /**
     * @param iterable<string> $keys
     */
    public function deleteMultiple($keys): bool
    {
        $keys = is_array($keys) ? array_values($keys) : iterator_to_array($keys);
        try {
            $result = $this->conn->del($keys);
        } catch (RedisException $e) {
            return $this->handleException($e);
        }

        return $result === count($keys);
    }

    public function has($key): bool
    {
        try {
            return $this->conn->exists($key) > 0;
        } catch (RedisException $e) {
            return $this->handleException($e);
        }
    }

    /**
     * @param self::MODE_* $mode Error handling mode
     */
    public function setMode(int $mode): void
    {
        $this->mode = $mode;
    }

    /**
     * @return false
     */
    private function handleException(RedisException $e): bool
    {
        return match ($this->mode) {
            self::MODE_THROW => throw new Exception(Exception::ERROR_GONE, $e),
            self::MODE_FAIL => false,
        };
    }
}
