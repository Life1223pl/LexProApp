<?php

namespace App\Service;

use App\Entity\Czynnosc;
use PhpOffice\PhpWord\TemplateProcessor;

final class ProtokolGenerator
{
    public function generate(Czynnosc $czynnosc, string $projectDir): string
    {
        $templatePath = $this->resolveTemplatePath($czynnosc, $projectDir);
        $tp = new TemplateProcessor($templatePath);

        $post = $czynnosc->getPostepowanie();

        $osoba = null;
        if (method_exists($czynnosc, 'getGlownaOsoba') && $czynnosc->getGlownaOsoba()) {
            $osoba = method_exists($czynnosc->getGlownaOsoba(), 'getOsoba')
                ? $czynnosc->getGlownaOsoba()->getOsoba()
                : null;
        }

        $this->setOsobaAllPlaceholders($tp, $osoba);

        // BASIC
        $tp->setValue('POSTEPOWANIE_NUMER', (string)($post->getNumer() ?? ''));
        $tp->setValue('POSTEPOWANIE_RODZAJ', (string)($post->getRodzaj() ?? ''));
        $tp->setValue('TYP_CZYNNOSCI', (string)($czynnosc->getTyp() ?? ''));
        $tp->setValue('PROWADZACY_MIEJSCE_ZATRUDNIENIA',
            (string)($post->getProwadzacy()?->getMiejsceZatrudnienia() ?? ''));
        $tp->setValue('POSTEPOWANIE_OPIS', (string)($post->getOpis() ?? ''));
        $tp->setValue('POSTEPOWANIE_GLOWNY_ARTYKUL', (string)($post->getGlownyArtykulSprawy() ?? ''));


        // DATE/TIME
        $tp->setValue('DATA_START', $czynnosc->getDataStart() ? $czynnosc->getDataStart()->format('Y-m-d H:i') : '');
        $tp->setValue('DATA_KONIEC', $czynnosc->getDataKoniec() ? $czynnosc->getDataKoniec()->format('Y-m-d H:i') : '');

        // PLACE
        $tp->setValue('MIEJSCE', (string)($czynnosc->getMiejsceOpis() ?? ''));

        //  UNIT / LEAD
        $tp->setValue('JEDNOSTKA_PROWADZACA', (string)($post->getRodzaj() ?? ''));
        $tp->setValue('PROWADZACY', $this->formatProwadzacy($post->getProwadzacy()));

        // PARTICIPANTS
        $tp->setValue('UCZESTNICY', $this->formatUczestnicy($czynnosc));

        //  SPIS
        $tp->setValue('SPIS_RZECZY', $this->formatSpisRzeczy($czynnosc));

        // NARRATIVE / ATTACHMENTS
        $tp->setValue('TRESC', (string)($czynnosc->getTresc() ?? ''));
        $tp->setValue('ZALACZNIKI', (string)($czynnosc->getZalacznikiOpis() ?? ''));

        // JSON DANE (dla PRZESZUKANIA i potem innych typów)
        $d = method_exists($czynnosc, 'getDane') ? $czynnosc->getDane() : [];

        $tp->setValue('PODSTAWA_DOKUMENT', (string)($d['podstawaDokument'] ?? ''));
        $tp->setValue('PRZESZUKIWANY_OPIS', (string)($d['przeszukiwanyOpis'] ?? ''));
        $tp->setValue('UWAGI_OSOB', (string)($d['uwagiOsob'] ?? ''));

        // dodatkowe dane
        $tp->setValue('OSWIADCZENIE_OSOBY', (string) $czynnosc->getDaneValue('oswiadczenie_osoby', ''));
        $tp->setValue('ZASTANA_OSOBA', (string) $czynnosc->getDaneValue('zastana_osoba', ''));
        $tp->setValue('DOPUSZCZONA_OSOBA', (string) $czynnosc->getDaneValue('dopuszczona_osoba', ''));
        $tp->setValue('TRESC_WEZWANIA', (string) $czynnosc->getDaneValue('tresc_wezwania', ''));
        $tp->setValue('OSWIADCZENIE_POZOSTALYCH_OSOB', (string) $czynnosc->getDaneValue('oswiadczenie_pozostalych_osob', ''));


        // rejestracja: bierzemy z JSON
        $rej = is_array($d['rejestracja'] ?? null) ? $d['rejestracja'] : [];



        if (!$czynnosc->isRejestrowana()) {
            $rej = [];
        }

        $tp->setValue('REJ_RODZAJ', (string)($rej['rodzaj'] ?? ''));
        $tp->setValue('REJ_URZADZENIE', (string)($rej['urzadzenie'] ?? ''));
        $tp->setValue('REJ_NOSNIK', (string)($rej['nosnik'] ?? ''));
        $tp->setValue('REJ_OPERATOR', (string)($rej['operator'] ?? ''));

        //  OUTPUT
        $outDir = $projectDir . '/var/protokoly';
        if (!is_dir($outDir)) {
            mkdir($outDir, 0775, true);
        }

        $safeTyp = preg_replace('/[^A-Z0-9_]+/', '_', (string)$czynnosc->getTyp());
        $filename = sprintf(
            'PROTOKOL_%s_%d_%s.docx',
            $safeTyp,
            (int)$czynnosc->getId(),
            (new \DateTimeImmutable())->format('Ymd_His')
        );

        $outPath = $outDir . '/' . $filename;
        $tp->saveAs($outPath);

        return $outPath;
    }

