<?php

namespace App\Controller;

use App\Entity\Follow;
use App\Entity\User;
use App\Repository\FollowRepository;
use App\Repository\UserRepository;
use App\Service\ApiResponseService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use OpenApi\Attributes as OA;

#[Route('/api/profile')]
class UserProfileController extends AbstractController
{
    public function __construct(
        private ApiResponseService $apiResponse,
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository,
        private FollowRepository $followRepository,
        private ValidatorInterface $validator,
        private UserPasswordHasherInterface $passwordHasher
    ) {}

    #[Route('/{id}', name: 'get_user_profile', methods: ['GET'])]
    public function getUserProfile(int $id, #[CurrentUser] ?User $currentUser): JsonResponse
    {
        $user = $this->userRepository->find($id);
        
        if (!$user || !$user->isActive()) {
            return $this->apiResponse->error('User not found', null, 404);
        }

        $isFollowing = false;
        if ($currentUser) {
            $isFollowing = $this->followRepository->isFollowing($currentUser, $user);
        }

        $profileData = [
            'id' => $user->getId(),
            'username' => $user->getUsername(),
            'firstName' => $user->getFirstName(),
            'bio' => $user->getBio(),
            'profilePicture' => $user->getProfilePicture(),
            'followersCount' => $this->followRepository->getFollowersCount($user),
            'followingCount' => $this->followRepository->getFollowingCount($user),
            'isFollowing' => $isFollowing,
            'createdAt' => $user->getCreatedAt()->format('Y-m-d H:i:s')
        ];

        return $this->apiResponse->success($profileData, 'Profile retrieved');
    }

    #[Route('', name: 'update_profile', methods: ['PUT'])]
    #[OA\Put(
        path: '/api/profile',
        summary: 'Update user profile',
        tags: ['User Profile'],
        security: [['JWT' => []]]
    )]
    public function updateProfile(Request $request, #[CurrentUser] User $user): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (isset($data['username'])) {
            $user->setUsername($data['username']);
        }
        if (isset($data['firstName'])) {
            $user->setFirstName($data['firstName']);
        }
        if (isset($data['bio'])) {
            $user->setBio($data['bio']);
        }
        if (isset($data['profilePicture'])) {
            $user->setProfilePicture($data['profilePicture']);
        }

        $user->setUpdatedAt(new \DateTime());

        $errors = $this->validator->validate($user);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return $this->apiResponse->error('Validation failed', $errorMessages, 422);
        }

        $this->entityManager->flush();

        return $this->apiResponse->success([
            'id' => $user->getId(),
            'username' => $user->getUsername(),
            'firstName' => $user->getFirstName(),
            'bio' => $user->getBio(),
            'profilePicture' => $user->getProfilePicture()
        ], 'Profile updated successfully');
    }

    #[Route('/{id}/follow', name: 'follow_user', methods: ['POST'])]
    #[OA\Post(
        path: '/api/profile/{id}/follow',
        summary: 'Follow user',
        tags: ['User Profile'],
        security: [['JWT' => []]]
    )]
    public function followUser(int $id, #[CurrentUser] User $currentUser): JsonResponse
    {
        $userToFollow = $this->userRepository->find($id);
        
        if (!$userToFollow || !$userToFollow->isActive()) {
            return $this->apiResponse->error('User not found', null, 404);
        }

        if ($currentUser->getId() === $userToFollow->getId()) {
            return $this->apiResponse->error('Cannot follow yourself', null, 400);
        }

        if ($this->followRepository->isFollowing($currentUser, $userToFollow)) {
            return $this->apiResponse->error('Already following this user', null, 400);
        }

        $follow = new Follow();
        $follow->setFollower($currentUser);
        $follow->setFollowing($userToFollow);

        $this->entityManager->persist($follow);
        $this->entityManager->flush();

        return $this->apiResponse->success([
            'followersCount' => $this->followRepository->getFollowersCount($userToFollow),
            'isFollowing' => true
        ], 'User followed successfully');
    }

    #[Route('/{id}/unfollow', name: 'unfollow_user', methods: ['DELETE'])]
    #[OA\Delete(
        path: '/api/profile/{id}/unfollow',
        summary: 'Unfollow user',
        tags: ['User Profile'],
        security: [['JWT' => []]]
    )]
    public function unfollowUser(int $id, #[CurrentUser] User $currentUser): JsonResponse
    {
        $userToUnfollow = $this->userRepository->find($id);
        
        if (!$userToUnfollow) {
            return $this->apiResponse->error('User not found', null, 404);
        }

        $follow = $this->entityManager->getRepository(Follow::class)->findOneBy([
            'follower' => $currentUser,
            'following' => $userToUnfollow
        ]);

        if (!$follow) {
            return $this->apiResponse->error('Not following this user', null, 400);
        }

        $this->entityManager->remove($follow);
        $this->entityManager->flush();

        return $this->apiResponse->success([
            'followersCount' => $this->followRepository->getFollowersCount($userToUnfollow),
            'isFollowing' => false
        ], 'User unfollowed successfully');
    }

    #[Route('/{id}/followers', name: 'get_followers', methods: ['GET'])]
    public function getFollowers(int $id, Request $request): JsonResponse
    {
        $user = $this->userRepository->find($id);
        
        if (!$user || !$user->isActive()) {
            return $this->apiResponse->error('User not found', null, 404);
        }

        $page = max(1, (int) $request->query->get('page', 1));
        $limit = min(50, max(1, (int) $request->query->get('limit', 20)));
        $offset = ($page - 1) * $limit;

        $follows = $this->followRepository->getFollowers($user, $limit, $offset);
        $total = $this->followRepository->getFollowersCount($user);

        $followers = array_map(function($follow) {
            $follower = $follow->getFollower();
            return [
                'id' => $follower->getId(),
                'username' => $follower->getUsername(),
                'firstName' => $follower->getFirstName(),
                'profilePicture' => $follower->getProfilePicture(),
                'followedAt' => $follow->getCreatedAt()->format('Y-m-d H:i:s')
            ];
        }, $follows);

        $pagination = $this->apiResponse->paginate($followers, $page, $limit, $total);

        return $this->apiResponse->success($followers, 'Followers retrieved', $pagination);
    }

    #[Route('/{id}/following', name: 'get_following', methods: ['GET'])]
    public function getFollowing(int $id, Request $request): JsonResponse
    {
        $user = $this->userRepository->find($id);
        
        if (!$user || !$user->isActive()) {
            return $this->apiResponse->error('User not found', null, 404);
        }

        $page = max(1, (int) $request->query->get('page', 1));
        $limit = min(50, max(1, (int) $request->query->get('limit', 20)));
        $offset = ($page - 1) * $limit;

        $follows = $this->followRepository->getFollowing($user, $limit, $offset);
        $total = $this->followRepository->getFollowingCount($user);

        $following = array_map(function($follow) {
            $followingUser = $follow->getFollowing();
            return [
                'id' => $followingUser->getId(),
                'username' => $followingUser->getUsername(),
                'firstName' => $followingUser->getFirstName(),
                'profilePicture' => $followingUser->getProfilePicture(),
                'followedAt' => $follow->getCreatedAt()->format('Y-m-d H:i:s')
            ];
        }, $follows);

        $pagination = $this->apiResponse->paginate($following, $page, $limit, $total);

        return $this->apiResponse->success($following, 'Following retrieved', $pagination);
    }

    #[Route('/search', name: 'search_users', methods: ['GET'])]
    public function searchUsers(Request $request): JsonResponse
    {
        $query = $request->query->get('q', '');
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = min(50, max(1, (int) $request->query->get('limit', 20)));

        if (strlen($query) < 2) {
            return $this->apiResponse->error('Search query must be at least 2 characters', null, 400);
        }

        $users = $this->userRepository->searchUsers($query, $limit, ($page - 1) * $limit);
        $total = $this->userRepository->countSearchUsers($query);

        $results = array_map(function($user) {
            return [
                'id' => $user->getId(),
                'username' => $user->getUsername(),
                'firstName' => $user->getFirstName(),
                'profilePicture' => $user->getProfilePicture(),
                'followersCount' => $this->followRepository->getFollowersCount($user)
            ];
        }, $users);

        $pagination = $this->apiResponse->paginate($results, $page, $limit, $total);

        return $this->apiResponse->success($results, 'Users found', $pagination);
    }
}