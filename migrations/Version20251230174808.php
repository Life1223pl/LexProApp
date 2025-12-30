<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251230174808 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE czynnosc_uczestnik (id INT AUTO_INCREMENT NOT NULL, rola VARCHAR(40) NOT NULL, opis_roli LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, czynnosc_id INT NOT NULL, pracownik_id INT DEFAULT NULL, osoba_id INT DEFAULT NULL, INDEX IDX_AFEE93B0F671C764 (czynnosc_id), INDEX IDX_AFEE93B051E53502 (pracownik_id), INDEX IDX_AFEE93B0B1A3B22E (osoba_id), INDEX idx_czynnosc_uczestnik_rola (rola), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE czynnosc_uczestnik ADD CONSTRAINT FK_AFEE93B0F671C764 FOREIGN KEY (czynnosc_id) REFERENCES czynnosc (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE czynnosc_uczestnik ADD CONSTRAINT FK_AFEE93B051E53502 FOREIGN KEY (pracownik_id) REFERENCES pracownik (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE czynnosc_uczestnik ADD CONSTRAINT FK_AFEE93B0B1A3B22E FOREIGN KEY (osoba_id) REFERENCES osoba (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE czynnosc_uczestnik DROP FOREIGN KEY FK_AFEE93B0F671C764');
        $this->addSql('ALTER TABLE czynnosc_uczestnik DROP FOREIGN KEY FK_AFEE93B051E53502');
        $this->addSql('ALTER TABLE czynnosc_uczestnik DROP FOREIGN KEY FK_AFEE93B0B1A3B22E');
        $this->addSql('DROP TABLE czynnosc_uczestnik');
    }
}
