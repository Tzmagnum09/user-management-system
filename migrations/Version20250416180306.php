<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250416180306 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE admin_permission (id INT AUTO_INCREMENT NOT NULL, admin_id INT NOT NULL, permission VARCHAR(100) NOT NULL, is_granted TINYINT(1) NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', updated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', INDEX IDX_2877342F642B8210 (admin_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE audit_log (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, action VARCHAR(100) NOT NULL, details LONGTEXT DEFAULT NULL, ip_address VARCHAR(45) NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', INDEX IDX_F6E1C0F5A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE email_template (id INT AUTO_INCREMENT NOT NULL, code VARCHAR(100) NOT NULL, subject VARCHAR(255) NOT NULL, html_content LONGTEXT NOT NULL, locale VARCHAR(10) NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', updated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE `user` (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, username VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, last_name VARCHAR(255) NOT NULL, first_name VARCHAR(255) NOT NULL, street VARCHAR(255) NOT NULL, house_number VARCHAR(20) NOT NULL, box_number VARCHAR(20) DEFAULT NULL, postal_code VARCHAR(20) NOT NULL, city VARCHAR(255) NOT NULL, country VARCHAR(255) NOT NULL, phone_number VARCHAR(50) NOT NULL, locale VARCHAR(10) NOT NULL, is_verified TINYINT(1) NOT NULL, is_approved TINYINT(1) NOT NULL, email_verified_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', approved_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', last_login_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', birth_date DATE DEFAULT NULL, UNIQUE INDEX UNIQ_8D93D649E7927C74 (email), UNIQUE INDEX UNIQ_8D93D649F85E0677 (username), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', available_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', delivered_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', INDEX IDX_75EA56E0FB7336F0 (queue_name), INDEX IDX_75EA56E0E3BD61CE (available_at), INDEX IDX_75EA56E016BA31DB (delivered_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE admin_permission ADD CONSTRAINT FK_2877342F642B8210 FOREIGN KEY (admin_id) REFERENCES `user` (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE audit_log ADD CONSTRAINT FK_F6E1C0F5A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE admin_permission DROP FOREIGN KEY FK_2877342F642B8210
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE audit_log DROP FOREIGN KEY FK_F6E1C0F5A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE admin_permission
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE audit_log
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE email_template
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE `user`
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE messenger_messages
        SQL);
    }
}
