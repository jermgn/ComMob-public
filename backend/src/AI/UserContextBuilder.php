<?php

namespace App\AI;

use App\Entity\User;

final class UserContextBuilder
{
    private const GENDER_MAP = [
        'male' => 'homme',
        'female' => 'femme',
    ];

    public function build(User $user): string
    {
        $parts = [];

        // --- Identité ---
        $identity = [];
        if ($user->getFirstName()) {
            $identity[] = "s'appelle " . $user->getFirstName();
        }
        if ($user->getAge()) {
            $identity[] = 'a ' . $user->getAge() . ' ans';
        }
        if ($user->getGender() && isset(self::GENDER_MAP[$user->getGender()])) {
            $identity[] = 'est un(e) ' . self::GENDER_MAP[$user->getGender()];
        }
        if (!empty($identity)) {
            $parts[] = "L'utilisateur " . implode(', ', $identity) . '.';
        }

        // --- Personnalisation IA ---
        if ($user->getAiDescription()) {
            $parts[] = "Description de l'utilisateur : " . $user->getAiDescription();
        }

        if (empty($parts)) {
            return '';
        }

        return "PROFIL UTILISATEUR :\n" . implode("\n", $parts);
    }
}
