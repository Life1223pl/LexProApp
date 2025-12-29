<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251212225811 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE postepowanie DROP FOREIGN KEY `FK_A28545F62D234F6A`');
        $this->addSql('ALTER TABLE postepowanie ADD approved_at DATETIME DEFAULT NULL, CHANGE data_zakonczenia data_zakonczenia DATE DEFAULT NULL, CHANGE approved_by_id approved_by_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE postepowanie ADD CONSTRAINT FK_A28545F62D234F6A FOREIGN KEY (approved_by_id) REFERENCES pracownik (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE postepowanie DROP FOREIGN KEY FK_A28545F62D234F6A');
        $this->addSql('ALTER TABLE postepowanie DROP approved_at, CHANGE data_zakonczenia data_zakonczenia DATE NOT NULL, CHANGE approved_by_id approved_by_id INT NOT NULL');
        $this->addSql('ALTER TABLE postepowanie ADD CONSTRAINT `FK_A28545F62D234F6A` FOREIGN KEY (approved_by_id) REFERENCES pracownik (id)');
    }
}
