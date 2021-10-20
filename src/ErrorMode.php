<?php

declare(strict_types=1);

namespace Firehed\Cache;

if (version_compare(PHP_VERSION, '8.1.0', '>=')) {
    enum ErrorMode
    {
        case EXCEPTION;
        case FAIL;
    }
} else {
    interface ErrorMode
    {
        public const EXCEPTION = 0;
        public const FAIL = 1;
    }
}