    private function resolveTemplatePath(Czynnosc $czynnosc, string $projectDir): string
    {
        $map = [
            // PRZESZUKANIA
            Czynnosc::TYP_PRZESZUKANIE
            => 'Protokol_przeszukania_TEMPLATE_placeholders.docx',

            Czynnosc::TYP_PRZESZUKANIE_OSOBY
            => 'Protokol_przeszukania_osoby_TEMPLATE_placeholders.docx',

            Czynnosc::TYP_PRZESZUKANIE_URZADZENIA
            => 'Protokol_przeszukania_urzadzenia_TEMPLATE_placeholders.docx',

            //  OGLĘDZINY
            Czynnosc::TYP_OGLEDZINY
            => 'Protokol_ogledzin_TEMPLATE_placeholders.docx',

            // ZATRZYMANIA
            Czynnosc::TYP_ZATRZYMANIE_RZECZY
            => 'Protokol_zatrzymania_rzeczy_TEMPLATE_placeholders.docx',

            Czynnosc::TYP_ZATRZYMANIE_OSOBY
            => 'Protokol_zatrzymania_osoby_TEMPLATE_placeholders.docx',

            // PRZESŁUCHANIA
            Czynnosc::TYP_PRZESLUCHANIE_SWIADKA
            => 'Protokol_przesluchania_swiadka_TEMPLATE_placeholders.docx',

            Czynnosc::TYP_PRZESLUCHANIE_PODEJRZANEGO
            => 'Protokol_przesluchania_podejrzanego_TEMPLATE_placeholders.docx',

            //  INNE
            Czynnosc::TYP_KONFRONTACJA
            => 'Protokol_konfrontacji_TEMPLATE_placeholders.docx',

            Czynnosc::TYP_POBRANIE_MATERIALU
            => 'Protokol_pobrania_materialu_porownawczego_TEMPLATE_placeholders.docx',

            Czynnosc::TYP_ZAZNAJOMIENIE_Z_AKTAMI
            => 'Protokol_zaznajomienia_z_materialami_dochodzenia_TEMPLATE_placeholders.docx',

            Czynnosc::TYP_TESTER_NARKOTYKOWY
            => 'Protokol_uzycia_testera_narkotykowego_TEMPLATE_placeholders.docx',

            //  FALLBACK
            Czynnosc::TYP_INNA
            => 'Protokol_inna_TEMPLATE_placeholders.docx',

            // zawiadomienie

            Czynnosc::TYP_ZAWIADOMIENIE
            => 'Protokol_przyjecia_ustnego_zawiadomienia_o_przestepstwie_TEMPLATE_placeholders.docx',
        ];

        $file = $map[$czynnosc->getTyp()] ?? null;

        if (!$file) {
            throw new \RuntimeException('Brak szablonu DOCX dla typu: ' . $czynnosc->getTyp());
        }

        $path = $projectDir . '/templates/docx/' . $file;

        if (!is_file($path)) {
            throw new \RuntimeException('Nie znaleziono pliku szablonu: ' . $path);
        }

        return $path;
    }


