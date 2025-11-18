<?php

namespace App\Repository;

use App\Entity\Hashtag;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Hashtag>
 */
class HashtagRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Hashtag::class);
    }

    public function findOrCreate(string $name): Hashtag
    {
        $hashtag = $this->findOneBy(['name' => strtolower(trim($name, '#'))]);
        
        if (!$hashtag) {
            $hashtag = new Hashtag();
            $hashtag->setName($name);
            $this->getEntityManager()->persist($hashtag);
        }
        
        return $hashtag;
    }

    public function getTrendingHashtags(int $limit = 20): array
    {
        return $this->createQueryBuilder('h')
            ->where('h.lastUsedAt >= :since')
            ->setParameter('since', new \DateTime('-7 days'))
            ->orderBy('h.usageCount', 'DESC')
            ->addOrderBy('h.lastUsedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function searchHashtags(string $query, int $limit = 20): array
    {
        return $this->createQueryBuilder('h')
            ->where('h.name LIKE :query')
            ->setParameter('query', '%' . strtolower($query) . '%')
            ->orderBy('h.usageCount', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function getPopularHashtags(int $limit = 50): array
    {
        return $this->createQueryBuilder('h')
            ->orderBy('h.usageCount', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}