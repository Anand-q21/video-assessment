<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\ApiResponseService;
use App\Service\FeedService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use OpenApi\Attributes as OA;

#[Route('/api/feed')]
class FeedController extends AbstractController
{
    public function __construct(
        private ApiResponseService $apiResponse,
        private FeedService $feedService,
        private UserRepository $userRepository
    ) {}

    #[Route('/vertical', name: 'vertical_feed', methods: ['GET'])]
    public function getVerticalFeed(Request $request, #[CurrentUser] ?User $user): JsonResponse
    {
        $cursor = $request->query->get('cursor');
        $limit = min(50, max(1, (int) $request->query->get('limit', 20)));

        $feedData = $this->feedService->getVerticalFeed($user, $cursor, $limit);
        $videos = array_map([$this, 'formatVideoForFeed'], $feedData['videos']);

        return $this->apiResponse->success([
            'videos' => $videos,
            'nextCursor' => $feedData['nextCursor'],
            'hasMore' => $feedData['hasMore']
        ], 'Vertical feed retrieved');
    }

    #[Route('/home', name: 'home_feed', methods: ['GET'])]
    #[OA\Get(
        path: '/api/feed/home',
        summary: 'Get home feed',
        tags: ['Feed'],
        security: [['JWT' => []]]
    )]
    public function getHomeFeed(Request $request, #[CurrentUser] User $user): JsonResponse
    {
        $cursor = $request->query->get('cursor');
        $limit = min(50, max(1, (int) $request->query->get('limit', 20)));

        $feedData = $this->feedService->getHomeFeed($user, $cursor, $limit);
        $videos = array_map([$this, 'formatVideoForFeed'], $feedData['videos']);

        return $this->apiResponse->success([
            'videos' => $videos,
            'nextCursor' => $feedData['nextCursor'],
            'hasMore' => $feedData['hasMore']
        ], 'Home feed retrieved');
    }

    #[Route('/trending', name: 'trending_feed', methods: ['GET'])]
    public function getTrendingFeed(Request $request): JsonResponse
    {
        $cursor = $request->query->get('cursor');
        $limit = min(50, max(1, (int) $request->query->get('limit', 20)));

        $feedData = $this->feedService->getTrendingFeed($cursor, $limit);
        $videos = array_map([$this, 'formatVideoForFeed'], $feedData['videos']);

        return $this->apiResponse->success([
            'videos' => $videos,
            'nextCursor' => $feedData['nextCursor'],
            'hasMore' => $feedData['hasMore']
        ], 'Trending feed retrieved');
    }

    #[Route('/chronological', name: 'chronological_feed', methods: ['GET'])]
    public function getChronologicalFeed(Request $request, #[CurrentUser] ?User $user): JsonResponse
    {
        $cursor = $request->query->get('cursor');
        $limit = min(50, max(1, (int) $request->query->get('limit', 20)));

        $feedData = $this->feedService->getChronologicalFeed($user, $cursor, $limit);
        $videos = array_map([$this, 'formatVideoForFeed'], $feedData['videos']);

        return $this->apiResponse->success([
            'videos' => $videos,
            'nextCursor' => $feedData['nextCursor'],
            'hasMore' => $feedData['hasMore']
        ], 'Chronological feed retrieved');
    }

    #[Route('/creator/{userId}', name: 'creator_channel', methods: ['GET'])]
    public function getCreatorChannel(int $userId, Request $request): JsonResponse
    {
        $creator = $this->userRepository->find($userId);
        
        if (!$creator || !$creator->isActive()) {
            return $this->apiResponse->error('Creator not found', null, 404);
        }

        $cursor = $request->query->get('cursor');
        $limit = min(50, max(1, (int) $request->query->get('limit', 20)));

        $feedData = $this->feedService->getCreatorChannel($creator, $cursor, $limit);
        $videos = array_map([$this, 'formatVideoForFeed'], $feedData['videos']);

        return $this->apiResponse->success([
            'creator' => [
                'id' => $creator->getId(),
                'username' => $creator->getUsername(),
                'firstName' => $creator->getFirstName(),
                'bio' => $creator->getBio(),
                'profilePicture' => $creator->getProfilePicture()
            ],
            'videos' => $videos,
            'nextCursor' => $feedData['nextCursor'],
            'hasMore' => $feedData['hasMore']
        ], 'Creator channel retrieved');
    }

    #[Route('/discover', name: 'discover_feed', methods: ['GET'])]
    public function getDiscoverFeed(Request $request, #[CurrentUser] ?User $user): JsonResponse
    {
        $cursor = $request->query->get('cursor');
        $limit = min(50, max(1, (int) $request->query->get('limit', 20)));
        $category = $request->query->get('category', 'all');

        // For discover, we'll use a mix of trending and random popular videos
        $feedData = $this->feedService->getVerticalFeed($user, $cursor, $limit);
        $videos = array_map([$this, 'formatVideoForFeed'], $feedData['videos']);

        return $this->apiResponse->success([
            'videos' => $videos,
            'nextCursor' => $feedData['nextCursor'],
            'hasMore' => $feedData['hasMore'],
            'category' => $category
        ], 'Discover feed retrieved');
    }

    #[Route('/popular', name: 'popular_feed', methods: ['GET'])]
    public function getPopularFeed(Request $request): JsonResponse
    {
        $cursor = $request->query->get('cursor');
        $limit = min(50, max(1, (int) $request->query->get('limit', 20)));
        $timeframe = $request->query->get('timeframe', 'week'); // day, week, month, all

        // Use trending feed as popular feed
        $feedData = $this->feedService->getTrendingFeed($cursor, $limit);
        $videos = array_map([$this, 'formatVideoForFeed'], $feedData['videos']);

        return $this->apiResponse->success([
            'videos' => $videos,
            'nextCursor' => $feedData['nextCursor'],
            'hasMore' => $feedData['hasMore'],
            'timeframe' => $timeframe
        ], 'Popular feed retrieved');
    }

    private function formatVideoForFeed($video): array
    {
        return [
            'id' => $video->getId(),
            'title' => $video->getTitle(),
            'description' => $video->getDescription(),
            'thumbnailPath' => $video->getThumbnailPath(),
            'duration' => $video->getDuration(),
            'viewsCount' => $video->getViewsCount(),
            'likesCount' => $video->getLikesCount(),
            'createdAt' => $video->getCreatedAt()->format('Y-m-d H:i:s'),
            'user' => [
                'id' => $video->getUser()->getId(),
                'username' => $video->getUser()->getUsername(),
                'firstName' => $video->getUser()->getFirstName(),
                'profilePicture' => $video->getUser()->getProfilePicture()
            ]
        ];
    }
}