    private function formatProwadzacy($pracownik): string
    {
        if (!$pracownik) return '';

        $stopien = method_exists($pracownik, 'getStopien') ? ($pracownik->getStopien() ?? '') : '';
        $imie = method_exists($pracownik, 'getImie') ? ($pracownik->getImie() ?? '') : '';
        $nazwisko = method_exists($pracownik, 'getNazwisko') ? ($pracownik->getNazwisko() ?? '') : '';

        return trim($stopien . ' ' . $imie . ' ' . $nazwisko);
    }

    private function formatUczestnicy(Czynnosc $czynnosc): string
    {
        $lines = [];
        $i = 1;

        foreach ($czynnosc->getUczestnicy() as $u) {
            $rola = (string)($u->getRola() ?? '');

            $label = '';
            if ($u->getPracownik()) {
                $p = $u->getPracownik();
                $label = trim(($p->getStopien() ?? '') . ' ' . ($p->getImie() ?? '') . ' ' . ($p->getNazwisko() ?? ''));
            } elseif ($u->getOsoba()) {
                $o = $u->getOsoba();
                $label = trim(($o->getImie() ?? '') . ' ' . ($o->getNazwisko() ?? ''));
            }

            $opis = trim((string)($u->getOpisRoli() ?? ''));
            $suffix = $opis !== '' ? ' — ' . $opis : '';

            $lines[] = sprintf('%d) %s: %s%s', $i, $rola, $label, $suffix);
            $i++;
        }

        return implode("\n", $lines);
    }

    private function formatSpisRzeczy(Czynnosc $czynnosc): string
    {
        if (!$czynnosc->isSpisRzeczyDozwolony()) {
            return '';
        }

        $spis = $czynnosc->getSpisRzeczy();
        if (!is_array($spis) || count($spis) === 0) {
            return '';
        }

        $lines = [];
        $i = 1;

        foreach ($spis as $item) {
            if (!is_array($item)) continue;

            $nazwa = trim((string)($item['nazwa'] ?? ''));
            $opis  = trim((string)($item['opis'] ?? ''));
            $kat   = trim((string)($item['kategoria'] ?? ''));

            $parts = [];
            if ($nazwa !== '') $parts[] = $nazwa;
            if ($kat !== '') $parts[] = '(' . $kat . ')';
            if ($opis !== '') $parts[] = $opis;

            $line = trim(implode(' ', $parts));
            if ($line === '') continue;

            $lines[] = sprintf('%d. %s', $i, $line);
            $i++;
        }

        return implode("\n", $lines);
    }
    private function setOsobaAllPlaceholders(TemplateProcessor $tp, ?\App\Entity\Osoba $o, string $prefix = 'OSOBA'): void
    {
        //  Dane podstawowe
        $tp->setValue($prefix.'_ID', (string)($o?->getId() ?? ''));

        $tp->setValue($prefix.'_IMIE', (string)($o?->getImie() ?? ''));
        $tp->setValue($prefix.'_DRUGIE_IMIE', (string)($o?->getDrugieImie() ?? ''));
        $tp->setValue($prefix.'_NAZWISKO', (string)($o?->getNazwisko() ?? ''));
        $tp->setValue($prefix.'_NAZWISKO_RODOWE', (string)($o?->getNazwiskoRodowe() ?? ''));

        $tp->setValue($prefix.'_IMIE_OJCA', (string)($o?->getImieOjca() ?? ''));
        $tp->setValue($prefix.'_IMIE_MATKI', (string)($o?->getImieMatki() ?? ''));
        $tp->setValue($prefix.'_NAZWISKO_RODOWE_MATKI', (string)($o?->getNazwiskoRodoweMatki() ?? ''));

        $tp->setValue($prefix.'_PESEL', (string)($o?->getPesel() ?? ''));
        $tp->setValue($prefix.'_NUMER_DOKUMENTU', (string)($o?->getNumerDokumentu() ?? ''));

        $tp->setValue($prefix.'_DATA_URODZENIA', $o?->getDataUrodzenia()?->format('Y-m-d') ?? '');
        $tp->setValue($prefix.'_MIEJSCE_URODZENIA', (string)($o?->getMiejsceUrodzenia() ?? ''));
        $tp->setValue($prefix.'_PLEC', (string)($o?->getPlec() ?? ''));

        // Obywatelstwo
        $tp->setValue($prefix.'_OBYWATELSTWO_GLOWNE', (string)($o?->getObywatelstwoGl() ?? ''));
        $tp->setValue($prefix.'_OBYWATELSTWO_DODATKOWE', (string)($o?->getObywatelstwoDodatkowe() ?? ''));

        //  Kontakt
        $tp->setValue($prefix.'_TELEFON', (string)($o?->getTelefon() ?? ''));
        $tp->setValue($prefix.'_EMAIL', (string)($o?->getEmail() ?? ''));

        //  Adresy (Embeddable Adres)
        $this->setAdresAllPlaceholders($tp, $o?->getAdresZamieszkania(), $prefix.'_ZAM');
        $this->setAdresAllPlaceholders($tp, $o?->getAdresZameldowania(), $prefix.'_ZAMEL');
        $this->setAdresAllPlaceholders($tp, $o?->getAdresKorespondencyjny(), $prefix.'_KOR');

        //  Pozostałe
        $tp->setValue($prefix.'_WYKSZTALCENIE', (string)($o?->getWyksztalcenie() ?? ''));
        $tp->setValue($prefix.'_STAN_CYWILNY', (string)($o?->getStanCywilny() ?? ''));
        $tp->setValue($prefix.'_ZAWOD', (string)($o?->getZawod() ?? ''));
        $tp->setValue($prefix.'_MIEJSCE_PRACY', (string)($o?->getMiejscePracy() ?? ''));
        $tp->setValue($prefix.'_STANOWISKO', (string)($o?->getStanowisko() ?? ''));
        $tp->setValue($prefix.'_NOTATKI', (string)($o?->getNotatki() ?? ''));

        // Daty systemowe
        $tp->setValue($prefix.'_CREATED_AT', $o?->getCreatedAt()?->format('Y-m-d H:i:s') ?? '');
        $tp->setValue($prefix.'_UPDATED_AT', $o?->getUpdatedAt()?->format('Y-m-d H:i:s') ?? '');

        // Pola pomocnicze
        $full = trim(
            (string)($o?->getImie() ?? '') . ' ' .
            (string)($o?->getDrugieImie() ?? '') . ' ' .
            (string)($o?->getNazwisko() ?? '')
        );
        $tp->setValue($prefix.'_IMIE_NAZWISKO', trim(preg_replace('/\s+/', ' ', $full)));
    }

