<?php

namespace App\Controller;

use App\Repository\HashtagRepository;
use App\Service\ApiResponseService;
use App\Service\SearchService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/search')]
class SearchController extends AbstractController
{
    public function __construct(
        private ApiResponseService $apiResponse,
        private SearchService $searchService,
        private HashtagRepository $hashtagRepository
    ) {}

    #[Route('/videos', name: 'search_videos', methods: ['GET'])]
    public function searchVideos(Request $request): JsonResponse
    {
        $query = $request->query->get('q', '');
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = min(50, max(1, (int) $request->query->get('limit', 20)));

        if (strlen($query) < 2) {
            return $this->apiResponse->error('Search query must be at least 2 characters', null, 400);
        }

        $filters = [
            'duration' => $request->query->get('duration'),
            'uploadDate' => $request->query->get('uploadDate'),
            'creator' => $request->query->get('creator'),
            'sortBy' => $request->query->get('sortBy', 'relevance')
        ];

        $offset = ($page - 1) * $limit;
        $videos = $this->searchService->searchVideos($query, $filters, $limit, $offset);

        $videoData = array_map([$this, 'formatVideoData'], $videos);
        $pagination = $this->apiResponse->paginate($videoData, $page, $limit, count($videos));

        return $this->apiResponse->success($videoData, 'Search results', $pagination);
    }

    #[Route('/hashtags/{hashtag}', name: 'search_by_hashtag', methods: ['GET'])]
    public function searchByHashtag(string $hashtag, Request $request): JsonResponse
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = min(50, max(1, (int) $request->query->get('limit', 20)));

        $offset = ($page - 1) * $limit;
        $videos = $this->searchService->searchByHashtag($hashtag, $limit, $offset);

        $videoData = array_map([$this, 'formatVideoData'], $videos);
        $pagination = $this->apiResponse->paginate($videoData, $page, $limit, count($videos));

        return $this->apiResponse->success([
            'hashtag' => '#' . trim($hashtag, '#'),
            'videos' => $videoData
        ], 'Hashtag videos retrieved', $pagination);
    }

    #[Route('/suggestions', name: 'search_suggestions', methods: ['GET'])]
    public function getSearchSuggestions(Request $request): JsonResponse
    {
        $query = $request->query->get('q', '');
        $limit = min(20, max(1, (int) $request->query->get('limit', 10)));

        if (strlen($query) < 2) {
            return $this->apiResponse->success([], 'No suggestions');
        }

        $suggestions = $this->searchService->getSearchSuggestions($query, $limit);

        return $this->apiResponse->success($suggestions, 'Search suggestions');
    }

    #[Route('/hashtags/trending', name: 'trending_hashtags', methods: ['GET'])]
    public function getTrendingHashtags(Request $request): JsonResponse
    {
        $limit = min(50, max(1, (int) $request->query->get('limit', 20)));
        $hashtags = $this->hashtagRepository->getTrendingHashtags($limit);

        $hashtagData = array_map(function($hashtag) {
            return [
                'id' => $hashtag->getId(),
                'name' => '#' . $hashtag->getName(),
                'usageCount' => $hashtag->getUsageCount(),
                'lastUsedAt' => $hashtag->getLastUsedAt()?->format('Y-m-d H:i:s')
            ];
        }, $hashtags);

        return $this->apiResponse->success($hashtagData, 'Trending hashtags retrieved');
    }

    #[Route('/hashtags/popular', name: 'popular_hashtags', methods: ['GET'])]
    public function getPopularHashtags(Request $request): JsonResponse
    {
        $limit = min(100, max(1, (int) $request->query->get('limit', 50)));
        $hashtags = $this->hashtagRepository->getPopularHashtags($limit);

        $hashtagData = array_map(function($hashtag) {
            return [
                'id' => $hashtag->getId(),
                'name' => '#' . $hashtag->getName(),
                'usageCount' => $hashtag->getUsageCount()
            ];
        }, $hashtags);

        return $this->apiResponse->success($hashtagData, 'Popular hashtags retrieved');
    }

    #[Route('/hashtags', name: 'search_hashtags', methods: ['GET'])]
    public function searchHashtags(Request $request): JsonResponse
    {
        $query = $request->query->get('q', '');
        $limit = min(50, max(1, (int) $request->query->get('limit', 20)));

        if (strlen($query) < 1) {
            return $this->apiResponse->error('Search query required', null, 400);
        }

        $hashtags = $this->hashtagRepository->searchHashtags($query, $limit);

        $hashtagData = array_map(function($hashtag) {
            return [
                'id' => $hashtag->getId(),
                'name' => '#' . $hashtag->getName(),
                'usageCount' => $hashtag->getUsageCount()
            ];
        }, $hashtags);

        return $this->apiResponse->success($hashtagData, 'Hashtags found');
    }

    #[Route('/categories', name: 'browse_categories', methods: ['GET'])]
    public function browseCategories(): JsonResponse
    {
        // Predefined categories based on popular hashtags
        $categories = [
            ['name' => 'Entertainment', 'hashtag' => 'entertainment', 'icon' => '🎭'],
            ['name' => 'Music', 'hashtag' => 'music', 'icon' => '🎵'],
            ['name' => 'Comedy', 'hashtag' => 'comedy', 'icon' => '😂'],
            ['name' => 'Dance', 'hashtag' => 'dance', 'icon' => '💃'],
            ['name' => 'Food', 'hashtag' => 'food', 'icon' => '🍕'],
            ['name' => 'Travel', 'hashtag' => 'travel', 'icon' => '✈️'],
            ['name' => 'Sports', 'hashtag' => 'sports', 'icon' => '⚽'],
            ['name' => 'Education', 'hashtag' => 'education', 'icon' => '📚'],
            ['name' => 'Technology', 'hashtag' => 'tech', 'icon' => '💻'],
            ['name' => 'Fashion', 'hashtag' => 'fashion', 'icon' => '👗'],
            ['name' => 'Gaming', 'hashtag' => 'gaming', 'icon' => '🎮'],
            ['name' => 'Art', 'hashtag' => 'art', 'icon' => '🎨']
        ];

        return $this->apiResponse->success($categories, 'Categories retrieved');
    }

    private function formatVideoData($video): array
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
            'hashtags' => array_map(fn($h) => '#' . $h->getName(), $video->getHashtags()->toArray()),
            'user' => [
                'id' => $video->getUser()->getId(),
                'username' => $video->getUser()->getUsername(),
                'firstName' => $video->getUser()->getFirstName(),
                'profilePicture' => $video->getUser()->getProfilePicture()
            ]
        ];
    }
}