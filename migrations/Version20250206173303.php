<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250206173303 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE commande (id INT AUTO_INCREMENT NOT NULL, utilisateur_id INT DEFAULT NULL, produit_id INT DEFAULT NULL, date DATETIME NOT NULL, statut VARCHAR(255) NOT NULL, quantite INT NOT NULL, INDEX IDX_6EEAA67DFB88E14F (utilisateur_id), INDEX IDX_6EEAA67DF347EFB (produit_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE commande ADD CONSTRAINT FK_6EEAA67DFB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE commande ADD CONSTRAINT FK_6EEAA67DF347EFB FOREIGN KEY (produit_id) REFERENCES produit (id)');
        $this->addSql('ALTER TABLE commentaire CHANGE date date DATETIME NOT NULL');
        $this->addSql('ALTER TABLE produit ADD stock INT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE commande DROP FOREIGN KEY FK_6EEAA67DFB88E14F');
        $this->addSql('ALTER TABLE commande DROP FOREIGN KEY FK_6EEAA67DF347EFB');
        $this->addSql('DROP TABLE commande');
        $this->addSql('ALTER TABLE commentaire CHANGE date date DATE NOT NULL');
        $this->addSql('ALTER TABLE produit DROP stock');
    }
}
