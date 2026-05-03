<?php

namespace App\Form;

use App\Entity\Postepowanie;
use App\Entity\Pracownik;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
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

            ->add('prowadzacy', EntityType::class, [
                'class' => Pracownik::class,
                'choice_label' => fn($p) => $p->getImie().' '.$p->getNazwisko(),
                'disabled' => false,
                'attr' => ['class' => 'form-select'],
            ])

            ->add('rodzaj', ChoiceType::class, [
                'label' => 'Rodzaj postępowania',
                'choices' => [
                    'Dochodzenie' => 'dochodzenie',
                    'Śledztwo' => 'sledztwo',
                    'Postępowanie o wykroczenie' => 'wykroczenie',
                ],
                'attr' => ['class' => 'form-select'],
            ])

            ->add('dataWszczecia', DateType::class, [
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control'],
            ])

            ->add('dataZakonczenia', DateType::class, [
                'widget' => 'single_text',
                'required' => false,
                'attr' => ['class' => 'form-control'],
            ])

            ->add('opis', TextareaType::class, [
                'required' => false,
                'attr' => ['class' => 'form-control'],
            ])

            ->add('glownyArtykulSprawy', TextType::class, [
                'required' => false,
                'attr' => ['class' => 'form-control'],
            ])

            ->add('save', SubmitType::class, [
                'label' => 'Zapisz zmiany',
                'attr' => ['class' => 'btn btn-primary']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Postepowanie::class,
            'is_supervisor' => false,
        ]);
    }
}
