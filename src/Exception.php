<?php

declare(strict_types=1);

namespace Firehed\Redis;

use RedisException;
use RuntimeException;
use Psr\SimpleCache\CacheException;

class Exception extends RuntimeException implements CacheException
{
    public const ERROR_PING = 1;
    public const ERROR_GONE = 2;

    /**
     * @param self::ERROR_* $error
     */
    public function __construct(int $error, RedisException $prev)
    {
        $message = match ($error) {
            self::ERROR_PING => 'Cannot ping server. Did you connect() and/or auth() first?',
            self::ERROR_GONE => '',
        };

        parent::__construct($message, $error, previous: $prev);
    }
}
