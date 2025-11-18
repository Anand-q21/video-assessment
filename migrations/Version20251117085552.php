<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251117085552 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE hashtag (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, usage_count INT NOT NULL, created_at DATETIME NOT NULL, last_used_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_5AB52A615E237E06 (name), INDEX idx_hashtag_name (name), INDEX idx_hashtag_usage (usage_count), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE video_hashtag (video_id INT NOT NULL, hashtag_id INT NOT NULL, INDEX IDX_BF58C3CC29C1004E (video_id), INDEX IDX_BF58C3CCFB34EF56 (hashtag_id), PRIMARY KEY(video_id, hashtag_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE video_hashtag ADD CONSTRAINT FK_BF58C3CC29C1004E FOREIGN KEY (video_id) REFERENCES video (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE video_hashtag ADD CONSTRAINT FK_BF58C3CCFB34EF56 FOREIGN KEY (hashtag_id) REFERENCES hashtag (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE video_hashtag DROP FOREIGN KEY FK_BF58C3CC29C1004E');
        $this->addSql('ALTER TABLE video_hashtag DROP FOREIGN KEY FK_BF58C3CCFB34EF56');
        $this->addSql('DROP TABLE hashtag');
        $this->addSql('DROP TABLE video_hashtag');
    }
}
