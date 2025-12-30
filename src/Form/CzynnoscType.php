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
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class CzynnoscType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Postepowanie $postepowanie */
        $postepowanie = $options['postepowanie'];

        $builder
            ->add('typ', ChoiceType::class, [
                'label' => 'Typ czynności',
                'choices' => array_combine(
                    Czynnosc::getDostepneTypy(),
                    Czynnosc::getDostepneTypy()
                ),
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
                'attr' => [
                    'rows' => 2,
                ],
            ])
            ->add('miejsceAdres', AdresType::class, [
                'label' => false,
                'required' => false,
                'required_main' => false, // <<< ważne dla Twojego AdresType
            ])
            ->add('podstawaPrawna', TextareaType::class, [
                'label' => 'Podstawa prawna (opcjonalnie)',
                'required' => false,
                'attr' => [
                    'rows' => 2,
                ],
            ])
            ->add('rejestrowana', CheckboxType::class, [
                'label' => 'Czynność była rejestrowana (A/V)',
                'required' => false,
            ])
            ->add('rejestracjaOpis', TextareaType::class, [
                'label' => 'Opis urządzenia / nośnika',
                'required' => false,
                'attr' => [
                    'rows' => 2,
                ],
            ])
            ->add('operatorRejestracjiOpis', TextareaType::class, [
                'label' => 'Operator rejestracji',
                'required' => false,
                'attr' => [
                    'rows' => 2,
                ],
            ])
            ->add('zalacznikiOpis', TextareaType::class, [
                'label' => 'Załączniki (np. „1 x spis i opis rzeczy”)',
                'required' => false,
                'attr' => [
                    'rows' => 3,
                ],
            ])
            ->add('tresc', TextareaType::class, [
                'label' => 'Treść / przebieg czynności',
                'required' => false,
                'attr' => [
                    'rows' => 8,
                ],
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
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Czynnosc::class,
        ]);

        $resolver->setRequired(['postepowanie']);
        $resolver->setAllowedTypes('postepowanie', Postepowanie::class);
    }
}
