<?php

declare(strict_types=1);

namespace Firehed\Redis;

use LogicException;
use Psr\SimpleCache\InvalidArgumentException;

class TypeException extends LogicException implements InvalidArgumentException
{
}
