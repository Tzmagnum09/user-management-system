<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250424155438 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            DROP INDEX created_at_idx ON audit_log
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX action_idx ON audit_log
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX type_idx ON audit_log
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE audit_log CHANGE action action VARCHAR(255) NOT NULL, CHANGE user_agent user_agent VARCHAR(2000) DEFAULT NULL, CHANGE device_brand device_brand VARCHAR(255) DEFAULT NULL, CHANGE device_model device_model VARCHAR(255) DEFAULT NULL, CHANGE os_name os_name VARCHAR(255) DEFAULT NULL, CHANGE os_version os_version VARCHAR(255) DEFAULT NULL, CHANGE browser_name browser_name VARCHAR(255) DEFAULT NULL, CHANGE browser_version browser_version VARCHAR(255) DEFAULT NULL, CHANGE type type VARCHAR(50) NOT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE audit_log CHANGE action action VARCHAR(100) NOT NULL, CHANGE user_agent user_agent VARCHAR(5000) DEFAULT NULL, CHANGE type type VARCHAR(20) NOT NULL, CHANGE device_brand device_brand VARCHAR(100) DEFAULT NULL, CHANGE device_model device_model VARCHAR(100) DEFAULT NULL, CHANGE os_name os_name VARCHAR(100) DEFAULT NULL, CHANGE os_version os_version VARCHAR(100) DEFAULT NULL, CHANGE browser_name browser_name VARCHAR(100) DEFAULT NULL, CHANGE browser_version browser_version VARCHAR(100) DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX created_at_idx ON audit_log (created_at)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX action_idx ON audit_log (action)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX type_idx ON audit_log (type)
        SQL);
    }
}
