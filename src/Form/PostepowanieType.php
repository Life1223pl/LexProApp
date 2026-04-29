<?php

namespace App\Form;

use App\Entity\Postepowanie;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PostepowanieType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('numer', null, [
                'label' => 'Numer postępowania',
                'disabled' => true,
                'attr' => ['class' => 'form-control'],
            ])

            ->add('rodzaj', ChoiceType::class, [
                'label' => 'Rodzaj postępowania',
                'choices' => [
                    'Dochodzenie' => 'dochodzenie',
                    'Śledztwo' => 'sledztwo',
                    'Postępowanie o wykroczenie' => 'wykroczenie',
                ],
                'placeholder' => '— wybierz —',
                'required' => true,
                'attr' => ['class' => 'form-select'],
            ])

            ->add('dataWszczecia', DateType::class, [
                'label' => 'Data wszczęcia',
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control'],
            ])

            ->add('dataZakonczenia', DateType::class, [
                'label' => 'Data zakończenia',
                'widget' => 'single_text',
                'required' => false,
                'attr' => ['class' => 'form-control'],
            ])

            ->add('opis', TextareaType::class, [
                'label' => 'Opis postępowania',
                'required' => false,
                'attr' => [
                    'rows' => 4,
                    'class' => 'form-control'
                ],
            ])

            ->add('glownyArtykulSprawy', TextType::class, [
                'label' => 'Główny artykuł sprawy',
                'required' => false,
                'attr' => ['class' => 'form-control'],
            ])

            // NOWY PRZYCISK
            ->add('save', SubmitType::class, [
                'label' => 'Zapisz zmiany',
                'attr' => [
                    'class' => 'btn btn-primary'
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Postepowanie::class,
        ]);
    }
}
