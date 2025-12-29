<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251229114653 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE osoba (id INT AUTO_INCREMENT NOT NULL, imie VARCHAR(80) DEFAULT NULL, drugie_imie VARCHAR(80) DEFAULT NULL, nazwisko VARCHAR(120) DEFAULT NULL, nazwisko_rodowe VARCHAR(120) DEFAULT NULL, imie_ojca VARCHAR(80) DEFAULT NULL, imie_matki VARCHAR(80) DEFAULT NULL, nazwisko_rodowe_matki VARCHAR(120) DEFAULT NULL, pesel VARCHAR(11) DEFAULT NULL, numer_dokumentu VARCHAR(60) DEFAULT NULL, data_urodzenia DATE DEFAULT NULL, miejsce_urodzenia VARCHAR(120) DEFAULT NULL, plec VARCHAR(1) DEFAULT NULL, obywatelstwo_gl VARCHAR(80) DEFAULT NULL, obywatelstwo_dodatkowe VARCHAR(120) DEFAULT NULL, telefon VARCHAR(30) DEFAULT NULL, email VARCHAR(180) DEFAULT NULL, wyksztalcenie VARCHAR(80) DEFAULT NULL, stan_cywilny VARCHAR(80) DEFAULT NULL, zawod VARCHAR(80) DEFAULT NULL, miejsce_pracy VARCHAR(120) DEFAULT NULL, stanowisko VARCHAR(120) DEFAULT NULL, notatki LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, zam_ulica VARCHAR(120) DEFAULT NULL, zam_nr_domu VARCHAR(20) DEFAULT NULL, zam_nr_lokalu VARCHAR(20) DEFAULT NULL, zam_kod_pocztowy VARCHAR(10) DEFAULT NULL, zam_miejscowosc VARCHAR(80) DEFAULT NULL, zam_kraj VARCHAR(80) DEFAULT NULL, zameld_ulica VARCHAR(120) DEFAULT NULL, zameld_nr_domu VARCHAR(20) DEFAULT NULL, zameld_nr_lokalu VARCHAR(20) DEFAULT NULL, zameld_kod_pocztowy VARCHAR(10) DEFAULT NULL, zameld_miejscowosc VARCHAR(80) DEFAULT NULL, zameld_kraj VARCHAR(80) DEFAULT NULL, kor_ulica VARCHAR(120) DEFAULT NULL, kor_nr_domu VARCHAR(20) DEFAULT NULL, kor_nr_lokalu VARCHAR(20) DEFAULT NULL, kor_kod_pocztowy VARCHAR(10) DEFAULT NULL, kor_miejscowosc VARCHAR(80) DEFAULT NULL, kor_kraj VARCHAR(80) DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE postepowanie_osoba (id INT AUTO_INCREMENT NOT NULL, rola VARCHAR(30) NOT NULL, stosunek_do_podejrzanego LONGTEXT DEFAULT NULL, stosunek_do_pokrzywdzonego LONGTEXT DEFAULT NULL, pouczony_odpowiedzialnosc_karna TINYINT DEFAULT 0 NOT NULL, notatki LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, postepowanie_id INT NOT NULL, osoba_id INT NOT NULL, INDEX IDX_53A4C2A3C7D4061F (postepowanie_id), INDEX IDX_53A4C2A3B1A3B22E (osoba_id), UNIQUE INDEX uniq_post_osoba_rola (postepowanie_id, osoba_id, rola), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE postepowanie_osoba ADD CONSTRAINT FK_53A4C2A3C7D4061F FOREIGN KEY (postepowanie_id) REFERENCES postepowanie (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE postepowanie_osoba ADD CONSTRAINT FK_53A4C2A3B1A3B22E FOREIGN KEY (osoba_id) REFERENCES osoba (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE postepowanie_osoba DROP FOREIGN KEY FK_53A4C2A3C7D4061F');
        $this->addSql('ALTER TABLE postepowanie_osoba DROP FOREIGN KEY FK_53A4C2A3B1A3B22E');
        $this->addSql('DROP TABLE osoba');
        $this->addSql('DROP TABLE postepowanie_osoba');
    }
}
