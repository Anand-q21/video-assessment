<?php

namespace App\Service;

use App\Entity\RefreshToken;
use App\Entity\User;
use App\Repository\RefreshTokenRepository;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;

class JWTService
{
    private const REFRESH_TOKEN_TTL = 7 * 24 * 60 * 60; // 7 days in seconds

    public function __construct(
        private JWTTokenManagerInterface $jwtManager,
        private EntityManagerInterface $entityManager,
        private RefreshTokenRepository $refreshTokenRepository
    ) {}

    public function createTokenPair(User $user): array
    {
        // Generate access token (short-lived)
        $accessToken = $this->jwtManager->create($user);

        // Generate refresh token (long-lived)
        $refreshToken = $this->generateRefreshToken($user);

        return [
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken->getToken(),
            'token_type' => 'Bearer',
            'expires_in' => 3600, // 1 hour for access token
        ];
    }

    public function refreshAccessToken(string $refreshTokenString): ?array
    {
        $refreshToken = $this->refreshTokenRepository->findValidToken($refreshTokenString);

        if (!$refreshToken || !$refreshToken->isValid()) {
            return null;
        }

        $user = $refreshToken->getUser();
        
        // Generate new access token
        $accessToken = $this->jwtManager->create($user);

        // Optionally rotate refresh token for better security
        $newRefreshToken = $this->rotateRefreshToken($refreshToken);

        return [
            'access_token' => $accessToken,
            'refresh_token' => $newRefreshToken->getToken(),
            'token_type' => 'Bearer',
            'expires_in' => 3600,
        ];
    }

    public function revokeRefreshToken(string $refreshTokenString): bool
    {
        $refreshToken = $this->refreshTokenRepository->findValidToken($refreshTokenString);

        if (!$refreshToken) {
            return false;
        }

        $refreshToken->setIsRevoked(true);
        $this->entityManager->flush();

        return true;
    }

    public function revokeAllUserTokens(User $user): void
    {
        $this->refreshTokenRepository->revokeAllUserTokens($user);
    }

    private function generateRefreshToken(User $user): RefreshToken
    {
        $refreshToken = new RefreshToken();
        $refreshToken->setToken(bin2hex(random_bytes(32)));
        $refreshToken->setUser($user);
        $refreshToken->setExpiresAt(new \DateTime('+' . self::REFRESH_TOKEN_TTL . ' seconds'));

        $this->entityManager->persist($refreshToken);
        $this->entityManager->flush();

        return $refreshToken;
    }

    private function rotateRefreshToken(RefreshToken $oldToken): RefreshToken
    {
        // Revoke old token
        $oldToken->setIsRevoked(true);

        // Create new token
        $newToken = $this->generateRefreshToken($oldToken->getUser());

        return $newToken;
    }
}