<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/auth')]
final class AuthController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserRepository $userRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly JWTTokenManagerInterface $jwtManager,
        private readonly ValidatorInterface $validator,
        private readonly RateLimiterFactory $authLoginLimiter,
        private readonly MailerInterface $mailer,
    ) {}

    #[Route('/register', name: 'api_auth_register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        $limiter = $this->authLoginLimiter->create($request->getClientIp());
        if (false === $limiter->consume(1)->isAccepted()) {
            return $this->json(['error' => 'Trop de tentatives. Réessayez plus tard.'], 429);
        }

        $data = json_decode($request->getContent(), true);

        if (!is_array($data) || !isset($data['email'], $data['password'])) {
            return $this->json(['error' => 'Email et mot de passe requis'], 400);
        }

        $email = strtolower(trim($data['email']));
        $password = $data['password'];

        if (strlen($password) < 8) {
            return $this->json(['error' => 'Le mot de passe doit contenir au moins 8 caractères'], 400);
        }

        if ($this->userRepository->findOneBy(['email' => $email]) !== null) {
            return $this->json(['error' => 'Un compte existe déjà avec cette adresse email'], 409);
        }

        $user = new User();
        $user->setEmail($email);
        $user->setPassword($this->passwordHasher->hashPassword($user, $password));

        $errors = $this->validator->validate($user);
        if (count($errors) > 0) {
            return $this->json(['error' => 'Adresse email invalide'], 400);
        }

        $this->em->persist($user);
        $this->em->flush();

        $token = $this->jwtManager->create($user);

        return $this->json([
            'token' => $token,
            'user' => [
                'email' => $user->getEmail(),
                'roles' => $user->getRoles(),
            ],
        ], 201);
    }

    #[Route('/login', name: 'api_auth_login', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {
        $limiter = $this->authLoginLimiter->create($request->getClientIp());
        if (false === $limiter->consume(1)->isAccepted()) {
            return $this->json(['error' => 'Trop de tentatives. Réessayez plus tard.'], 429);
        }

        $data = json_decode($request->getContent(), true);

        if (!is_array($data) || !isset($data['email'], $data['password'])) {
            return $this->json(['error' => 'Email et mot de passe requis'], 400);
        }

        $user = $this->userRepository->findOneBy(['email' => strtolower(trim($data['email']))]);

        if ($user === null || !$this->passwordHasher->isPasswordValid($user, $data['password'])) {
            return $this->json(['error' => 'Identifiants incorrects'], 401);
        }

        // Générer code OTP 6 chiffres
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $user->setOtpCode($code);
        $user->setOtpExpiresAt(new \DateTimeImmutable('+10 minutes'));
        $this->em->flush();

        // Envoyer le code par email
        $emailMessage = (new Email())
            ->from('no-reply@commob.local')
            ->to($user->getEmail())
            ->subject('Votre code de connexion ComMob')
            ->text(
                "Bonjour,\n\n" .
                "Votre code de connexion ComMob est :\n\n" .
                "    {$code}\n\n" .
                "Ce code est valable 10 minutes.\n\n" .
                "Si vous n'êtes pas à l'origine de cette demande, ignorez ce message."
            );

        $this->mailer->send($emailMessage);

        return $this->json(['requires_2fa' => true]);
    }

    #[Route('/verify-login', name: 'api_auth_verify_login', methods: ['POST'])]
    public function verifyLogin(Request $request): JsonResponse
    {
        $limiter = $this->authLoginLimiter->create($request->getClientIp() . '_verify');
        if (false === $limiter->consume(1)->isAccepted()) {
            return $this->json(['error' => 'Trop de tentatives. Réessayez plus tard.'], 429);
        }

        $data = json_decode($request->getContent(), true);

        if (!is_array($data) || !isset($data['email'], $data['code'])) {
            return $this->json(['error' => 'Email et code requis'], 400);
        }

        $user = $this->userRepository->findOneBy(['email' => strtolower(trim($data['email']))]);

        if ($user === null) {
            return $this->json(['error' => 'Code invalide ou expiré'], 401);
        }

        $code = trim($data['code']);

        if (
            $user->getOtpCode() === null ||
            $user->getOtpCode() !== $code ||
            $user->getOtpExpiresAt() === null ||
            $user->getOtpExpiresAt() < new \DateTimeImmutable()
        ) {
            return $this->json(['error' => 'Code invalide ou expiré'], 401);
        }

        // Invalider le code après usage
        $user->setOtpCode(null);
        $user->setOtpExpiresAt(null);
        $this->em->flush();

        $token = $this->jwtManager->create($user);

        return $this->json([
            'token' => $token,
            'user' => [
                'email' => $user->getEmail(),
                'roles' => $user->getRoles(),
            ],
        ]);
    }
}
