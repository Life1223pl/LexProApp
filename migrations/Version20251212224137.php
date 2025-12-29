<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251212224137 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE postepowanie (id INT AUTO_INCREMENT NOT NULL, numer VARCHAR(50) NOT NULL, rodzaj VARCHAR(100) NOT NULL, data_wszczecia DATE NOT NULL, data_zakonczenia DATE NOT NULL, status VARCHAR(30) NOT NULL, prowadzacy_id INT NOT NULL, approved_by_id INT NOT NULL, UNIQUE INDEX UNIQ_A28545F65A4FB91E (numer), INDEX IDX_A28545F66FE44FE7 (prowadzacy_id), INDEX IDX_A28545F62D234F6A (approved_by_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE postepowanie_pracownik (id INT AUTO_INCREMENT NOT NULL, rola VARCHAR(30) NOT NULL, postepowanie_id INT NOT NULL, pracownik_id INT NOT NULL, INDEX IDX_95C6DFECC7D4061F (postepowanie_id), INDEX IDX_95C6DFEC51E53502 (pracownik_id), UNIQUE INDEX uniq_postepowanie_pracownik (postepowanie_id, pracownik_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE postepowanie ADD CONSTRAINT FK_A28545F66FE44FE7 FOREIGN KEY (prowadzacy_id) REFERENCES pracownik (id)');
        $this->addSql('ALTER TABLE postepowanie ADD CONSTRAINT FK_A28545F62D234F6A FOREIGN KEY (approved_by_id) REFERENCES pracownik (id)');
        $this->addSql('ALTER TABLE postepowanie_pracownik ADD CONSTRAINT FK_95C6DFECC7D4061F FOREIGN KEY (postepowanie_id) REFERENCES postepowanie (id)');
        $this->addSql('ALTER TABLE postepowanie_pracownik ADD CONSTRAINT FK_95C6DFEC51E53502 FOREIGN KEY (pracownik_id) REFERENCES pracownik (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE postepowanie DROP FOREIGN KEY FK_A28545F66FE44FE7');
        $this->addSql('ALTER TABLE postepowanie DROP FOREIGN KEY FK_A28545F62D234F6A');
        $this->addSql('ALTER TABLE postepowanie_pracownik DROP FOREIGN KEY FK_95C6DFECC7D4061F');
        $this->addSql('ALTER TABLE postepowanie_pracownik DROP FOREIGN KEY FK_95C6DFEC51E53502');
        $this->addSql('DROP TABLE postepowanie');
        $this->addSql('DROP TABLE postepowanie_pracownik');
    }
}
