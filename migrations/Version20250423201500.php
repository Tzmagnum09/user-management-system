<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250423201500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Mise à jour des index de la table audit_log pour améliorer les performances et la recherche';
    }

    public function up(Schema $schema): void
    {
        // Modification de la colonne user_agent pour accepter des chaînes plus longues
        $this->addSql('ALTER TABLE audit_log MODIFY user_agent VARCHAR(1000) DEFAULT NULL');
        
        // Ajout d'index pour améliorer les performances des requêtes sur les journaux d'audit
        $this->addSql('CREATE INDEX action_idx ON audit_log (action)');
        
        // Vérifier si l'index sur type existe déjà
        $table = $schema->getTable('audit_log');
        if (!$table->hasIndex('type_idx')) {
            $this->addSql('CREATE INDEX type_idx ON audit_log (type)');
        }
        
        // Ajout d'un index sur created_at pour améliorer les performances des requêtes par date
        $this->addSql('CREATE INDEX created_at_idx ON audit_log (created_at)');
    }

    public function down(Schema $schema): void
    {
        // Restauration de la colonne user_agent à sa taille d'origine
        $this->addSql('ALTER TABLE audit_log MODIFY user_agent VARCHAR(255) DEFAULT NULL');
        
        // Suppression des index ajoutés
        $this->addSql('DROP INDEX action_idx ON audit_log');
        $this->addSql('DROP INDEX created_at_idx ON audit_log');
        
        // Ne pas supprimer type_idx s'il existait déjà avant cette migration
        $table = $schema->getTable('audit_log');
        if ($table->hasIndex('type_idx')) {
            $this->addSql('DROP INDEX type_idx ON audit_log');
        }
    }
}