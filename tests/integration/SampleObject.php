<?php

declare(strict_types=1);

namespace Firehed\Cache;

class SampleObject
{
    public function __construct(
        public int $int,
        public string $string,
        public float $float,
        public bool $bool,
    ) {
    }
}
