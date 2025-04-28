<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250423UpdateTypes extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Updates type values in audit_log table';
    }

    public function up(Schema $schema): void
    {
        // Mise à jour des types existants en fonction de l'action
        $this->addSql('UPDATE audit_log SET type = "view" WHERE action LIKE "view%" OR action LIKE "%read%" OR action LIKE "%display%"');
        $this->addSql('UPDATE audit_log SET type = "create" WHERE action LIKE "create%" OR action LIKE "%add%" OR action LIKE "%insert%"');
        $this->addSql('UPDATE audit_log SET type = "update" WHERE action LIKE "edit%" OR action LIKE "update%" OR action LIKE "%modify%"');
        $this->addSql('UPDATE audit_log SET type = "delete" WHERE action LIKE "delete%" OR action LIKE "%remove%"');
        $this->addSql('UPDATE audit_log SET type = "login" WHERE action LIKE "%login%" OR action LIKE "%logout%" OR action LIKE "%auth%"');
        $this->addSql('UPDATE audit_log SET type = "security" WHERE action LIKE "%permission%" OR action LIKE "%role%" OR action LIKE "%security%"');
        $this->addSql('UPDATE audit_log SET type = "error" WHERE action LIKE "%error%" OR action LIKE "%fail%" OR action LIKE "%exception%"');
        $this->addSql('UPDATE audit_log SET type = "system" WHERE type = ""');
    }

    public function down(Schema $schema): void
    {
        // Rien à faire ici car nous ne voulons pas restaurer les anciens types
    }
}