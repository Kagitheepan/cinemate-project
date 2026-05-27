<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260527113800 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create deleted_user table and remove deleted_at from user table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE deleted_user (id INT AUTO_INCREMENT NOT NULL, original_id INT NOT NULL, username VARCHAR(180) NOT NULL, email VARCHAR(180) NOT NULL, deleted_at DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        
        // Remove deleted_at from user table
        $this->addSql('ALTER TABLE `user` DROP deleted_at');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE deleted_user');
        
        // Add deleted_at back to user table
        $this->addSql('ALTER TABLE `user` ADD deleted_at DATETIME DEFAULT NULL');
    }
}
