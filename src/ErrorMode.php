<?php

declare(strict_types=1);

namespace Firehed\Cache;

if (version_compare(PHP_VERSION, '8.1.0', '>=')) {
    class_alias(ErrorModeGTE81::class, ErrorMode::class);
} else {
    class_alias(ErrorModeLT81::class, ErrorMode::class);
}
