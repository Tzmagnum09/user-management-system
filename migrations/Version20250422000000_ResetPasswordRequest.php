<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250422000000_ResetPasswordRequest extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create reset_password_request table';
    }

    public function up(Schema $schema): void
    {
        // Create reset_password_request table if it doesn't exist
        if (!$schema->hasTable('reset_password_request')) {
            $this->addSql('CREATE TABLE reset_password_request (
                id INT AUTO_INCREMENT NOT NULL,
                user_id INT NOT NULL,
                token VARCHAR(100) NOT NULL,
                requested_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
                expires_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
                PRIMARY KEY(id),
                INDEX IDX_7CE748AA76ED395 (user_id),
                CONSTRAINT FK_7CE748AA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        }
    }

    public function down(Schema $schema): void
    {
        if ($schema->hasTable('reset_password_request')) {
            $this->addSql('DROP TABLE reset_password_request');
        }
    }
}