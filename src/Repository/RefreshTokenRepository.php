<?php

namespace App\Repository;

use App\Entity\RefreshToken;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<RefreshToken>
 */
class RefreshTokenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RefreshToken::class);
    }

    public function findValidToken(string $token): ?RefreshToken
    {
        return $this->createQueryBuilder('rt')
            ->where('rt.token = :token')
            ->andWhere('rt.expiresAt > :now')
            ->andWhere('rt.isRevoked = false')
            ->setParameter('token', $token)
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function revokeAllUserTokens(User $user): void
    {
        $this->createQueryBuilder('rt')
            ->update()
            ->set('rt.isRevoked', true)
            ->where('rt.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->execute();
    }

    public function deleteExpiredTokens(): int
    {
        return $this->createQueryBuilder('rt')
            ->delete()
            ->where('rt.expiresAt < :now')
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->execute();
    }
}