<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250131203358 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE utilisateur ADD personnalisation_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE utilisateur ADD CONSTRAINT FK_1D1C63B33DD790BB FOREIGN KEY (personnalisation_id) REFERENCES personnalisation (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1D1C63B33DD790BB ON utilisateur (personnalisation_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE utilisateur DROP FOREIGN KEY FK_1D1C63B33DD790BB');
        $this->addSql('DROP INDEX UNIQ_1D1C63B33DD790BB ON utilisateur');
        $this->addSql('ALTER TABLE utilisateur DROP personnalisation_id');
    }
}
