<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260625133745 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE Categorie (Id_Film INT NOT NULL, Id_Genre INT NOT NULL, INDEX IDX_CB8C549766EB3482 (Id_Film), INDEX IDX_CB8C549755623F9A (Id_Genre), PRIMARY KEY (Id_Film, Id_Genre)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE Categorie ADD CONSTRAINT FK_CB8C549766EB3482 FOREIGN KEY (Id_Film) REFERENCES Film (Id_Film)');
        $this->addSql('ALTER TABLE Categorie ADD CONSTRAINT FK_CB8C549755623F9A FOREIGN KEY (Id_Genre) REFERENCES Genre (Id_Genre)');
        $this->addSql('ALTER TABLE Catégorie DROP FOREIGN KEY `FK_A026AE6755623F9A`');
        $this->addSql('ALTER TABLE Catégorie DROP FOREIGN KEY `FK_A026AE6766EB3482`');
        $this->addSql('DROP TABLE Catégorie');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE Catégorie (Id_Film INT NOT NULL, Id_Genre INT NOT NULL, INDEX IDX_A026AE6755623F9A (Id_Genre), INDEX IDX_A026AE6766EB3482 (Id_Film), PRIMARY KEY (Id_Film, Id_Genre)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE Catégorie ADD CONSTRAINT `FK_A026AE6755623F9A` FOREIGN KEY (Id_Genre) REFERENCES Genre (Id_Genre) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE Catégorie ADD CONSTRAINT `FK_A026AE6766EB3482` FOREIGN KEY (Id_Film) REFERENCES Film (Id_Film) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE Categorie DROP FOREIGN KEY FK_CB8C549766EB3482');
        $this->addSql('ALTER TABLE Categorie DROP FOREIGN KEY FK_CB8C549755623F9A');
        $this->addSql('DROP TABLE Categorie');
    }
}
