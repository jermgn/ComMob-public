<?php

namespace App\Context;

final class ContextResult
{
    public function __construct(
        public string $context,
        public array $keywords,
        public float $confidence
    ) {}
}