<?php

namespace App\Context;

final class ContextCandidate
{
    public function __construct(
        public readonly string $id,
        public readonly string $label,
        public readonly float $score
    ) {}
}