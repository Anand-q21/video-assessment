<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251117084112 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE video (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, title VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, filename VARCHAR(255) NOT NULL, original_filename VARCHAR(255) DEFAULT NULL, thumbnail_path VARCHAR(255) DEFAULT NULL, duration INT NOT NULL, file_size INT NOT NULL, status VARCHAR(50) NOT NULL, processing_error LONGTEXT DEFAULT NULL, views_count INT NOT NULL, likes_count INT NOT NULL, is_public TINYINT(1) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, deleted_at DATETIME DEFAULT NULL, INDEX idx_video_user (user_id), INDEX idx_video_status (status), INDEX idx_video_created (created_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE video ADD CONSTRAINT FK_7CC7DA2CA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE video DROP FOREIGN KEY FK_7CC7DA2CA76ED395');
        $this->addSql('DROP TABLE video');
    }
}
