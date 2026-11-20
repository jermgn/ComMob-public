<?php

namespace App\AI;

interface KeywordGeneratorInterface
{
    /**
     * @return string[] Liste de mots-clés
     */
    public function generate(string $contextLabel): array;
}
