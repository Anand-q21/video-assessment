<?php

namespace App\Controller;

use App\Entity\Like;
use App\Entity\User;
use App\Entity\Video;
use App\Repository\LikeRepository;
use App\Repository\VideoRepository;
use App\Service\ApiResponseService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use OpenApi\Attributes as OA;

#[Route('/api/videos')]
class VideoInteractionController extends AbstractController
{
    public function __construct(
        private ApiResponseService $apiResponse,
        private EntityManagerInterface $entityManager,
        private VideoRepository $videoRepository,
        private LikeRepository $likeRepository
    ) {}

    #[Route('/{id}/like', name: 'like_video', methods: ['POST'])]
    #[OA\Post(
        path: '/api/videos/{id}/like',
        summary: 'Like a video',
        tags: ['Video Interactions'],
        security: [['JWT' => []]]
    )]
    public function likeVideo(int $id, #[CurrentUser] User $user): JsonResponse
    {
        $video = $this->videoRepository->find($id);

        if (!$video || $video->isDeleted()) {
            return $this->apiResponse->error('Video not found', null, 404);
        }

        if ($this->likeRepository->isLikedByUser($video, $user)) {
            return $this->apiResponse->error('Video already liked', null, 400);
        }

        $like = new Like();
        $like->setUser($user);
        $like->setVideo($video);

        $this->entityManager->persist($like);

        // Update video likes count
        $video->setLikesCount($video->getLikesCount() + 1);
        
        $this->entityManager->flush();

        return $this->apiResponse->success([
            'likesCount' => $video->getLikesCount(),
            'isLiked' => true
        ], 'Video liked successfully');
    }

    #[Route('/{id}/like', name: 'unlike_video', methods: ['DELETE'])]
    #[OA\Delete(
        path: '/api/videos/{id}/like',
        summary: 'Unlike a video',
        tags: ['Video Interactions'],
        security: [['JWT' => []]]
    )]
    public function unlikeVideo(int $id, #[CurrentUser] User $user): JsonResponse
    {
        $video = $this->videoRepository->find($id);

        if (!$video || $video->isDeleted()) {
            return $this->apiResponse->error('Video not found', null, 404);
        }

        $like = $this->entityManager->getRepository(Like::class)->findOneBy([
            'user' => $user,
            'video' => $video
        ]);

        if (!$like) {
            return $this->apiResponse->error('Video not liked', null, 400);
        }

        $this->entityManager->remove($like);

        // Update video likes count
        $video->setLikesCount(max(0, $video->getLikesCount() - 1));
        
        $this->entityManager->flush();

        return $this->apiResponse->success([
            'likesCount' => $video->getLikesCount(),
            'isLiked' => false
        ], 'Video unliked successfully');
    }

    #[Route('/{id}/view', name: 'view_video', methods: ['POST'])]
    public function viewVideo(int $id): JsonResponse
    {
        $video = $this->videoRepository->find($id);

        if (!$video || $video->isDeleted()) {
            return $this->apiResponse->error('Video not found', null, 404);
        }

        $video->incrementViews();
        $this->entityManager->flush();

        return $this->apiResponse->success([
            'viewsCount' => $video->getViewsCount()
        ], 'View recorded');
    }
}