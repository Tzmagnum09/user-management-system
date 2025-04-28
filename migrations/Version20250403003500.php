<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Ajoute les champs de détails du navigateur et appareil aux logs d'audit
 */
final class Version20250423000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute des champs pour stocker les informations du dispositif et du navigateur dans les logs d\'audit';
    }

    public function up(Schema $schema): void
    {
        // Ajout des colonnes à la table audit_log
        $this->addSql('ALTER TABLE audit_log ADD user_agent VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE audit_log ADD device_brand VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE audit_log ADD device_model VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE audit_log ADD os_name VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE audit_log ADD os_version VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE audit_log ADD browser_name VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE audit_log ADD browser_version VARCHAR(100) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // Suppression des colonnes en cas de rollback
        $this->addSql('ALTER TABLE audit_log DROP user_agent');
        $this->addSql('ALTER TABLE audit_log DROP device_brand');
        $this->addSql('ALTER TABLE audit_log DROP device_model');
        $this->addSql('ALTER TABLE audit_log DROP os_name');
        $this->addSql('ALTER TABLE audit_log DROP os_version');
        $this->addSql('ALTER TABLE audit_log DROP browser_name');
        $this->addSql('ALTER TABLE audit_log DROP browser_version');
    }
}