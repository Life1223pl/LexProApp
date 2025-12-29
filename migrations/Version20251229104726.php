<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251229104726 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE postepowanie ADD delete_requested_at DATETIME DEFAULT NULL, ADD delete_approved_at DATETIME DEFAULT NULL, ADD delete_requested_by_id INT DEFAULT NULL, ADD delete_approved_by_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE postepowanie ADD CONSTRAINT FK_A28545F6682D15BC FOREIGN KEY (delete_requested_by_id) REFERENCES pracownik (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE postepowanie ADD CONSTRAINT FK_A28545F6CE0302D8 FOREIGN KEY (delete_approved_by_id) REFERENCES pracownik (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_A28545F6682D15BC ON postepowanie (delete_requested_by_id)');
        $this->addSql('CREATE INDEX IDX_A28545F6CE0302D8 ON postepowanie (delete_approved_by_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE postepowanie DROP FOREIGN KEY FK_A28545F6682D15BC');
        $this->addSql('ALTER TABLE postepowanie DROP FOREIGN KEY FK_A28545F6CE0302D8');
        $this->addSql('DROP INDEX IDX_A28545F6682D15BC ON postepowanie');
        $this->addSql('DROP INDEX IDX_A28545F6CE0302D8 ON postepowanie');
        $this->addSql('ALTER TABLE postepowanie DROP delete_requested_at, DROP delete_approved_at, DROP delete_requested_by_id, DROP delete_approved_by_id');
    }
}
