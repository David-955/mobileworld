<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250623075625 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE utilisateur DROP FOREIGN KEY FK_1D1C63B33DD790BB');
        $this->addSql('ALTER TABLE utilisateur ADD CONSTRAINT FK_1D1C63B33DD790BB FOREIGN KEY (personnalisation_id) REFERENCES personnalisation (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE utilisateur DROP FOREIGN KEY FK_1D1C63B33DD790BB');
        $this->addSql('ALTER TABLE utilisateur ADD CONSTRAINT FK_1D1C63B33DD790BB FOREIGN KEY (personnalisation_id) REFERENCES personnalisation (id)');
    }
}
