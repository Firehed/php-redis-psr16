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
