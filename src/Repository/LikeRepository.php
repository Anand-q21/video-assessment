<?php

namespace App\Repository;

use App\Entity\Like;
use App\Entity\User;
use App\Entity\Video;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Like>
 */
class LikeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Like::class);
    }

    public function isLikedByUser(Video $video, User $user): bool
    {
        return $this->createQueryBuilder('l')
            ->select('COUNT(l.id)')
            ->where('l.video = :video')
            ->andWhere('l.user = :user')
            ->setParameter('video', $video)
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult() > 0;
    }

    public function getLikesCount(Video $video): int
    {
        return $this->createQueryBuilder('l')
            ->select('COUNT(l.id)')
            ->where('l.video = :video')
            ->setParameter('video', $video)
            ->getQuery()
            ->getSingleScalarResult();
    }
}