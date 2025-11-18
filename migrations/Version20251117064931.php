<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251117064931 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE refresh_token (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, token VARCHAR(255) NOT NULL, expires_at DATETIME NOT NULL, created_at DATETIME NOT NULL, is_revoked TINYINT(1) NOT NULL, UNIQUE INDEX UNIQ_C74F21955F37A13B (token), INDEX IDX_C74F2195A76ED395 (user_id), INDEX idx_refresh_token (token), INDEX idx_expires_at (expires_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE refresh_token ADD CONSTRAINT FK_C74F2195A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE refresh_token DROP FOREIGN KEY FK_C74F2195A76ED395');
        $this->addSql('DROP TABLE refresh_token');
    }
}
