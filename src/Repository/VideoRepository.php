<?php

namespace App\Repository;

use App\Entity\Video;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Video>
 */
class VideoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Video::class);
    }

    public function findPublicVideos(int $limit = 20, int $offset = 0): array
    {
        return $this->createQueryBuilder('v')
            ->where('v.isPublic = true')
            ->andWhere('v.status = :status')
            ->andWhere('v.deletedAt IS NULL')
            ->setParameter('status', Video::STATUS_READY)
            ->orderBy('v.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    public function countPublicVideos(): int
    {
        return $this->createQueryBuilder('v')
            ->select('COUNT(v.id)')
            ->where('v.isPublic = true')
            ->andWhere('v.status = :status')
            ->andWhere('v.deletedAt IS NULL')
            ->setParameter('status', Video::STATUS_READY)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findUserVideos(User $user, int $limit = 20, int $offset = 0): array
    {
        return $this->createQueryBuilder('v')
            ->where('v.user = :user')
            ->andWhere('v.deletedAt IS NULL')
            ->setParameter('user', $user)
            ->orderBy('v.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    public function countUserVideos(User $user): int
    {
        return $this->createQueryBuilder('v')
            ->select('COUNT(v.id)')
            ->where('v.user = :user')
            ->andWhere('v.deletedAt IS NULL')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function searchVideos(string $query, int $limit = 20, int $offset = 0): array
    {
        return $this->createQueryBuilder('v')
            ->where('v.isPublic = true')
            ->andWhere('v.status = :status')
            ->andWhere('v.deletedAt IS NULL')
            ->andWhere('v.title LIKE :query OR v.description LIKE :query')
            ->setParameter('status', Video::STATUS_READY)
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('v.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    public function findTrendingVideos(int $limit = 20): array
    {
        return $this->createQueryBuilder('v')
            ->where('v.isPublic = true')
            ->andWhere('v.status = :status')
            ->andWhere('v.deletedAt IS NULL')
            ->andWhere('v.createdAt >= :since')
            ->setParameter('status', Video::STATUS_READY)
            ->setParameter('since', new \DateTime('-7 days'))
            ->orderBy('v.viewsCount', 'DESC')
            ->addOrderBy('v.likesCount', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}