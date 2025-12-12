<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251212214615 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE pracownik DROP FOREIGN KEY `FK_9AE28D9EFDA1287B`');
        $this->addSql('ALTER TABLE pracownik CHANGE stopien stopien VARCHAR(50) DEFAULT NULL, CHANGE funkcja funkcja VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE pracownik ADD CONSTRAINT FK_9AE28D9EFDA1287B FOREIGN KEY (przelozony_id) REFERENCES pracownik (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE pracownik DROP FOREIGN KEY FK_9AE28D9EFDA1287B');
        $this->addSql('ALTER TABLE pracownik CHANGE stopien stopien VARCHAR(255) NOT NULL, CHANGE funkcja funkcja VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE pracownik ADD CONSTRAINT `FK_9AE28D9EFDA1287B` FOREIGN KEY (przelozony_id) REFERENCES pracownik (id)');
    }
}
