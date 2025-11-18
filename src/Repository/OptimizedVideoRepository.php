<?php

namespace App\Repository;

use App\Entity\Video;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Optimized video repository for better performance
 */
class OptimizedVideoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Video::class);
    }

    public function findPublicVideosOptimized(int $limit = 20, int $offset = 0): Query
    {
        return $this->createQueryBuilder('v')
            ->select('v.id, v.title, v.description, v.thumbnailPath, v.duration, v.viewsCount, v.likesCount, v.createdAt')
            ->addSelect('u.id as user_id, u.username, u.firstName, u.profilePicture')
            ->join('v.user', 'u')
            ->where('v.isPublic = true')
            ->andWhere('v.status = :status')
            ->andWhere('v.deletedAt IS NULL')
            ->setParameter('status', Video::STATUS_READY)
            ->orderBy('v.id', 'DESC') // Use ID for better performance than createdAt
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->enableResultCache(300); // 5 minutes cache
    }

    public function findTrendingVideosOptimized(int $limit = 20): Query
    {
        return $this->createQueryBuilder('v')
            ->select('v.id, v.title, v.description, v.thumbnailPath, v.duration, v.viewsCount, v.likesCount, v.createdAt')
            ->addSelect('u.id as user_id, u.username, u.firstName, u.profilePicture')
            ->addSelect('(v.viewsCount * 0.4 + v.likesCount * 0.6) as HIDDEN trending_score')
            ->join('v.user', 'u')
            ->where('v.isPublic = true')
            ->andWhere('v.status = :status')
            ->andWhere('v.deletedAt IS NULL')
            ->andWhere('v.createdAt >= :since')
            ->setParameter('status', Video::STATUS_READY)
            ->setParameter('since', new \DateTime('-7 days'))
            ->orderBy('trending_score', 'DESC')
            ->addOrderBy('v.id', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->enableResultCache(600); // 10 minutes cache for trending
    }

    public function getVideoCountByUser(User $user): int
    {
        return $this->createQueryBuilder('v')
            ->select('COUNT(v.id)')
            ->where('v.user = :user')
            ->andWhere('v.deletedAt IS NULL')
            ->setParameter('user', $user)
            ->getQuery()
            ->enableResultCache(1800) // 30 minutes cache
            ->getSingleScalarResult();
    }

    public function bulkUpdateViewCounts(array $videoIds): void
    {
        if (empty($videoIds)) {
            return;
        }

        $this->createQueryBuilder('v')
            ->update()
            ->set('v.viewsCount', 'v.viewsCount + 1')
            ->where('v.id IN (:ids)')
            ->setParameter('ids', $videoIds)
            ->getQuery()
            ->execute();
    }
}