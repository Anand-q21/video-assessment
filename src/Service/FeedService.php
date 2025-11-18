<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\Video;
use App\Repository\FollowRepository;
use App\Repository\VideoRepository;
use App\Service\CacheService;
use Doctrine\ORM\EntityManagerInterface;

class FeedService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private VideoRepository $videoRepository,
        private FollowRepository $followRepository,
        private CacheService $cacheService
    ) {}

    public function getVerticalFeed(?User $user = null, ?string $cursor = null, int $limit = 20): array
    {
        $cacheKey = 'vertical_feed_' . ($user ? $user->getId() : 'guest') . '_' . ($cursor ?? '0') . '_' . $limit;
        
        return $this->cacheService->getFeedCache($cacheKey, function() use ($user, $cursor, $limit) {
            $qb = $this->entityManager->createQueryBuilder()
                ->select('v', 'u')
                ->from(Video::class, 'v')
                ->join('v.user', 'u')
                ->where('v.isPublic = true')
                ->andWhere('v.status = :status')
                ->andWhere('v.deletedAt IS NULL')
                ->setParameter('status', Video::STATUS_READY);

            // Cursor-based pagination
            if ($cursor) {
                $qb->andWhere('v.id < :cursor')
                   ->setParameter('cursor', $cursor);
            }

            // Optimized ordering for performance
            $qb->addSelect('(v.viewsCount * 0.3 + v.likesCount * 0.7) as HIDDEN score')
               ->orderBy('score', 'DESC')
               ->addOrderBy('v.id', 'DESC') // Use ID instead of createdAt for better performance
               ->setMaxResults($limit + 1);

            // Enable query result cache
            $query = $qb->getQuery();
            $query->enableResultCache(300); // 5 minutes
            
            $videos = $query->getResult();
            
            $hasMore = count($videos) > $limit;
            if ($hasMore) {
                array_pop($videos);
            }

            $nextCursor = $hasMore && !empty($videos) ? end($videos)->getId() : null;

            return [
                'videos' => $videos,
                'nextCursor' => $nextCursor,
                'hasMore' => $hasMore
            ];
        }, 300);
    }

    public function getHomeFeed(User $user, ?string $cursor = null, int $limit = 20): array
    {
        // Get users that current user follows
        $following = $this->followRepository->getFollowing($user, 1000, 0);
        $followingIds = array_map(fn($follow) => $follow->getFollowing()->getId(), $following);

        if (empty($followingIds)) {
            // If not following anyone, return popular videos
            return $this->getVerticalFeed($user, $cursor, $limit);
        }

        $qb = $this->entityManager->createQueryBuilder()
            ->select('v', 'u')
            ->from(Video::class, 'v')
            ->join('v.user', 'u')
            ->where('v.isPublic = true')
            ->andWhere('v.status = :status')
            ->andWhere('v.deletedAt IS NULL')
            ->andWhere('v.user IN (:followingIds)')
            ->setParameter('status', Video::STATUS_READY)
            ->setParameter('followingIds', $followingIds);

        if ($cursor) {
            $qb->andWhere('v.id < :cursor')
               ->setParameter('cursor', $cursor);
        }

        $qb->orderBy('v.createdAt', 'DESC')
           ->setMaxResults($limit + 1);

        $videos = $qb->getQuery()->getResult();
        
        $hasMore = count($videos) > $limit;
        if ($hasMore) {
            array_pop($videos);
        }

        $nextCursor = $hasMore && !empty($videos) ? end($videos)->getId() : null;

        return [
            'videos' => $videos,
            'nextCursor' => $nextCursor,
            'hasMore' => $hasMore
        ];
    }

    public function getTrendingFeed(?string $cursor = null, int $limit = 20): array
    {
        $qb = $this->entityManager->createQueryBuilder()
            ->select('v', 'u')
            ->from(Video::class, 'v')
            ->join('v.user', 'u')
            ->where('v.isPublic = true')
            ->andWhere('v.status = :status')
            ->andWhere('v.deletedAt IS NULL')
            ->andWhere('v.createdAt >= :since')
            ->setParameter('status', Video::STATUS_READY)
            ->setParameter('since', new \DateTime('-7 days'));

        if ($cursor) {
            $qb->andWhere('v.id < :cursor')
               ->setParameter('cursor', $cursor);
        }

        // Trending algorithm: weighted by views and likes in last 7 days
        $qb->addSelect('(v.viewsCount * 0.4 + v.likesCount * 0.6) as HIDDEN trending_score')
           ->orderBy('trending_score', 'DESC')
           ->addOrderBy('v.createdAt', 'DESC')
           ->setMaxResults($limit + 1);

        $videos = $qb->getQuery()->getResult();
        
        $hasMore = count($videos) > $limit;
        if ($hasMore) {
            array_pop($videos);
        }

        $nextCursor = $hasMore && !empty($videos) ? end($videos)->getId() : null;

        return [
            'videos' => $videos,
            'nextCursor' => $nextCursor,
            'hasMore' => $hasMore
        ];
    }

    public function getCreatorChannel(User $creator, ?string $cursor = null, int $limit = 20): array
    {
        $qb = $this->entityManager->createQueryBuilder()
            ->select('v')
            ->from(Video::class, 'v')
            ->where('v.user = :creator')
            ->andWhere('v.isPublic = true')
            ->andWhere('v.status = :status')
            ->andWhere('v.deletedAt IS NULL')
            ->setParameter('creator', $creator)
            ->setParameter('status', Video::STATUS_READY);

        if ($cursor) {
            $qb->andWhere('v.id < :cursor')
               ->setParameter('cursor', $cursor);
        }

        $qb->orderBy('v.createdAt', 'DESC')
           ->setMaxResults($limit + 1);

        $videos = $qb->getQuery()->getResult();
        
        $hasMore = count($videos) > $limit;
        if ($hasMore) {
            array_pop($videos);
        }

        $nextCursor = $hasMore && !empty($videos) ? end($videos)->getId() : null;

        return [
            'videos' => $videos,
            'nextCursor' => $nextCursor,
            'hasMore' => $hasMore
        ];
    }

    public function getChronologicalFeed(?User $user = null, ?string $cursor = null, int $limit = 20): array
    {
        $qb = $this->entityManager->createQueryBuilder()
            ->select('v', 'u')
            ->from(Video::class, 'v')
            ->join('v.user', 'u')
            ->where('v.isPublic = true')
            ->andWhere('v.status = :status')
            ->andWhere('v.deletedAt IS NULL')
            ->setParameter('status', Video::STATUS_READY);

        if ($cursor) {
            $qb->andWhere('v.id < :cursor')
               ->setParameter('cursor', $cursor);
        }

        $qb->orderBy('v.createdAt', 'DESC')
           ->setMaxResults($limit + 1);

        $videos = $qb->getQuery()->getResult();
        
        $hasMore = count($videos) > $limit;
        if ($hasMore) {
            array_pop($videos);
        }

        $nextCursor = $hasMore && !empty($videos) ? end($videos)->getId() : null;

        return [
            'videos' => $videos,
            'nextCursor' => $nextCursor,
            'hasMore' => $hasMore
        ];
    }
}