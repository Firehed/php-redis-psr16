<?php

declare(strict_types=1);

namespace Firehed\Redis;

use RuntimeException;
use Psr\SimpleCache\CacheException;

class Exception extends RuntimeException implements CacheException
{

}
