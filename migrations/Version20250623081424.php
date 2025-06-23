<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250623081424 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE personnalisation DROP FOREIGN KEY FK_C9B3EAE6FB88E14F');
        $this->addSql('ALTER TABLE personnalisation ADD CONSTRAINT FK_C9B3EAE6FB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE personnalisation DROP FOREIGN KEY FK_C9B3EAE6FB88E14F');
        $this->addSql('ALTER TABLE personnalisation ADD CONSTRAINT FK_C9B3EAE6FB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id) ON DELETE CASCADE');
    }
}
