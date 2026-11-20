<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/user')]
final class UserProfileController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {}

    #[Route('/profile', name: 'api_user_profile_get', methods: ['GET'])]
    public function getProfile(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->json($user->toProfileArray());
    }

    #[Route('/profile', name: 'api_user_profile_update', methods: ['PUT'])]
    public function updateProfile(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            return $this->json(['error' => 'Données invalides'], 400);
        }

        if (array_key_exists('firstName', $data)) {
            $val = $data['firstName'];
            if ($val !== null && (!is_string($val) || mb_strlen($val) > 100)) {
                return $this->json(['error' => 'Prénom invalide (100 caractères max)'], 400);
            }
            $user->setFirstName($val);
        }

        if (array_key_exists('lastName', $data)) {
            $val = $data['lastName'];
            if ($val !== null && (!is_string($val) || mb_strlen($val) > 100)) {
                return $this->json(['error' => 'Nom invalide (100 caractères max)'], 400);
            }
            $user->setLastName($val);
        }

        if (array_key_exists('age', $data)) {
            $val = $data['age'];
            if ($val !== null) {
                $val = (int) $val;
                if ($val < 0 || $val > 150) {
                    return $this->json(['error' => 'Âge invalide'], 400);
                }
            }
            $user->setAge($val);
        }

        if (array_key_exists('gender', $data)) {
            $val = $data['gender'];
            if ($val !== null && !in_array($val, ['male', 'female'], true)) {
                return $this->json(['error' => 'Valeur invalide pour gender'], 400);
            }
            $user->setGender($val);
        }

        if (array_key_exists('aiDescription', $data)) {
            $user->setAiDescription($data['aiDescription']);
        }

        if (array_key_exists('savedPhrases', $data)) {
            $val = $data['savedPhrases'];
            if (!is_array($val)) {
                return $this->json(['error' => 'savedPhrases doit être un tableau'], 400);
            }
            $user->setSavedPhrases(array_values(array_filter($val, 'is_string')));
        }

        if (array_key_exists('customContexts', $data)) {
            $val = $data['customContexts'];
            if (!is_array($val)) {
                return $this->json(['error' => 'customContexts doit être un tableau'], 400);
            }
            $sanitized = [];
            foreach ($val as $ctx) {
                if (is_array($ctx) && isset($ctx['id'], $ctx['label']) && is_string($ctx['id']) && is_string($ctx['label'])) {
                    $sanitized[] = ['id' => $ctx['id'], 'label' => $ctx['label']];
                }
            }
            $user->setCustomContexts($sanitized);
        }

        $this->em->flush();

        return $this->json($user->toProfileArray());
    }
}
