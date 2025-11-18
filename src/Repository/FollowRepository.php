<?php

namespace App\Repository;

use App\Entity\Follow;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Follow>
 */
class FollowRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Follow::class);
    }

    public function isFollowing(User $follower, User $following): bool
    {
        return $this->createQueryBuilder('f')
            ->select('COUNT(f.id)')
            ->where('f.follower = :follower')
            ->andWhere('f.following = :following')
            ->setParameter('follower', $follower)
            ->setParameter('following', $following)
            ->getQuery()
            ->getSingleScalarResult() > 0;
    }

    public function getFollowersCount(User $user): int
    {
        return $this->createQueryBuilder('f')
            ->select('COUNT(f.id)')
            ->where('f.following = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getFollowingCount(User $user): int
    {
        return $this->createQueryBuilder('f')
            ->select('COUNT(f.id)')
            ->where('f.follower = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getFollowers(User $user, int $limit = 20, int $offset = 0): array
    {
        return $this->createQueryBuilder('f')
            ->select('f', 'follower')
            ->join('f.follower', 'follower')
            ->where('f.following = :user')
            ->setParameter('user', $user)
            ->orderBy('f.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    public function getFollowing(User $user, int $limit = 20, int $offset = 0): array
    {
        return $this->createQueryBuilder('f')
            ->select('f', 'following')
            ->join('f.following', 'following')
            ->where('f.follower = :user')
            ->setParameter('user', $user)
            ->orderBy('f.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }
}