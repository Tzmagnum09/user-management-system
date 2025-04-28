<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration pour augmenter la longueur du champ userAgent dans la table audit_log
 */
final class Version20250423UserAgentLength extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Augmente la longueur du champ userAgent de 1000 à 2000 caractères.';
    }

    public function up(Schema $schema): void
    {
        // Modifie la colonne pour augmenter sa longueur
        $this->addSql('ALTER TABLE audit_log MODIFY user_agent VARCHAR(2000) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // Revient à la longueur précédente
        $this->addSql('ALTER TABLE audit_log MODIFY user_agent VARCHAR(1000) DEFAULT NULL');
    }
}