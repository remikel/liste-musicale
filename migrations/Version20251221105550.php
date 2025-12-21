<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251221105550 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE participant (id INT AUTO_INCREMENT NOT NULL, session_id INT NOT NULL, name VARCHAR(255) NOT NULL, joined_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', validated TINYINT(1) NOT NULL, INDEX IDX_D79F6B11613FECDF (session_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE session (id INT AUTO_INCREMENT NOT NULL, code VARCHAR(5) NOT NULL, name VARCHAR(255) NOT NULL, max_tracks_per_participant INT DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_D044D5D477153098 (code), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE track (id INT AUTO_INCREMENT NOT NULL, session_id INT NOT NULL, participant_id INT NOT NULL, title VARCHAR(255) NOT NULL, artist VARCHAR(255) NOT NULL, album VARCHAR(255) DEFAULT NULL, duration INT DEFAULT NULL, deezer_track_id VARCHAR(255) DEFAULT NULL, spotify_track_id VARCHAR(255) DEFAULT NULL, spotify_uri VARCHAR(255) DEFAULT NULL, cover_url VARCHAR(500) DEFAULT NULL, added_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_D6E3F8A6613FECDF (session_id), INDEX IDX_D6E3F8A69D1C3019 (participant_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', available_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', delivered_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_75EA56E0FB7336F0 (queue_name), INDEX IDX_75EA56E0E3BD61CE (available_at), INDEX IDX_75EA56E016BA31DB (delivered_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE participant ADD CONSTRAINT FK_D79F6B11613FECDF FOREIGN KEY (session_id) REFERENCES session (id)');
        $this->addSql('ALTER TABLE track ADD CONSTRAINT FK_D6E3F8A6613FECDF FOREIGN KEY (session_id) REFERENCES session (id)');
        $this->addSql('ALTER TABLE track ADD CONSTRAINT FK_D6E3F8A69D1C3019 FOREIGN KEY (participant_id) REFERENCES participant (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE participant DROP FOREIGN KEY FK_D79F6B11613FECDF');
        $this->addSql('ALTER TABLE track DROP FOREIGN KEY FK_D6E3F8A6613FECDF');
        $this->addSql('ALTER TABLE track DROP FOREIGN KEY FK_D6E3F8A69D1C3019');
        $this->addSql('DROP TABLE participant');
        $this->addSql('DROP TABLE session');
        $this->addSql('DROP TABLE track');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
