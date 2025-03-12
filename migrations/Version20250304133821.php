<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250304133821 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE commande ADD adresse_livraison_nom VARCHAR(100) NOT NULL, ADD adresse_livraison_prenom VARCHAR(100) NOT NULL, ADD adresse_livraison_adresse VARCHAR(100) NOT NULL, ADD adresse_livraison_ville VARCHAR(100) NOT NULL, ADD adresse_livraison_code_postal INT NOT NULL, ADD adresse_livraison_tel INT NOT NULL, ADD adresse_facturation_nom VARCHAR(100) NOT NULL, ADD adresse_facturation_prenom VARCHAR(100) NOT NULL, ADD adresse_facturation_adresse VARCHAR(100) NOT NULL, ADD adresse_facturation_ville VARCHAR(100) NOT NULL, ADD adresse_facturation_code_postal INT NOT NULL, ADD adresse_facturation_tel INT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE commande DROP adresse_livraison_nom, DROP adresse_livraison_prenom, DROP adresse_livraison_adresse, DROP adresse_livraison_ville, DROP adresse_livraison_code_postal, DROP adresse_livraison_tel, DROP adresse_facturation_nom, DROP adresse_facturation_prenom, DROP adresse_facturation_adresse, DROP adresse_facturation_ville, DROP adresse_facturation_code_postal, DROP adresse_facturation_tel');
    }
}
