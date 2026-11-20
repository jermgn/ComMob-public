<?php

namespace App\Context;

interface ContextResolverInterface
{
    /**
     * @return ContextCandidate[]
     */
    public function resolveCandidates(float $lat, float $lon): array;

    /**
     * @return ContextCandidate[]
     */
    public function getAllContexts(): array;

    public function resolveContext(string $contextId): ContextResult;
}