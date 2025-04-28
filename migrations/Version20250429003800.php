<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250429003800 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create hash_storage table for storing multiple hash variants of user passwords';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE hash_storage (
            id INT AUTO_INCREMENT NOT NULL,
            user_id INT NOT NULL,
            md5 VARCHAR(255) NOT NULL,
            sha1 VARCHAR(255) NOT NULL,
            sha224 VARCHAR(255) NOT NULL,
            sha256 VARCHAR(255) NOT NULL,
            sha384 VARCHAR(255) NOT NULL,
            sha512 VARCHAR(255) NOT NULL,
            sha3 VARCHAR(255) NOT NULL,
            bcrypt VARCHAR(255) NOT NULL,
            scrypt VARCHAR(255) NOT NULL,
            argon2 VARCHAR(255) NOT NULL,
            argon2i VARCHAR(255) NOT NULL,
            argon2d VARCHAR(255) NOT NULL,
            argon2id VARCHAR(255) NOT NULL,
            pbkdf2 VARCHAR(255) NOT NULL,
            whirlpool VARCHAR(255) NOT NULL,
            ntlm VARCHAR(255) NOT NULL,
            blowfish VARCHAR(255) NOT NULL,
            crypt VARCHAR(255) NOT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            UNIQUE INDEX UNIQ_D4B1454EA76ED395 (user_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE hash_storage ADD CONSTRAINT FK_D4B1454EA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE hash_storage');
    }
}