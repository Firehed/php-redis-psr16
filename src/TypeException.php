<?php

declare(strict_types=1);

namespace Firehed\Cache;

use LogicException;
use Psr\SimpleCache\InvalidArgumentException;

class TypeException extends LogicException implements InvalidArgumentException
{
}
