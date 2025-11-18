<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    public function searchUsers(string $query, int $limit = 20, int $offset = 0): array
    {
        return $this->createQueryBuilder('u')
            ->where('u.isActive = true')
            ->andWhere('u.username LIKE :query OR u.firstName LIKE :query OR u.email LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('u.username', 'ASC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    public function countSearchUsers(string $query): int
    {
        return $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('u.isActive = true')
            ->andWhere('u.username LIKE :query OR u.firstName LIKE :query OR u.email LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getVideosCount(User $user): int
    {
        return $this->getEntityManager()
            ->createQuery('SELECT COUNT(v.id) FROM App\Entity\Video v WHERE v.user = :user AND v.deletedAt IS NULL')
            ->setParameter('user', $user)
            ->getSingleScalarResult();
    }

    public function getTotalViews(User $user): int
    {
        $result = $this->getEntityManager()
            ->createQuery('SELECT SUM(v.viewsCount) FROM App\Entity\Video v WHERE v.user = :user AND v.deletedAt IS NULL')
            ->setParameter('user', $user)
            ->getSingleScalarResult();
        
        return $result ?: 0;
    }

    public function getTotalLikes(User $user): int
    {
        $result = $this->getEntityManager()
            ->createQuery('SELECT SUM(v.likesCount) FROM App\Entity\Video v WHERE v.user = :user AND v.deletedAt IS NULL')
            ->setParameter('user', $user)
            ->getSingleScalarResult();
        
        return $result ?: 0;
    }

    //    /**
    //     * @return User[] Returns an array of User objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('u')
    //            ->andWhere('u.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('u.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?User
    //    {
    //        return $this->createQueryBuilder('u')
    //            ->andWhere('u.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
