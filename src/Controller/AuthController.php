<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\JWTService;
use App\Service\ApiResponseService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api')]
class AuthController extends AbstractController
{
    public function __construct(
        private JWTService $jwtService,
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
        private ApiResponseService $apiResponse,
        private ValidatorInterface $validator
    ) {}

    #[Route('/login', name: 'api_login', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        if (!isset($data['email']) || !isset($data['password'])) {
            return $this->apiResponse->error('Email and password required', null, 400);
        }

        $user = $this->entityManager->getRepository(User::class)->findOneBy([
            'email' => $data['email'],
            'isActive' => true
        ]);
        
        if (!$user || !$this->passwordHasher->isPasswordValid($user, $data['password'])) {
            return $this->apiResponse->error('Invalid credentials', null, 401);
        }

        $tokens = $this->jwtService->createTokenPair($user);
        $tokens['user'] = [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'username' => $user->getUsername(),
            'roles' => $user->getRoles()
        ];

        return $this->apiResponse->success($tokens, 'Login successful');
    }

    #[Route('/refresh', name: 'api_refresh', methods: ['POST'])]
    public function refresh(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        if (!isset($data['refresh_token'])) {
            return $this->apiResponse->error('Refresh token required', null, 400);
        }

        $tokens = $this->jwtService->refreshAccessToken($data['refresh_token']);

        if (!$tokens) {
            return $this->apiResponse->error('Invalid or expired refresh token', null, 401);
        }

        return $this->apiResponse->success($tokens, 'Token refreshed successfully');
    }

    #[Route('/logout', name: 'api_logout', methods: ['POST'])]
    public function logout(Request $request, #[CurrentUser] User $user): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        if (isset($data['refresh_token'])) {
            $this->jwtService->revokeRefreshToken($data['refresh_token']);
        }

        return $this->apiResponse->success(null, 'Logged out successfully');
    }

    #[Route('/logout-all', name: 'api_logout_all', methods: ['POST'])]
    public function logoutAll(#[CurrentUser] User $user): JsonResponse
    {
        $this->jwtService->revokeAllUserTokens($user);
        return $this->apiResponse->success(null, 'Logged out from all devices');
    }

    #[Route('/me', name: 'api_me', methods: ['GET'])]
    public function me(#[CurrentUser] User $user): JsonResponse
    {
        $userData = [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'username' => $user->getUsername(),
            'firstName' => $user->getFirstName(),
            'bio' => $user->getBio(),
            'profilePicture' => $user->getProfilePicture(),
            'roles' => $user->getRoles(),
            'createdAt' => $user->getCreatedAt()->format('Y-m-d H:i:s')
        ];

        return $this->apiResponse->success($userData, 'User profile retrieved');
    }

    #[Route('/register', name: 'api_register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        $user = new User();
        $user->setEmail($data['email'] ?? '');
        $user->setUsername($data['username'] ?? '');
        $user->setFirstName($data['firstName'] ?? '');
        
        if (isset($data['password'])) {
            $user->setPassword($this->passwordHasher->hashPassword($user, $data['password']));
        }
        
        $errors = $this->validator->validate($user);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return $this->apiResponse->error('Validation failed', $errorMessages, 422);
        }

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $userData = [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'username' => $user->getUsername()
        ];

        return $this->apiResponse->success($userData, 'User registered successfully', null);
    }
}