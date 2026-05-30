<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260528144238 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE Agenda (event_date DATETIME NOT NULL, time_slot VARCHAR(100) DEFAULT NULL, Id_Utilisateur INT NOT NULL, Id_Film INT NOT NULL, INDEX IDX_2B41CD414EF6594B (Id_Utilisateur), INDEX IDX_2B41CD4166EB3482 (Id_Film), PRIMARY KEY (Id_Utilisateur, Id_Film)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE Casting (Id_Casting INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, profile_path VARCHAR(255) DEFAULT NULL, PRIMARY KEY (Id_Casting)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE Composition_Film (character_name VARCHAR(255) DEFAULT NULL, cast_order INT DEFAULT NULL, Id_Film INT NOT NULL, Id_Casting INT NOT NULL, INDEX IDX_F806099166EB3482 (Id_Film), INDEX IDX_F80609918F66296C (Id_Casting), PRIMARY KEY (Id_Film, Id_Casting)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE Film (Id_Film INT AUTO_INCREMENT NOT NULL, tmdb_id INT NOT NULL, title VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, release_date DATE DEFAULT NULL, poster VARCHAR(255) DEFAULT NULL, backdrop VARCHAR(255) DEFAULT NULL, director VARCHAR(255) DEFAULT NULL, rating DOUBLE PRECISION DEFAULT NULL, trailer_key VARCHAR(255) DEFAULT NULL, runtime INT DEFAULT NULL, UNIQUE INDEX UNIQ_2276111C55BCC5E5 (tmdb_id), PRIMARY KEY (Id_Film)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE Catégorie (Id_Film INT NOT NULL, Id_Genre INT NOT NULL, INDEX IDX_A026AE6766EB3482 (Id_Film), INDEX IDX_A026AE6755623F9A (Id_Genre), PRIMARY KEY (Id_Film, Id_Genre)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE En_Streaming_Sur (Id_Film INT NOT NULL, Id_Plateforme INT NOT NULL, INDEX IDX_7221790466EB3482 (Id_Film), INDEX IDX_7221790465ABC4E3 (Id_Plateforme), PRIMARY KEY (Id_Film, Id_Plateforme)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE Genre (Id_Genre INT AUTO_INCREMENT NOT NULL, genre_name VARCHAR(100) NOT NULL, PRIMARY KEY (Id_Genre)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE Plateforme (Id_Plateforme INT AUTO_INCREMENT NOT NULL, platform_name VARCHAR(100) NOT NULL, PRIMARY KEY (Id_Plateforme)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE UserWatchlist (statut VARCHAR(255) DEFAULT NULL, Id_Utilisateur INT NOT NULL, Id_Film INT NOT NULL, INDEX IDX_CBC1E0E54EF6594B (Id_Utilisateur), INDEX IDX_CBC1E0E566EB3482 (Id_Film), PRIMARY KEY (Id_Utilisateur, Id_Film)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE Utilisateur (Id_Utilisateur INT AUTO_INCREMENT NOT NULL, username VARCHAR(180) NOT NULL, email VARCHAR(255) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_9B80EC64F85E0677 (username), UNIQUE INDEX UNIQ_9B80EC64E7927C74 (email), PRIMARY KEY (Id_Utilisateur)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE Plateforme_Favoris_User (Id_Utilisateur INT NOT NULL, Id_Plateforme INT NOT NULL, INDEX IDX_9891BF704EF6594B (Id_Utilisateur), INDEX IDX_9891BF7065ABC4E3 (Id_Plateforme), PRIMARY KEY (Id_Utilisateur, Id_Plateforme)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE Genre_Favoris (Id_Utilisateur INT NOT NULL, Id_Genre INT NOT NULL, INDEX IDX_CE7525A34EF6594B (Id_Utilisateur), INDEX IDX_CE7525A355623F9A (Id_Genre), PRIMARY KEY (Id_Utilisateur, Id_Genre)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE notification (id INT AUTO_INCREMENT NOT NULL, message VARCHAR(255) NOT NULL, type VARCHAR(50) NOT NULL, is_read TINYINT NOT NULL, email_sent TINYINT NOT NULL, movie_id VARCHAR(255) DEFAULT NULL, event_id VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, event_date DATETIME DEFAULT NULL, Id_Utilisateur INT NOT NULL, INDEX IDX_BF5476CA4EF6594B (Id_Utilisateur), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE Agenda ADD CONSTRAINT FK_2B41CD414EF6594B FOREIGN KEY (Id_Utilisateur) REFERENCES Utilisateur (Id_Utilisateur)');
        $this->addSql('ALTER TABLE Agenda ADD CONSTRAINT FK_2B41CD4166EB3482 FOREIGN KEY (Id_Film) REFERENCES Film (Id_Film)');
        $this->addSql('ALTER TABLE Composition_Film ADD CONSTRAINT FK_F806099166EB3482 FOREIGN KEY (Id_Film) REFERENCES Film (Id_Film)');
        $this->addSql('ALTER TABLE Composition_Film ADD CONSTRAINT FK_F80609918F66296C FOREIGN KEY (Id_Casting) REFERENCES Casting (Id_Casting)');
        $this->addSql('ALTER TABLE Catégorie ADD CONSTRAINT FK_A026AE6766EB3482 FOREIGN KEY (Id_Film) REFERENCES Film (Id_Film)');
        $this->addSql('ALTER TABLE Catégorie ADD CONSTRAINT FK_A026AE6755623F9A FOREIGN KEY (Id_Genre) REFERENCES Genre (Id_Genre)');
        $this->addSql('ALTER TABLE En_Streaming_Sur ADD CONSTRAINT FK_7221790466EB3482 FOREIGN KEY (Id_Film) REFERENCES Film (Id_Film)');
        $this->addSql('ALTER TABLE En_Streaming_Sur ADD CONSTRAINT FK_7221790465ABC4E3 FOREIGN KEY (Id_Plateforme) REFERENCES Plateforme (Id_Plateforme)');
        $this->addSql('ALTER TABLE UserWatchlist ADD CONSTRAINT FK_CBC1E0E54EF6594B FOREIGN KEY (Id_Utilisateur) REFERENCES Utilisateur (Id_Utilisateur)');
        $this->addSql('ALTER TABLE UserWatchlist ADD CONSTRAINT FK_CBC1E0E566EB3482 FOREIGN KEY (Id_Film) REFERENCES Film (Id_Film)');
        $this->addSql('ALTER TABLE Plateforme_Favoris_User ADD CONSTRAINT FK_9891BF704EF6594B FOREIGN KEY (Id_Utilisateur) REFERENCES Utilisateur (Id_Utilisateur)');
        $this->addSql('ALTER TABLE Plateforme_Favoris_User ADD CONSTRAINT FK_9891BF7065ABC4E3 FOREIGN KEY (Id_Plateforme) REFERENCES Plateforme (Id_Plateforme)');
        $this->addSql('ALTER TABLE Genre_Favoris ADD CONSTRAINT FK_CE7525A34EF6594B FOREIGN KEY (Id_Utilisateur) REFERENCES Utilisateur (Id_Utilisateur)');
        $this->addSql('ALTER TABLE Genre_Favoris ADD CONSTRAINT FK_CE7525A355623F9A FOREIGN KEY (Id_Genre) REFERENCES Genre (Id_Genre)');
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_BF5476CA4EF6594B FOREIGN KEY (Id_Utilisateur) REFERENCES Utilisateur (Id_Utilisateur)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE Agenda DROP FOREIGN KEY FK_2B41CD414EF6594B');
        $this->addSql('ALTER TABLE Agenda DROP FOREIGN KEY FK_2B41CD4166EB3482');
        $this->addSql('ALTER TABLE Composition_Film DROP FOREIGN KEY FK_F806099166EB3482');
        $this->addSql('ALTER TABLE Composition_Film DROP FOREIGN KEY FK_F80609918F66296C');
        $this->addSql('ALTER TABLE Catégorie DROP FOREIGN KEY FK_A026AE6766EB3482');
        $this->addSql('ALTER TABLE Catégorie DROP FOREIGN KEY FK_A026AE6755623F9A');
        $this->addSql('ALTER TABLE En_Streaming_Sur DROP FOREIGN KEY FK_7221790466EB3482');
        $this->addSql('ALTER TABLE En_Streaming_Sur DROP FOREIGN KEY FK_7221790465ABC4E3');
        $this->addSql('ALTER TABLE UserWatchlist DROP FOREIGN KEY FK_CBC1E0E54EF6594B');
        $this->addSql('ALTER TABLE UserWatchlist DROP FOREIGN KEY FK_CBC1E0E566EB3482');
        $this->addSql('ALTER TABLE Plateforme_Favoris_User DROP FOREIGN KEY FK_9891BF704EF6594B');
        $this->addSql('ALTER TABLE Plateforme_Favoris_User DROP FOREIGN KEY FK_9891BF7065ABC4E3');
        $this->addSql('ALTER TABLE Genre_Favoris DROP FOREIGN KEY FK_CE7525A34EF6594B');
        $this->addSql('ALTER TABLE Genre_Favoris DROP FOREIGN KEY FK_CE7525A355623F9A');
        $this->addSql('ALTER TABLE notification DROP FOREIGN KEY FK_BF5476CA4EF6594B');
        $this->addSql('DROP TABLE Agenda');
        $this->addSql('DROP TABLE Casting');
        $this->addSql('DROP TABLE Composition_Film');
        $this->addSql('DROP TABLE Film');
        $this->addSql('DROP TABLE Catégorie');
        $this->addSql('DROP TABLE En_Streaming_Sur');
        $this->addSql('DROP TABLE Genre');
        $this->addSql('DROP TABLE Plateforme');
        $this->addSql('DROP TABLE UserWatchlist');
        $this->addSql('DROP TABLE Utilisateur');
        $this->addSql('DROP TABLE Plateforme_Favoris_User');
        $this->addSql('DROP TABLE Genre_Favoris');
        $this->addSql('DROP TABLE notification');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
