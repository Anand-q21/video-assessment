<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251117082516 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE follow (id INT AUTO_INCREMENT NOT NULL, follower_id INT NOT NULL, following_id INT NOT NULL, created_at DATETIME NOT NULL, INDEX idx_follower (follower_id), INDEX idx_following (following_id), UNIQUE INDEX unique_follow (follower_id, following_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE follow ADD CONSTRAINT FK_68344470AC24F853 FOREIGN KEY (follower_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE follow ADD CONSTRAINT FK_683444701816E3A3 FOREIGN KEY (following_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user CHANGE is_active is_active TINYINT(1) NOT NULL, CHANGE created_at created_at DATETIME NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE follow DROP FOREIGN KEY FK_68344470AC24F853');
        $this->addSql('ALTER TABLE follow DROP FOREIGN KEY FK_683444701816E3A3');
        $this->addSql('DROP TABLE follow');
        $this->addSql('ALTER TABLE user CHANGE is_active is_active TINYINT(1) DEFAULT 1 NOT NULL, CHANGE created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL');
    }
}
