<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250609102531 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE personnalisation ADD utilisateur_id INT NOT NULL');
        $this->addSql('ALTER TABLE personnalisation ADD CONSTRAINT FK_C9B3EAE6FB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_C9B3EAE6FB88E14F ON personnalisation (utilisateur_id)');
        $this->addSql('ALTER TABLE utilisateur DROP FOREIGN KEY FK_1D1C63B33DD790BB');
        $this->addSql('ALTER TABLE utilisateur CHANGE personnalisation_id personnalisation_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE utilisateur ADD CONSTRAINT FK_1D1C63B33DD790BB FOREIGN KEY (personnalisation_id) REFERENCES personnalisation (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE personnalisation DROP FOREIGN KEY FK_C9B3EAE6FB88E14F');
        $this->addSql('DROP INDEX UNIQ_C9B3EAE6FB88E14F ON personnalisation');
        $this->addSql('ALTER TABLE personnalisation DROP utilisateur_id');
        $this->addSql('ALTER TABLE utilisateur DROP FOREIGN KEY FK_1D1C63B33DD790BB');
        $this->addSql('ALTER TABLE utilisateur CHANGE personnalisation_id personnalisation_id INT NOT NULL');
        $this->addSql('ALTER TABLE utilisateur ADD CONSTRAINT FK_1D1C63B33DD790BB FOREIGN KEY (personnalisation_id) REFERENCES personnalisation (id) ON DELETE CASCADE');
    }
}
