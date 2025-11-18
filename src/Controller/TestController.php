<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\ApiResponseService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/test')]
class TestController extends AbstractController
{
    public function __construct(
        private ApiResponseService $apiResponse,
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {}

    #[Route('/create-user', name: 'test_create_user', methods: ['POST'])]
    public function createTestUser(): JsonResponse
    {
        // Create a test user
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setUsername('testuser');
        $user->setFirstName('Test');
        $user->setPassword($this->passwordHasher->hashPassword($user, 'password123'));

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $this->apiResponse->success([
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'username' => $user->getUsername()
        ], 'Test user created');
    }

    #[Route('/public', name: 'test_public', methods: ['GET'])]
    public function publicEndpoint(): JsonResponse
    {
        return $this->apiResponse->success(['message' => 'This is a public endpoint'], 'Public access works');
    }

    #[Route('/jwt-test', name: 'test_jwt', methods: ['POST'])]
    public function testJWT(): JsonResponse
    {
        // Find or create test user
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => 'test@example.com']);
        
        if (!$user) {
            $user = new User();
            $user->setEmail('test@example.com');
            $user->setUsername('testuser');
            $user->setFirstName('Test');
            $user->setPassword($this->passwordHasher->hashPassword($user, 'password123'));
            $this->entityManager->persist($user);
            $this->entityManager->flush();
        }

        // Generate token using Lexik JWT directly
        $jwtManager = $this->container->get('lexik_jwt_authentication.jwt_manager');
        $token = $jwtManager->create($user);

        return $this->apiResponse->success([
            'token' => $token,
            'user_id' => $user->getId(),
            'user_email' => $user->getEmail()
        ], 'JWT token generated');
    }
}