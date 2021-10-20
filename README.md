# PSR-16 Redis

This is a Redis implementation for the [PSR-16](https://www.php-fig.org/psr/psr-16/) SimpleCache interface.

This library actually uses the multi-key operators supported by Redis (`mget`, etc), unlike most other implementations at the time of writing.

## Installation and Usage

Install: `composer require firehed/redis-psr16`

Usage:

```php
$redis = new \Redis();
$redis->connect('yourhost', 6379);
$redis->auth(['user' => 'youruser', 'pass' => 'yourpass']);

$cache = new \Firehed\Cache\RedisPsr16($redis);
// Use like any other PSR-16 implementation
```

If `Redis::OPT_SERIALIZER` is not set (or uses the default `Redis::SERIALIZER_NONE`), this library will automatically set it to `Redis::SERIALIZER_PHP`.
This will ensure that non-string values are stored and retreived correctly.
Be aware that this means if any `object`s are cached, any magic methods related to serialization (`__sleep()`, `__wakeup()`, `__serialize()`, `__unserialize()`) will be called during caching operations.
Setting that option to any other value before providing Redis to this library will use the set serializer:

```php
use Firehed\Cache\RedisPsr16;
use Redis;

// Automatically sets SERIALIZER_PHP:
$redis = new Redis();
// connect/auth
$cache = new RedisPsr16($redis);

// Uses specified SERIALIZER_JSON
$redis = new Redis();
// connect/auth
$redis->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_JSON);
$cache = new RedisPsr16($redis);
```

### Configuration

A runtime mode can be set via the `$mode` constructor parameter:

```php
use Firehed\Cache\RedisPsr16;

$cache = new RedisPsr16($redis, RedisPsr16::MODE_THROW);
```

- `RedisPsr16::MODE_THROW` may throw exceptions on network issues (in the same way directly using the `Redis` extension can).
  Exceptions thrown will implement `Psr\SimpleCache\CacheException`, per PSR-16 requirements.
  This will help expose networking issues and may be beneficial for logging and error handling, but does require calling libraries to handle them.
  _This is the default mode._

- `RedisPsr16::MODE_FAIL` will prevent exceptions from being thrown.
  Any error, including networking errors (where the `Redis` extension throws) will be treated as a failure.
  This could result in misleading behavior around cache misses; if it's important for your application to know the difference between "miss" and "Redis unavailable", do not use this mode.

The mode can be adjusted at runtime with `RedisPsr16::setMode($mode)`.
