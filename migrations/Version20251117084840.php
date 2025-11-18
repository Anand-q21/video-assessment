<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251117084840 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE `like` (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, video_id INT NOT NULL, created_at DATETIME NOT NULL, INDEX idx_like_user (user_id), INDEX idx_like_video (video_id), UNIQUE INDEX unique_user_video_like (user_id, video_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE `like` ADD CONSTRAINT FK_AC6340B3A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE `like` ADD CONSTRAINT FK_AC6340B329C1004E FOREIGN KEY (video_id) REFERENCES video (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE `like` DROP FOREIGN KEY FK_AC6340B3A76ED395');
        $this->addSql('ALTER TABLE `like` DROP FOREIGN KEY FK_AC6340B329C1004E');
        $this->addSql('DROP TABLE `like`');
    }
}
