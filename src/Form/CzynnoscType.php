<?php

namespace App\Form;

use App\Entity\Czynnosc;
use App\Entity\Postepowanie;
use Symfony\Component\Form\AbstractType;
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
                'placeholder' => '— wybierz osobę —',
                'choice_label' => function (PostepowanieOsoba $po) {
                    $o = $po->getOsoba();
                    return trim(($o?->getImie() ?? '') . ' ' . ($o?->getNazwisko() ?? ''));
                },
                'query_builder' => function (PostepowanieOsobaRepository $r) use ($postepowanie) {
                    return $r->createQueryBuilder('po')
                        ->leftJoin('po.osoba', 'o')->addSelect('o')
                        ->andWhere('po.postepowanie = :p')
                        ->setParameter('p', $postepowanie);
                },
            ])

            ->add('dataStart', DateTimeType::class, [
                'widget' => 'single_text',
                'required' => false,
            ])
            ->add('dataKoniec', DateTimeType::class, [
                'widget' => 'single_text',
                'required' => false,
            ])

            ->add('miejsceOpis', TextareaType::class, [
                'required' => false,
            ])

            ->add('miejsceAdres', AdresType::class, [
                'required' => false,
            ])

            ->add('podstawaPrawna', TextareaType::class, [
                'required' => false,
            ])

            ->add('tresc', TextareaType::class, [
                'required' => false,
            ])

            ->add('zalacznikiOpis', TextareaType::class, [
                'required' => false,
            ])

            // ===== JEDYNE POLA REJESTRACJI (działające) =====
            ->add('dane_rejRodzaj', ChoiceType::class, [
                'label' => 'Rejestracja (rodzaj)',
                'required' => false,
                'mapped' => false,
                'choices' => [
                    'Audio' => 'AUDIO',
                    'Wideo' => 'WIDEO',
                    'Audio + Wideo' => 'AUDIO_WIDEO',
                ],
            ])
            ->add('dane_rejUrzadzenie', TextType::class, [
                'label' => 'Urządzenie',
                'required' => false,
                'mapped' => false,
            ])
            ->add('dane_rejNosnik', TextType::class, [
                'label' => 'Nośnik',
                'required' => false,
                'mapped' => false,
            ])
            ->add('dane_rejOperator', TextType::class, [
                'label' => 'Operator',
                'required' => false,
                'mapped' => false,
            ])

            // ===== INNE POLA (nie ruszamy) =====
            ->add('dane_podstawaDokument', TextType::class, [
                'required' => false,
                'mapped' => false,
            ])
            ->add('dane_przeszukiwanyOpis', TextareaType::class, [
                'required' => false,
                'mapped' => false,
            ])
            ->add('dane_uwagiOsob', TextareaType::class, [
                'required' => false,
                'mapped' => false,
            ])

            ->add('oswiadczenie_osoby', TextareaType::class, [
                'required' => false,
                'mapped' => false,
            ])
            ->add('zastana_osoba', TextareaType::class, [
                'required' => false,
                'mapped' => false,
            ])
            ->add('dopuszczona_osoba', TextareaType::class, [
                'required' => false,
                'mapped' => false,
            ])
            ->add('tresc_wezwania', TextareaType::class, [
                'required' => false,
                'mapped' => false,
            ])
            ->add('oswiadczenie_pozostalych_osob', TextareaType::class, [
                'required' => false,
                'mapped' => false,
            ])

            ->add('uczestnicy', CollectionType::class, [
                'entry_type' => CzynnoscUczestnikType::class,
                'entry_options' => ['postepowanie' => $postepowanie],
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
            ])
        ;

        // ===== LOAD =====
        $builder->addEventListener(FormEvents::POST_SET_DATA, function (FormEvent $event) {
            $cz = $event->getData();
            if (!$cz) return;

            $d = $cz->getDane();
            $form = $event->getForm();

            $rej = $d['rejestracja'] ?? [];

            $form->get('dane_rejRodzaj')->setData($rej['rodzaj'] ?? null);
            $form->get('dane_rejUrzadzenie')->setData($rej['urzadzenie'] ?? null);
            $form->get('dane_rejNosnik')->setData($rej['nosnik'] ?? null);
            $form->get('dane_rejOperator')->setData($rej['operator'] ?? null);
        });

        // ===== SAVE =====
        $builder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event) {
            $cz = $event->getData();
            if (!$cz) return;

            $form = $event->getForm();
            $d = $cz->getDane();

            // 🔥 zapis dla każdej czynności
            $d['rejestracja'] = [
                'rodzaj' => $form->get('dane_rejRodzaj')->getData(),
                'urzadzenie' => $form->get('dane_rejUrzadzenie')->getData(),
                'nosnik' => $form->get('dane_rejNosnik')->getData(),
                'operator' => $form->get('dane_rejOperator')->getData(),
            ];

            // tylko dla przeszukania
            if ($cz->getTyp() === Czynnosc::TYP_PRZESZUKANIE) {
                $d['podstawaDokument'] = $form->get('dane_podstawaDokument')->getData();
                $d['przeszukiwanyOpis'] = $form->get('dane_przeszukiwanyOpis')->getData();
                $d['uwagiOsob'] = $form->get('dane_uwagiOsob')->getData();
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
    }
}
