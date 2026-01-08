<?php

namespace App\Form;

use App\Entity\Czynnosc;
use App\Entity\Postepowanie;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Entity\PostepowanieOsoba;
use App\Repository\PostepowanieOsobaRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;



class CzynnoscType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Postepowanie|null $postepowanie */
        $postepowanie = $options['postepowanie'] ?? null;

        $typy = Czynnosc::getDostepneTypy();
        $choicesTypy = array_combine($typy, $typy);

        $builder
            ->add('typ', ChoiceType::class, [
                'label' => 'Typ czynności',
                'choices' => $choicesTypy,
            ])
            ->add('glownaOsoba', EntityType::class, [
                'label' => 'Główna osoba do czynności',
                'class' => PostepowanieOsoba::class,
                'required' => false,
                'placeholder' => '— wybierz osobę z postępowania —',
                'choice_label' => function (PostepowanieOsoba $po) {
                    $o = $po->getOsoba();
                    $imie = $o?->getImie() ?? '';
                    $nazw = $o?->getNazwisko() ?? '';
                    $pesel = $o?->getPesel() ?? '';
                    $label = trim($imie.' '.$nazw);
                    if ($pesel !== '') $label .= ' ('.$pesel.')';
                    return $label;
                },
                'query_builder' => function (PostepowanieOsobaRepository $r) use ($postepowanie) {
                    $qb = $r->createQueryBuilder('po')
                        ->leftJoin('po.osoba', 'o')->addSelect('o')
                        ->orderBy('o.nazwisko', 'ASC')
                        ->addOrderBy('o.imie', 'ASC');

                    if ($postepowanie) {
                        $qb->andWhere('po.postepowanie = :p')->setParameter('p', $postepowanie);
                    } else {
                        $qb->andWhere('1=0');
                    }

                    return $qb;
                },
            ])



            ->add('dataStart', DateTimeType::class, [
                'label' => 'Data i godzina rozpoczęcia',
                'required' => false,
                'widget' => 'single_text',
            ])
            ->add('dataKoniec', DateTimeType::class, [
                'label' => 'Data i godzina zakończenia',
                'required' => false,
                'widget' => 'single_text',
            ])

            ->add('miejsceOpis', TextareaType::class, [
                'label' => 'Miejsce czynności (opis)',
                'required' => false,
                'attr' => ['rows' => 2],
            ])

            ->add('miejsceAdres', AdresType::class, [
                'label' => false,
                'required' => false,
                'required_main' => false,
            ])

            ->add('podstawaPrawna', TextareaType::class, [
                'label' => 'Podstawa prawna',
                'required' => false,
                'attr' => ['rows' => 2],
            ])

            ->add('tresc', TextareaType::class, [
                'label' => 'Treść / przebieg czynności',
                'required' => false,
                'attr' => ['rows' => 6],
            ])

            ->add('zalacznikiOpis', TextareaType::class, [
                'label' => 'Załączniki (opis)',
                'required' => false,
                'attr' => ['rows' => 2],
            ])

            ->add('rejestrowana', CheckboxType::class, [
                'label' => 'Rejestrowana (A/V)',
                'required' => false,
            ])
            ->add('rejestracjaOpis', TextareaType::class, [
                'label' => 'Opis rejestracji (ogólnie)',
                'required' => false,
                'attr' => ['rows' => 2],
                'help' => 'Np. numer nośnika, krótki opis urządzenia itp.',
            ])
            ->add('operatorRejestracjiOpis', TextType::class, [
                'label' => 'Operator rejestracji (ogólnie)',
                'required' => false,
            ])

            // ===== DANE SZCZEGÓŁOWE DLA PROTOKOŁU PRZESZUKANIA (JSON: dane[...]) =====
            // Uwaga: pola są mapped=false i zapisujemy je eventem do Czynnosc->dane (JSON)
            ->add('dane_podstawaDokument', TextType::class, [
                'label' => 'Podstawa dokumentu (np. postanowienie/nakaz: numer, data)',
                'required' => false,
                'mapped' => false,
            ])
            ->add('dane_przeszukiwanyOpis', TextareaType::class, [
                'label' => 'Czego dotyczy przeszukanie (opis)',
                'required' => false,
                'mapped' => false,
                'attr' => ['rows' => 3],
            ])
            ->add('dane_uwagiOsob', TextareaType::class, [
                'label' => 'Uwagi osoby/osób obecnych',
                'required' => false,
                'mapped' => false,
                'attr' => ['rows' => 3],
            ])
            ->add('dane_rejRodzaj', ChoiceType::class, [
                'label' => 'Rejestracja (rodzaj) — szczegóły do protokołu',
                'required' => false,
                'mapped' => false,
                'placeholder' => '— brak / nie dotyczy —',
                'choices' => [
                    'Audio' => 'AUDIO',
                    'Wideo' => 'WIDEO',
                    'Audio + Wideo' => 'AUDIO_WIDEO',
                ],
            ])
            ->add('dane_rejUrzadzenie', TextType::class, [
                'label' => 'Urządzenie rejestrujące (szczegóły)',
                'required' => false,
                'mapped' => false,
            ])
            ->add('dane_rejNosnik', TextType::class, [
                'label' => 'Nośnik (szczegóły)',
                'required' => false,
                'mapped' => false,
            ])
            ->add('dane_rejOperator', TextType::class, [
                'label' => 'Kto obsługiwał rejestrację (operator — szczegóły)',
                'required' => false,
                'mapped' => false,
            ])

            ->add('uczestnicy', CollectionType::class, [
                'label' => false,
                'entry_type' => CzynnoscUczestnikType::class,
                'entry_options' => [
                    'postepowanie' => $postepowanie,
                ],
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'prototype' => true,
            ])

            // ===== NOWE OGÓLNE POLA DO PROTOKOŁÓW (JSON: dane[...]) =====
            ->add('oswiadczenie_osoby', TextareaType::class, [
                'label' => 'Oświadczenie osoby',
                'required' => false,
                'mapped' => false,
                'attr' => ['rows' => 3],
            ])
            ->add('zastana_osoba', TextareaType::class, [
                'label' => 'Zastana osoba (opis)',
                'required' => false,
                'mapped' => false,
                'attr' => ['rows' => 2],
            ])
            ->add('dopuszczona_osoba', TextareaType::class, [
                'label' => 'Dopuszczona osoba (opis)',
                'required' => false,
                'mapped' => false,
                'attr' => ['rows' => 2],
            ])
            ->add('tresc_wezwania', TextareaType::class, [
                'label' => 'Treść wezwania',
                'required' => false,
                'mapped' => false,
                'attr' => ['rows' => 3],
            ])
            ->add('oswiadczenie_pozostalych_osob', TextareaType::class, [
                'label' => 'Oświadczenie pozostałych osób',
                'required' => false,
                'mapped' => false,
                'attr' => ['rows' => 3],
            ])
        ;

        // WYPEŁNIANIE FORMULARZA Z JSON (POST_SET_DATA)
        $builder->addEventListener(FormEvents::POST_SET_DATA, function (FormEvent $event) {
            $cz = $event->getData();
            $form = $event->getForm();
            if (!$cz) return;

            if (!method_exists($cz, 'getDane')) return;
            $d = $cz->getDane();

            // Sekcja "dane_*"  — klucze bez prefiksu "dane_"
            if ($form->has('dane_podstawaDokument')) {
                $form->get('dane_podstawaDokument')->setData($d['podstawaDokument'] ?? null);
            }
            if ($form->has('dane_przeszukiwanyOpis')) {
                $form->get('dane_przeszukiwanyOpis')->setData($d['przeszukiwanyOpis'] ?? null);
            }
            if ($form->has('dane_uwagiOsob')) {
                $form->get('dane_uwagiOsob')->setData($d['uwagiOsob'] ?? null);
            }

            $rej = is_array($d['rejestracja'] ?? null) ? $d['rejestracja'] : [];
            if ($form->has('dane_rejRodzaj')) {
                $form->get('dane_rejRodzaj')->setData($rej['rodzaj'] ?? null);
            }
            if ($form->has('dane_rejUrzadzenie')) {
                $form->get('dane_rejUrzadzenie')->setData($rej['urzadzenie'] ?? null);
            }
            if ($form->has('dane_rejNosnik')) {
                $form->get('dane_rejNosnik')->setData($rej['nosnik'] ?? null);
            }
            if ($form->has('dane_rejOperator')) {
                $form->get('dane_rejOperator')->setData($rej['operator'] ?? null);
            }

            if ($form->has('oswiadczenie_osoby')) {
                $form->get('oswiadczenie_osoby')->setData($d['oswiadczenie_osoby'] ?? null);
            }
            if ($form->has('zastana_osoba')) {
                $form->get('zastana_osoba')->setData($d['zastana_osoba'] ?? null);
            }
            if ($form->has('dopuszczona_osoba')) {
                $form->get('dopuszczona_osoba')->setData($d['dopuszczona_osoba'] ?? null);
            }
            if ($form->has('tresc_wezwania')) {
                $form->get('tresc_wezwania')->setData($d['tresc_wezwania'] ?? null);
            }
            if ($form->has('oswiadczenie_pozostalych_osob')) {
                $form->get('oswiadczenie_pozostalych_osob')->setData($d['oswiadczenie_pozostalych_osob'] ?? null);
            }
        });

        // ZAPIS DO JSON (SUBMIT)
        $builder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event) {
            $cz = $event->getData();
            $form = $event->getForm();
            if (!$cz) return;

            if (!method_exists($cz, 'getTyp') || !method_exists($cz, 'getDane') || !method_exists($cz, 'setDane')) {
                return;
            }

            $d = $cz->getDane();


            if ($form->has('oswiadczenie_osoby')) {
                $d['oswiadczenie_osoby'] = $form->get('oswiadczenie_osoby')->getData();
            }
            if ($form->has('zastana_osoba')) {
                $d['zastana_osoba'] = $form->get('zastana_osoba')->getData();
            }
            if ($form->has('dopuszczona_osoba')) {
                $d['dopuszczona_osoba'] = $form->get('dopuszczona_osoba')->getData();
            }
            if ($form->has('tresc_wezwania')) {
                $d['tresc_wezwania'] = $form->get('tresc_wezwania')->getData();
            }
            if ($form->has('oswiadczenie_pozostalych_osob')) {
                $d['oswiadczenie_pozostalych_osob'] = $form->get('oswiadczenie_pozostalych_osob')->getData();
            }

            // ✅ STARE szczegóły zapisujemy tylko dla PRZESZUKANIE (tak jak miałeś)
            if ($cz->getTyp() === Czynnosc::TYP_PRZESZUKANIE) {
                $d['podstawaDokument'] = $form->get('dane_podstawaDokument')->getData();
                $d['przeszukiwanyOpis'] = $form->get('dane_przeszukiwanyOpis')->getData();
                $d['uwagiOsob'] = $form->get('dane_uwagiOsob')->getData();

                $d['rejestracja'] = [
                    'rodzaj' => $form->get('dane_rejRodzaj')->getData(),
                    'urzadzenie' => $form->get('dane_rejUrzadzenie')->getData(),
                    'nosnik' => $form->get('dane_rejNosnik')->getData(),
                    'operator' => $form->get('dane_rejOperator')->getData(),
                ];
            }

            $cz->setDane($d);
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Czynnosc::class,
            'postepowanie' => null,
        ]);

        $resolver->setAllowedTypes('postepowanie', ['null', Postepowanie::class]);
    }
}
