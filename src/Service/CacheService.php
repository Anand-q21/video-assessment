<?php

namespace App\Service;

use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class CacheService
{
    public function __construct(
        private CacheInterface $videosCache,
        private CacheInterface $feedsCache,
        private CacheInterface $usersCache
    ) {}

    public function getVideoCache(string $key, callable $callback, int $ttl = 1800): mixed
    {
        return $this->videosCache->get($key, function (ItemInterface $item) use ($callback, $ttl) {
            $item->expiresAfter($ttl);
            return $callback();
        });
    }

    public function getFeedCache(string $key, callable $callback, int $ttl = 300): mixed
    {
        return $this->feedsCache->get($key, function (ItemInterface $item) use ($callback, $ttl) {
            $item->expiresAfter($ttl);
            return $callback();
        });
    }

    public function getUserCache(string $key, callable $callback, int $ttl = 3600): mixed
    {
        return $this->usersCache->get($key, function (ItemInterface $item) use ($callback, $ttl) {
            $item->expiresAfter($ttl);
            return $callback();
        });
    }

    public function invalidateVideoCache(int $videoId): void
    {
        $this->videosCache->delete("video_{$videoId}");
        $this->feedsCache->clear(); // Clear all feeds when video changes
    }

    public function invalidateUserCache(int $userId): void
    {
        $this->usersCache->delete("user_{$userId}");
        $this->usersCache->delete("user_profile_{$userId}");
    }

    public function invalidateFeedCache(): void
    {
        $this->feedsCache->clear();
    }
}