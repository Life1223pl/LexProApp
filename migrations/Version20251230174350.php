<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251230174350 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE czynnosc (id INT AUTO_INCREMENT NOT NULL, typ VARCHAR(60) NOT NULL, data_start DATETIME DEFAULT NULL, data_koniec DATETIME DEFAULT NULL, miejsce_opis LONGTEXT DEFAULT NULL, podstawa_prawna LONGTEXT DEFAULT NULL, tresc LONGTEXT DEFAULT NULL, zalaczniki_opis LONGTEXT DEFAULT NULL, rejestrowana TINYINT DEFAULT 0 NOT NULL, rejestracja_opis LONGTEXT DEFAULT NULL, operator_rejestracji_opis LONGTEXT DEFAULT NULL, spis_rzeczy JSON DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, miejsce_ulica VARCHAR(120) DEFAULT NULL, miejsce_nr_domu VARCHAR(20) DEFAULT NULL, miejsce_nr_lokalu VARCHAR(20) DEFAULT NULL, miejsce_kod_pocztowy VARCHAR(10) DEFAULT NULL, miejsce_miejscowosc VARCHAR(80) DEFAULT NULL, miejsce_kraj VARCHAR(80) DEFAULT NULL, postepowanie_id INT NOT NULL, INDEX IDX_31AA748BC7D4061F (postepowanie_id), INDEX idx_czynnosc_typ (typ), INDEX idx_czynnosc_data_start (data_start), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE czynnosc ADD CONSTRAINT FK_31AA748BC7D4061F FOREIGN KEY (postepowanie_id) REFERENCES postepowanie (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE czynnosc DROP FOREIGN KEY FK_31AA748BC7D4061F');
        $this->addSql('DROP TABLE czynnosc');
    }
}
