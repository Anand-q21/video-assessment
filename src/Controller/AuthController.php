<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\JWTService;
use App\Service\ApiResponseService;
use App\Service\ValidationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use OpenApi\Attributes as OA;

#[Route('/api')]
class AuthController extends AbstractController
{
    public function __construct(
        private JWTService $jwtService,
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
        private ApiResponseService $apiResponse,
        private ValidatorInterface $validator,
        private ValidationService $validationService
    ) {}

    #[Route('/login', name: 'api_login', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        // Validate request body
        $bodyErrors = $this->validationService->validateRequestBody($data);
        if (!empty($bodyErrors)) {
            return $this->apiResponse->error('Invalid request', $bodyErrors, 400);
        }
        
        // Validate required fields
        $requiredErrors = $this->validationService->validateRequiredFields($data, ['email', 'password']);
        if (!empty($requiredErrors)) {
            return $this->apiResponse->error('Missing required fields', $requiredErrors, 400);
        }
        
        // Validate email format
        $emailErrors = $this->validationService->validateEmail($data['email']);
        if (!empty($emailErrors)) {
            return $this->apiResponse->error('Validation failed', $emailErrors, 400);
        }

        // Sanitize input
        $email = $this->validationService->sanitizeString($data['email']);
        
        $user = $this->entityManager->getRepository(User::class)->findOneBy([
            'email' => $email,
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
        
        // Validate request body
        $bodyErrors = $this->validationService->validateRequestBody($data);
        if (!empty($bodyErrors)) {
            return $this->apiResponse->error('Invalid request', $bodyErrors, 400);
        }
        
        // Validate required fields
        $requiredErrors = $this->validationService->validateRequiredFields($data, ['refresh_token']);
        if (!empty($requiredErrors)) {
            return $this->apiResponse->error('Missing required fields', $requiredErrors, 400);
        }
        
        // Validate token format (basic check)
        if (empty(trim($data['refresh_token'])) || strlen($data['refresh_token']) < 10) {
            return $this->apiResponse->error('Invalid refresh token format', null, 400);
        }

        $tokens = $this->jwtService->refreshAccessToken($data['refresh_token']);

        if (!$tokens) {
            return $this->apiResponse->error('Invalid or expired refresh token', null, 401);
        }

        return $this->apiResponse->success($tokens, 'Token refreshed successfully');
    }

    #[Route('/logout', name: 'api_logout', methods: ['POST'])]
    #[OA\Post(
        path: '/api/logout',
        summary: 'User logout',
        tags: ['Authentication'],
        security: [['JWT' => []]]
    )]
    public function logout(Request $request, #[CurrentUser] User $user): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        if (isset($data['refresh_token'])) {
            $this->jwtService->revokeRefreshToken($data['refresh_token']);
        }

        return $this->apiResponse->success(null, 'Logged out successfully');
    }

    #[Route('/logout-all', name: 'api_logout_all', methods: ['POST'])]
    #[OA\Post(
        path: '/api/logout-all',
        summary: 'Logout from all devices',
        tags: ['Authentication'],
        security: [['JWT' => []]]
    )]
    public function logoutAll(#[CurrentUser] User $user): JsonResponse
    {
        $this->jwtService->revokeAllUserTokens($user);
        return $this->apiResponse->success(null, 'Logged out from all devices');
    }

    #[Route('/me', name: 'api_me', methods: ['GET'])]
    #[OA\Get(
        path: '/api/me',
        summary: 'Get current user profile',
        tags: ['Authentication'],
        security: [['JWT' => []]]
    )]
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
        
        // Validate request body
        $bodyErrors = $this->validationService->validateRequestBody($data);
        if (!empty($bodyErrors)) {
            return $this->apiResponse->error('Invalid request', $bodyErrors, 400);
        }
        
        // Validate required fields
        $requiredErrors = $this->validationService->validateRequiredFields($data, ['email', 'username', 'password', 'firstName']);
        if (!empty($requiredErrors)) {
            return $this->apiResponse->error('Missing required fields', $requiredErrors, 422);
        }
        
        // Sanitize inputs
        $email = $this->validationService->sanitizeString($data['email']);
        $username = $this->validationService->sanitizeString($data['username']);
        $firstName = $this->validationService->sanitizeString($data['firstName']);
        $password = $data['password']; // Don't sanitize password
        
        // Validate email format
        $emailErrors = $this->validationService->validateEmail($email);
        if (!empty($emailErrors)) {
            return $this->apiResponse->error('Validation failed', $emailErrors, 422);
        }
        
        // Validate username format
        $usernameErrors = $this->validationService->validateUsername($username);
        if (!empty($usernameErrors)) {
            return $this->apiResponse->error('Validation failed', $usernameErrors, 422);
        }
        
        // Validate password strength
        $passwordErrors = $this->validationService->validatePassword($password);
        if (!empty($passwordErrors)) {
            return $this->apiResponse->error('Validation failed', $passwordErrors, 422);
        }
        
        // Validate firstName length
        $firstNameErrors = $this->validationService->validateStringLength($firstName, 'First name', 1, 255);
        if (!empty($firstNameErrors)) {
            return $this->apiResponse->error('Validation failed', $firstNameErrors, 422);
        }
        
        // Check for duplicate email
        $existingUserByEmail = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
        if ($existingUserByEmail) {
            return $this->apiResponse->error('Validation failed', ['Email already exists'], 422);
        }
        
        // Check for duplicate username
        $existingUserByUsername = $this->entityManager->getRepository(User::class)->findOneBy(['username' => $username]);
        if ($existingUserByUsername) {
            return $this->apiResponse->error('Validation failed', ['Username already taken'], 422);
        }
        
        // Create user
        $user = new User();
        $user->setEmail($email);
        $user->setUsername($username);
        $user->setFirstName($firstName);
        $user->setPassword($this->passwordHasher->hashPassword($user, $password));
        
        // Final validation with Symfony validator
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