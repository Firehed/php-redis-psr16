<?php

declare(strict_types=1);

namespace Firehed\Cache;

use LogicException;
use Psr\SimpleCache\InvalidArgumentException;

/**
 * @internal - client code should only look for PSR exceptions, not these
 * specific implementations.
 */
class TypeException extends LogicException implements InvalidArgumentException
{
}