    private function setAdresAllPlaceholders(TemplateProcessor $tp, ?\App\Entity\Embeddable\Adres $a, string $prefix): void
    {
        $ulica = (string)($a?->getUlica() ?? '');
        $nrDomu = (string)($a?->getNrDomu() ?? '');
        $nrLokalu = (string)($a?->getNrLokalu() ?? '');
        $kod = (string)($a?->getKodPocztowy() ?? '');
        $miejscowosc = (string)($a?->getMiejscowosc() ?? '');
        $kraj = (string)($a?->getKraj() ?? '');

        $tp->setValue($prefix.'_ULICA', $ulica);
        $tp->setValue($prefix.'_NR_DOMU', $nrDomu);
        $tp->setValue($prefix.'_NR_LOKALU', $nrLokalu);
        $tp->setValue($prefix.'_KOD_POCZTOWY', $kod);
        $tp->setValue($prefix.'_MIEJSCOWOSC', $miejscowosc);
        $tp->setValue($prefix.'_KRAJ', $kraj);

        // Złożenia pomocnicze
        $nr = trim($nrDomu . ($nrLokalu !== '' ? '/' . $nrLokalu : ''));
        $linia1 = trim($ulica . ' ' . $nr);
        $linia2 = trim($kod . ' ' . $miejscowosc);


        $pelnyParts = array_filter([$linia1, $linia2, $kraj], fn($x) => trim((string)$x) !== '');
        $pelny = trim(implode(', ', $pelnyParts));

        $tp->setValue($prefix.'_NR', $nr);
        $tp->setValue($prefix.'_ADRES_LINIA1', $linia1);
        $tp->setValue($prefix.'_ADRES_LINIA2', $linia2);
        $tp->setValue($prefix.'_ADRES_PELNY', $pelny);


        $tp->setValue($prefix.'_ADRES_TOSTRING', (string)($a ? (string)$a : ''));
    }

}
