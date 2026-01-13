<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260113154734 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE history_log (id INT AUTO_INCREMENT NOT NULL, action VARCHAR(10) NOT NULL, entity_class VARCHAR(255) NOT NULL, entity_id INT DEFAULT NULL, changes JSON DEFAULT NULL, occurred_at DATETIME NOT NULL, ip VARCHAR(45) DEFAULT NULL, user_agent VARCHAR(255) DEFAULT NULL, postepowanie_id INT DEFAULT NULL, user_id INT DEFAULT NULL, INDEX IDX_6190350AC7D4061F (postepowanie_id), INDEX IDX_6190350AA76ED395 (user_id), INDEX idx_history_postepowanie_time (postepowanie_id, occurred_at), INDEX idx_history_entity (entity_class, entity_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE history_log ADD CONSTRAINT FK_6190350AC7D4061F FOREIGN KEY (postepowanie_id) REFERENCES postepowanie (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE history_log ADD CONSTRAINT FK_6190350AA76ED395 FOREIGN KEY (user_id) REFERENCES pracownik (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE history_log DROP FOREIGN KEY FK_6190350AC7D4061F');
        $this->addSql('ALTER TABLE history_log DROP FOREIGN KEY FK_6190350AA76ED395');
        $this->addSql('DROP TABLE history_log');
    }
}
