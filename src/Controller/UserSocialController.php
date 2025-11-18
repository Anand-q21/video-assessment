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
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api/users')]
class UserSocialController extends AbstractController
{
    public function __construct(
        private ApiResponseService $apiResponse,
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository,
        private FollowRepository $followRepository
    ) {}

    #[Route('/{userId}/follow', name: 'api_follow_user', methods: ['POST'])]
    public function followUser(int $userId, #[CurrentUser] User $currentUser): JsonResponse
    {
        $userToFollow = $this->userRepository->find($userId);
        
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

    #[Route('/{userId}/follow', name: 'api_unfollow_user', methods: ['DELETE'])]
    public function unfollowUser(int $userId, #[CurrentUser] User $currentUser): JsonResponse
    {
        $userToUnfollow = $this->userRepository->find($userId);
        
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

    #[Route('/{userId}/stats', name: 'api_user_stats', methods: ['GET'])]
    public function getUserStats(int $userId): JsonResponse
    {
        $user = $this->userRepository->find($userId);
        
        if (!$user || !$user->isActive()) {
            return $this->apiResponse->error('User not found', null, 404);
        }

        $stats = [
            'followersCount' => $this->followRepository->getFollowersCount($user),
            'followingCount' => $this->followRepository->getFollowingCount($user),
            'videosCount' => $this->userRepository->getVideosCount($user),
            'totalViews' => $this->userRepository->getTotalViews($user),
            'totalLikes' => $this->userRepository->getTotalLikes($user)
        ];

        return $this->apiResponse->success($stats, 'User statistics retrieved');
    }

    #[Route('/search', name: 'api_search_users', methods: ['GET'])]
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

    #[Route('/profile', name: 'api_update_profile', methods: ['PUT'])]
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

        $user->setUpdatedAt(new \DateTime());
        $this->entityManager->flush();

        return $this->apiResponse->success([
            'id' => $user->getId(),
            'username' => $user->getUsername(),
            'firstName' => $user->getFirstName(),
            'bio' => $user->getBio()
        ], 'Profile updated successfully');
    }
}