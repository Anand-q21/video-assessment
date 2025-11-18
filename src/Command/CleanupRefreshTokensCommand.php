<?php

namespace App\Command;

use App\Repository\RefreshTokenRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:cleanup-refresh-tokens',
    description: 'Remove expired refresh tokens from the database',
)]
class CleanupRefreshTokensCommand extends Command
{
    public function __construct(
        private RefreshTokenRepository $refreshTokenRepository
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $deletedCount = $this->refreshTokenRepository->deleteExpiredTokens();

        $io->success(sprintf('Deleted %d expired refresh tokens.', $deletedCount));

        return Command::SUCCESS;
    }
}