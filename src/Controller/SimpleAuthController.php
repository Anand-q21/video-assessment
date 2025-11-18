<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\ApiResponseService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/simple')]
class SimpleAuthController extends AbstractController
{
    public function __construct(
        private ApiResponseService $apiResponse,
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {}

    #[Route('/login', name: 'simple_login', methods: ['POST'])]
    public function simpleLogin(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        $user = $this->entityManager->getRepository(User::class)->findOneBy([
            'email' => $data['email'] ?? '',
            'isActive' => true
        ]);
        
        if (!$user || !$this->passwordHasher->isPasswordValid($user, $data['password'] ?? '')) {
            return $this->apiResponse->error('Invalid credentials', null, 401);
        }

        // Create simple token (base64 encoded user data)
        $tokenData = [
            'user_id' => $user->getId(),
            'email' => $user->getEmail(),
            'exp' => time() + 3600
        ];
        
        $token = base64_encode(json_encode($tokenData));

        return $this->apiResponse->success([
            'token' => $token,
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'username' => $user->getUsername()
            ]
        ], 'Login successful');
    }

    #[Route('/profile', name: 'simple_profile', methods: ['GET'])]
    public function simpleProfile(Request $request): JsonResponse
    {
        $authHeader = $request->headers->get('Authorization');
        
        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return $this->apiResponse->error('Authorization header required', null, 401);
        }

        $token = substr($authHeader, 7);
        $tokenData = json_decode(base64_decode($token), true);

        if (!$tokenData || $tokenData['exp'] < time()) {
            return $this->apiResponse->error('Invalid or expired token', null, 401);
        }

        $user = $this->entityManager->getRepository(User::class)->find($tokenData['user_id']);
        
        if (!$user) {
            return $this->apiResponse->error('User not found', null, 404);
        }

        return $this->apiResponse->success([
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'username' => $user->getUsername(),
            'firstName' => $user->getFirstName(),
            'bio' => $user->getBio(),
            'roles' => $user->getRoles()
        ], 'Profile retrieved');
    }
}