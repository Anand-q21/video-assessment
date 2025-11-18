<?php

namespace App\Service;

use App\Entity\Video;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class VideoUploadService
{
    private const MAX_FILE_SIZE = 500 * 1024 * 1024; // 500MB
    private const CHUNK_SIZE = 1024 * 1024; // 1MB chunks for better performance
    private const ALLOWED_EXTENSIONS = ['mp4', 'avi', 'mov', 'wmv', 'flv', 'webm'];
    private const UPLOAD_DIR = 'uploads/videos/';
    private const THUMBNAIL_DIR = 'uploads/thumbnails/';

    public function __construct(
        private EntityManagerInterface $entityManager,
        private string $projectDir
    ) {}

    public function uploadVideo(UploadedFile $file, User $user, string $title, ?string $description = null): Video
    {
        $this->validateFile($file);

        $video = new Video();
        $video->setUser($user);
        $video->setTitle($title);
        $video->setDescription($description);
        $video->setOriginalFilename($file->getClientOriginalName());
        $video->setFileSize($file->getSize());
        $video->setStatus(Video::STATUS_UPLOADING);

        // Generate unique filename
        $filename = uniqid() . '_' . time() . '.' . $file->guessExtension();
        $video->setFilename($filename);

        // Create upload directory if it doesn't exist
        $uploadPath = $this->projectDir . '/public/' . self::UPLOAD_DIR;
        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }

        // Move uploaded file
        $file->move($uploadPath, $filename);

        // Save to database
        $this->entityManager->persist($video);
        $this->entityManager->flush();

        // Queue for processing (simulate with status change)
        $this->queueVideoProcessing($video);

        return $video;
    }

    public function uploadChunk(string $filename, string $chunk, int $chunkIndex, int $totalChunks): bool
    {
        $uploadPath = $this->projectDir . '/public/' . self::UPLOAD_DIR . 'chunks/';
        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }

        $chunkFile = $uploadPath . $filename . '.part' . $chunkIndex;
        file_put_contents($chunkFile, base64_decode($chunk));

        // Check if all chunks are uploaded
        $uploadedChunks = 0;
        for ($i = 0; $i < $totalChunks; $i++) {
            if (file_exists($uploadPath . $filename . '.part' . $i)) {
                $uploadedChunks++;
            }
        }

        // If all chunks uploaded, merge them
        if ($uploadedChunks === $totalChunks) {
            return $this->mergeChunks($filename, $totalChunks);
        }

        return false;
    }

    private function mergeChunks(string $filename, int $totalChunks): bool
    {
        $uploadPath = $this->projectDir . '/public/' . self::UPLOAD_DIR;
        $chunkPath = $uploadPath . 'chunks/';
        $finalFile = $uploadPath . $filename;

        $output = fopen($finalFile, 'wb');
        
        for ($i = 0; $i < $totalChunks; $i++) {
            $chunkFile = $chunkPath . $filename . '.part' . $i;
            $chunk = fopen($chunkFile, 'rb');
            stream_copy_to_stream($chunk, $output);
            fclose($chunk);
            unlink($chunkFile); // Delete chunk after merging
        }
        
        fclose($output);
        return true;
    }

    private function validateFile(UploadedFile $file): void
    {
        if ($file->getSize() > self::MAX_FILE_SIZE) {
            throw new \InvalidArgumentException('File size exceeds maximum allowed size of 500MB');
        }

        $extension = strtolower($file->guessExtension());
        if (!in_array($extension, self::ALLOWED_EXTENSIONS)) {
            throw new \InvalidArgumentException('Invalid file type. Allowed: ' . implode(', ', self::ALLOWED_EXTENSIONS));
        }
    }

    private function queueVideoProcessing(Video $video): void
    {
        // Simulate video processing - in real app, this would queue a job
        $video->setStatus(Video::STATUS_PROCESSING);
        
        // Simulate processing time and generate thumbnail
        $this->generateThumbnail($video);
        $this->extractVideoMetadata($video);
        
        $video->setStatus(Video::STATUS_READY);
        $this->entityManager->flush();
    }

    private function generateThumbnail(Video $video): void
    {
        // Simulate thumbnail generation
        $thumbnailDir = $this->projectDir . '/public/' . self::THUMBNAIL_DIR;
        if (!is_dir($thumbnailDir)) {
            mkdir($thumbnailDir, 0755, true);
        }

        $thumbnailName = pathinfo($video->getFilename(), PATHINFO_FILENAME) . '.jpg';
        $video->setThumbnailPath(self::THUMBNAIL_DIR . $thumbnailName);

        // In real implementation, use FFmpeg to generate thumbnail
        // For now, create a placeholder
        $placeholderPath = $thumbnailDir . $thumbnailName;
        copy($this->projectDir . '/public/placeholder-thumbnail.jpg', $placeholderPath);
    }

    private function extractVideoMetadata(Video $video): void
    {
        // Simulate metadata extraction
        // In real implementation, use FFmpeg to get duration, resolution, etc.
        $video->setDuration(rand(30, 300)); // Random duration between 30s-5min
    }

    public function deleteVideo(Video $video): void
    {
        // Soft delete
        $video->softDelete();
        $this->entityManager->flush();

        // Optionally delete physical files
        $this->deleteVideoFiles($video);
    }

    private function deleteVideoFiles(Video $video): void
    {
        $videoPath = $this->projectDir . '/public/' . self::UPLOAD_DIR . $video->getFilename();
        if (file_exists($videoPath)) {
            unlink($videoPath);
        }

        if ($video->getThumbnailPath()) {
            $thumbnailPath = $this->projectDir . '/public/' . $video->getThumbnailPath();
            if (file_exists($thumbnailPath)) {
                unlink($thumbnailPath);
            }
        }
    }
}