<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration pour améliorer la capture des User-Agents et informations du device
 */
final class Version20250423UserAgentMigration extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Augmente la taille de la colonne user_agent et assure que toutes les colonnes de détails de device ont des valeurs par défaut correctes';
    }

    public function up(Schema $schema): void
    {
        // Changer la taille du champ userAgent pour stocker des chaînes plus longues
        $this->addSql('ALTER TABLE audit_log MODIFY user_agent VARCHAR(5000) NULL');
        
        // S'assurer que toutes les colonnes ont une valeur par défaut appropriée
        $this->addSql('UPDATE audit_log SET device_brand = "Unknown" WHERE device_brand IS NULL OR device_brand = ""');
        $this->addSql('UPDATE audit_log SET device_model = "Unknown" WHERE device_model IS NULL OR device_model = ""');
        $this->addSql('UPDATE audit_log SET os_name = "Unknown" WHERE os_name IS NULL OR os_name = ""');
        $this->addSql('UPDATE audit_log SET os_version = "Unknown" WHERE os_version IS NULL OR os_version = ""');
        $this->addSql('UPDATE audit_log SET browser_name = "Unknown" WHERE browser_name IS NULL OR browser_name = ""');
        $this->addSql('UPDATE audit_log SET browser_version = "Unknown" WHERE browser_version IS NULL OR browser_version = ""');
    }

    public function down(Schema $schema): void
    {
        // Revenir à la taille d'origine
        $this->addSql('ALTER TABLE audit_log MODIFY user_agent VARCHAR(255) NULL');
        
        // Il n'est pas nécessaire d'annuler la mise à jour des valeurs par défaut
    }
}