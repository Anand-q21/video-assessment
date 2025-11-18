<?php

namespace App\Service;

use App\Entity\Hashtag;
use App\Entity\Video;
use App\Repository\HashtagRepository;
use App\Repository\UserRepository;
use App\Repository\VideoRepository;
use Doctrine\ORM\EntityManagerInterface;

class SearchService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private VideoRepository $videoRepository,
        private UserRepository $userRepository,
        private HashtagRepository $hashtagRepository
    ) {}

    public function searchVideos(
        string $query,
        array $filters = [],
        int $limit = 20,
        int $offset = 0
    ): array {
        $qb = $this->entityManager->createQueryBuilder()
            ->select('v', 'u')
            ->from(Video::class, 'v')
            ->join('v.user', 'u')
            ->where('v.isPublic = true')
            ->andWhere('v.status = :status')
            ->andWhere('v.deletedAt IS NULL')
            ->setParameter('status', Video::STATUS_READY);

        // Full-text search
        if (!empty($query)) {
            $qb->andWhere('v.title LIKE :query OR v.description LIKE :query')
               ->setParameter('query', '%' . $query . '%');
        }

        // Duration filter
        if (isset($filters['duration'])) {
            switch ($filters['duration']) {
                case 'short':
                    $qb->andWhere('v.duration <= 60');
                    break;
                case 'medium':
                    $qb->andWhere('v.duration > 60 AND v.duration <= 300');
                    break;
                case 'long':
                    $qb->andWhere('v.duration > 300');
                    break;
            }
        }

        // Upload date filter
        if (isset($filters['uploadDate'])) {
            switch ($filters['uploadDate']) {
                case 'today':
                    $qb->andWhere('v.createdAt >= :today')
                       ->setParameter('today', new \DateTime('today'));
                    break;
                case 'week':
                    $qb->andWhere('v.createdAt >= :week')
                       ->setParameter('week', new \DateTime('-7 days'));
                    break;
                case 'month':
                    $qb->andWhere('v.createdAt >= :month')
                       ->setParameter('month', new \DateTime('-30 days'));
                    break;
            }
        }

        // Creator filter
        if (isset($filters['creator']) && !empty($filters['creator'])) {
            $qb->andWhere('u.username = :creator OR u.firstName LIKE :creatorName')
               ->setParameter('creator', $filters['creator'])
               ->setParameter('creatorName', '%' . $filters['creator'] . '%');
        }

        // Sort order
        $sortBy = $filters['sortBy'] ?? 'relevance';
        switch ($sortBy) {
            case 'newest':
                $qb->orderBy('v.createdAt', 'DESC');
                break;
            case 'oldest':
                $qb->orderBy('v.createdAt', 'ASC');
                break;
            case 'popular':
                $qb->orderBy('v.viewsCount', 'DESC');
                break;
            case 'liked':
                $qb->orderBy('v.likesCount', 'DESC');
                break;
            default: // relevance
                $qb->addSelect('(v.viewsCount * 0.3 + v.likesCount * 0.7) as HIDDEN relevance_score')
                   ->orderBy('relevance_score', 'DESC')
                   ->addOrderBy('v.createdAt', 'DESC');
        }

        return $qb->setMaxResults($limit)
                  ->setFirstResult($offset)
                  ->getQuery()
                  ->getResult();
    }

    public function searchByHashtag(string $hashtag, int $limit = 20, int $offset = 0): array
    {
        return $this->entityManager->createQueryBuilder()
            ->select('v', 'u')
            ->from(Video::class, 'v')
            ->join('v.user', 'u')
            ->join('v.hashtags', 'h')
            ->where('v.isPublic = true')
            ->andWhere('v.status = :status')
            ->andWhere('v.deletedAt IS NULL')
            ->andWhere('h.name = :hashtag')
            ->setParameter('status', Video::STATUS_READY)
            ->setParameter('hashtag', strtolower(trim($hashtag, '#')))
            ->orderBy('v.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    public function getSearchSuggestions(string $query, int $limit = 10): array
    {
        $suggestions = [];

        // Video title suggestions
        $videoSuggestions = $this->entityManager->createQueryBuilder()
            ->select('DISTINCT v.title')
            ->from(Video::class, 'v')
            ->where('v.isPublic = true')
            ->andWhere('v.status = :status')
            ->andWhere('v.deletedAt IS NULL')
            ->andWhere('v.title LIKE :query')
            ->setParameter('status', Video::STATUS_READY)
            ->setParameter('query', '%' . $query . '%')
            ->setMaxResults($limit)
            ->getQuery()
            ->getScalarResult();

        foreach ($videoSuggestions as $suggestion) {
            $suggestions[] = [
                'type' => 'video',
                'text' => $suggestion['title']
            ];
        }

        // Hashtag suggestions
        $hashtagSuggestions = $this->hashtagRepository->searchHashtags($query, $limit);
        foreach ($hashtagSuggestions as $hashtag) {
            $suggestions[] = [
                'type' => 'hashtag',
                'text' => '#' . $hashtag->getName()
            ];
        }

        // User suggestions
        $userSuggestions = $this->userRepository->searchUsers($query, $limit, 0);
        foreach ($userSuggestions as $user) {
            $suggestions[] = [
                'type' => 'user',
                'text' => $user->getUsername()
            ];
        }

        return array_slice($suggestions, 0, $limit);
    }

    public function extractHashtags(string $text): array
    {
        preg_match_all('/#([a-zA-Z0-9_]+)/', $text, $matches);
        return array_unique($matches[1]);
    }

    public function processVideoHashtags(Video $video, string $description): void
    {
        // Extract hashtags from description
        $hashtagNames = $this->extractHashtags($description);
        
        // Remove existing hashtags
        foreach ($video->getHashtags() as $hashtag) {
            $hashtag->decrementUsage();
            $video->removeHashtag($hashtag);
        }

        // Add new hashtags
        foreach ($hashtagNames as $hashtagName) {
            $hashtag = $this->hashtagRepository->findOrCreate($hashtagName);
            $hashtag->incrementUsage();
            $video->addHashtag($hashtag);
        }

        $this->entityManager->flush();
    }
}