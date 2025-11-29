<?php

namespace App\Controller;

use App\Entity\Video;
use App\Repository\VideoRepository;
use App\Service\ApiResponseService;
use App\Service\VideoUploadService;
use App\Service\SearchService;
use App\Service\ValidationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use App\Entity\User;
use OpenApi\Attributes as OA;

#[Route('/api/videos')]
class VideoController extends AbstractController
{
    public function __construct(
        private ApiResponseService $apiResponse,
        private EntityManagerInterface $entityManager,
        private VideoRepository $videoRepository,
        private VideoUploadService $uploadService,
        private ValidatorInterface $validator,
        private SearchService $searchService,
        private ValidationService $validationService
    ) {}

    #[Route('/upload', name: 'upload_video', methods: ['POST'])]
    #[OA\Post(
        path: '/api/videos/upload',
        summary: 'Upload a video',
        tags: ['Videos'],
        security: [['JWT' => []]]
    )]
    public function uploadVideo(Request $request, #[CurrentUser] User $user): JsonResponse
    {
        $uploadedFile = $request->files->get('video');
        $title = $request->request->get('title');
        $description = $request->request->get('description');

        // Validate video file
        if (!$uploadedFile) {
            return $this->apiResponse->error('No video file provided', null, 400);
        }
        
        // Validate file type
        $allowedMimeTypes = ['video/mp4', 'video/avi', 'video/quicktime', 'video/x-msvideo'];
        $fileTypeErrors = $this->validationService->validateFileType(
            $uploadedFile->getMimeType(),
            $allowedMimeTypes,
            'Video file'
        );
        if (!empty($fileTypeErrors)) {
            return $this->apiResponse->error('Invalid file type', $fileTypeErrors, 400);
        }
        
        // Validate file size (max 500MB)
        $maxSize = 500 * 1024 * 1024; // 500MB in bytes
        $fileSizeErrors = $this->validationService->validateFileSize(
            $uploadedFile->getSize(),
            $maxSize,
            'Video file'
        );
        if (!empty($fileSizeErrors)) {
            return $this->apiResponse->error('File too large', $fileSizeErrors, 400);
        }

        // Validate title
        if (!$title) {
            return $this->apiResponse->error('Title is required', null, 400);
        }
        
        // Sanitize inputs
        $title = $this->validationService->sanitizeString($title);
        $description = $this->validationService->sanitizeString($description);
        
        // Validate title length
        $titleErrors = $this->validationService->validateStringLength($title, 'Title', 3, 200);
        if (!empty($titleErrors)) {
            return $this->apiResponse->error('Validation failed', $titleErrors, 400);
        }
        
        // Validate description length if provided
        if ($description) {
            $descErrors = $this->validationService->validateStringLength($description, 'Description', 0, 5000);
            if (!empty($descErrors)) {
                return $this->apiResponse->error('Validation failed', $descErrors, 400);
            }
        }

        try {
            $video = $this->uploadService->uploadVideo($uploadedFile, $user, $title, $description);
            
            // Process hashtags from description
            // Temporarily disabled to prevent upload errors
            // if ($description) {
            //     $this->searchService->processVideoHashtags($video, $description);
            // }

            return $this->apiResponse->success([
                'id' => $video->getId(),
                'title' => $video->getTitle(),
                'status' => $video->getStatus(),
                'filename' => $video->getFilename()
            ], 'Video uploaded successfully');

        } catch (\InvalidArgumentException $e) {
            return $this->apiResponse->error($e->getMessage(), null, 400);
        } catch (\Exception $e) {
            // Return actual error for debugging
            return $this->apiResponse->error('Upload failed: ' . $e->getMessage(), null, 500);
        }
    }

    #[Route('/upload-chunk', name: 'upload_video_chunk', methods: ['POST'])]
    public function uploadChunk(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        $filename = $data['filename'] ?? null;
        $chunk = $data['chunk'] ?? null;
        $chunkIndex = $data['chunkIndex'] ?? null;
        $totalChunks = $data['totalChunks'] ?? null;

        if (!$filename || !$chunk || $chunkIndex === null || !$totalChunks) {
            return $this->apiResponse->error('Missing required parameters', null, 400);
        }

        try {
            $isComplete = $this->uploadService->uploadChunk($filename, $chunk, $chunkIndex, $totalChunks);

            return $this->apiResponse->success([
                'chunkIndex' => $chunkIndex,
                'isComplete' => $isComplete
            ], $isComplete ? 'Upload completed' : 'Chunk uploaded');

        } catch (\Exception $e) {
            return $this->apiResponse->error('Chunk upload failed', null, 500);
        }
    }

    #[Route('', name: 'get_videos', methods: ['GET'])]
    public function getVideos(Request $request): JsonResponse
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = min(50, max(1, (int) $request->query->get('limit', 20)));
        $offset = ($page - 1) * $limit;

        $videos = $this->videoRepository->findPublicVideos($limit, $offset);
        $total = $this->videoRepository->countPublicVideos();

        $videoData = array_map([$this, 'formatVideoData'], $videos);
        $pagination = $this->apiResponse->paginate($videoData, $page, $limit, $total);

        return $this->apiResponse->success($videoData, 'Videos retrieved', $pagination);
    }

    #[Route('/{id}', name: 'get_video', methods: ['GET'])]
    public function getVideo(int $id, #[CurrentUser] ?User $currentUser): JsonResponse
    {
        $video = $this->videoRepository->find($id);

        if (!$video || $video->isDeleted()) {
            return $this->apiResponse->error('Video not found', null, 404);
        }

        // Check if user can view this video
        if (!$video->isPublic() && (!$currentUser || $currentUser->getId() !== $video->getUser()->getId())) {
            return $this->apiResponse->error('Video not accessible', null, 403);
        }

        // Increment view count
        $video->incrementViews();
        $this->entityManager->flush();

        return $this->apiResponse->success($this->formatVideoData($video, true), 'Video retrieved');
    }

    #[Route('/{id}', name: 'update_video', methods: ['PUT'])]
    #[OA\Put(
        path: '/api/videos/{id}',
        summary: 'Update video',
        tags: ['Videos'],
        security: [['JWT' => []]]
    )]
    public function updateVideo(int $id, Request $request, #[CurrentUser] User $user): JsonResponse
    {
        $video = $this->videoRepository->find($id);

        if (!$video || $video->isDeleted()) {
            return $this->apiResponse->error('Video not found', null, 404);
        }

        if ($video->getUser()->getId() !== $user->getId()) {
            return $this->apiResponse->error('Not authorized to update this video', null, 403);
        }

        $data = json_decode($request->getContent(), true);
        
        // Validate request body
        $bodyErrors = $this->validationService->validateRequestBody($data);
        if (!empty($bodyErrors)) {
            return $this->apiResponse->error('Invalid request', $bodyErrors, 400);
        }

        if (isset($data['title'])) {
            $title = $this->validationService->sanitizeString($data['title']);
            
            // Validate title length
            $titleErrors = $this->validationService->validateStringLength($title, 'Title', 3, 200);
            if (!empty($titleErrors)) {
                return $this->apiResponse->error('Validation failed', $titleErrors, 422);
            }
            
            $video->setTitle($title);
        }
        
        if (isset($data['description'])) {
            $description = $this->validationService->sanitizeString($data['description']);
            
            // Validate description length
            $descErrors = $this->validationService->validateStringLength($description, 'Description', 0, 5000);
            if (!empty($descErrors)) {
                return $this->apiResponse->error('Validation failed', $descErrors, 422);
            }
            
            $video->setDescription($description);
        }
        
        if (isset($data['isPublic'])) {
            $video->setIsPublic((bool) $data['isPublic']);
        }

        $video->setUpdatedAt(new \DateTime());

        $errors = $this->validator->validate($video);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return $this->apiResponse->error('Validation failed', $errorMessages, 422);
        }

        $this->entityManager->flush();

        return $this->apiResponse->success($this->formatVideoData($video), 'Video updated successfully');
    }

    #[Route('/{id}', name: 'delete_video', methods: ['DELETE'])]
    #[OA\Delete(
        path: '/api/videos/{id}',
        summary: 'Delete video',
        tags: ['Videos'],
        security: [['JWT' => []]]
    )]
    public function deleteVideo(int $id, #[CurrentUser] User $user): JsonResponse
    {
        $video = $this->videoRepository->find($id);

        if (!$video || $video->isDeleted()) {
            return $this->apiResponse->error('Video not found', null, 404);
        }

        if ($video->getUser()->getId() !== $user->getId()) {
            return $this->apiResponse->error('Not authorized to delete this video', null, 403);
        }

        $this->uploadService->deleteVideo($video);

        return $this->apiResponse->success(null, 'Video deleted successfully');
    }

    #[Route('/user/{userId}', name: 'get_user_videos', methods: ['GET'])]
    public function getUserVideos(int $userId, Request $request): JsonResponse
    {
        $user = $this->entityManager->getRepository(User::class)->find($userId);
        
        if (!$user) {
            return $this->apiResponse->error('User not found', null, 404);
        }

        $page = max(1, (int) $request->query->get('page', 1));
        $limit = min(50, max(1, (int) $request->query->get('limit', 20)));
        $offset = ($page - 1) * $limit;

        $videos = $this->videoRepository->findUserVideos($user, $limit, $offset);
        $total = $this->videoRepository->countUserVideos($user);

        $videoData = array_map([$this, 'formatVideoData'], $videos);
        $pagination = $this->apiResponse->paginate($videoData, $page, $limit, $total);

        return $this->apiResponse->success($videoData, 'User videos retrieved', $pagination);
    }

    #[Route('/search', name: 'search_videos', methods: ['GET'])]
    public function searchVideos(Request $request): JsonResponse
    {
        try {
            $query = $request->query->get('q', '');
            $page = max(1, (int) $request->query->get('page', 1));
            $limit = min(50, max(1, (int) $request->query->get('limit', 20)));

            // Validate minimum query length
            if (strlen($query) < 2) {
                return $this->apiResponse->error('Search query must be at least 2 characters', null, 400);
            }
            
            // Validate maximum query length
            if (strlen($query) > 200) {
                return $this->apiResponse->error('Search query must not exceed 200 characters', null, 400);
            }
            
            // Sanitize query
            $query = $this->validationService->sanitizeString($query);

            $offset = ($page - 1) * $limit;
            $videos = $this->videoRepository->searchVideos($query, $limit, $offset);

            $videoData = array_map([$this, 'formatVideoData'], $videos);
            $pagination = $this->apiResponse->paginate($videoData, $page, $limit, count($videos));

            return $this->apiResponse->success($videoData, 'Videos found', $pagination);
        } catch (\Exception $e) {
            return $this->apiResponse->error('Search failed: ' . $e->getMessage(), null, 500);
        }
    }

    #[Route('/trending', name: 'get_trending_videos', methods: ['GET'])]
    public function getTrendingVideos(Request $request): JsonResponse
    {
        try {
            $limit = min(50, max(1, (int) $request->query->get('limit', 20)));
            $videos = $this->videoRepository->findTrendingVideos($limit);

            $videoData = array_map([$this, 'formatVideoData'], $videos);

            return $this->apiResponse->success($videoData, 'Trending videos retrieved');
        } catch (\Exception $e) {
            return $this->apiResponse->error('Failed to retrieve trending videos: ' . $e->getMessage(), null, 500);
        }
    }

    private function formatVideoData(Video $video, bool $includeDetails = false): array
    {
        $data = [
            'id' => $video->getId(),
            'title' => $video->getTitle(),
            'description' => $video->getDescription(),
            'thumbnailPath' => $video->getThumbnailPath(),
            'duration' => $video->getDuration(),
            'viewsCount' => $video->getViewsCount(),
            'likesCount' => $video->getLikesCount(),
            'status' => $video->getStatus(),
            'isPublic' => $video->isPublic(),
            'createdAt' => $video->getCreatedAt()->format('Y-m-d H:i:s'),
            'user' => [
                'id' => $video->getUser()->getId(),
                'username' => $video->getUser()->getUsername(),
                'firstName' => $video->getUser()->getFirstName(),
                'profilePicture' => $video->getUser()->getProfilePicture()
            ]
        ];

        if ($includeDetails) {
            $data['filename'] = $video->getFilename();
            $data['fileSize'] = $video->getFileSize();
            $data['originalFilename'] = $video->getOriginalFilename();
        }

        return $data;
    }
}