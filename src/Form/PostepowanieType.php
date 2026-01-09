<?php

namespace App\Form;

use App\Entity\Postepowanie;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;


class PostepowanieType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('numer', TextType::class)
            ->add('rodzaj', TextType::class)
            ->add('dataWszczecia', DateType::class, [
                'widget' => 'single_text',
            ])
            ->add('dataZakonczenia', DateType::class, [
                'widget' => 'single_text',
                'required' => false,
            ])
            ->add('opis', TextareaType::class, [
                'label' => 'Opis postępowania',
                'required' => false,
                'attr' => ['rows' => 4],
            ])
            ->add('glownyArtykulSprawy', TextType::class, [
                'label' => 'Główny artykuł sprawy',
                'required' => false,
            ]);


    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Postepowanie::class,
        ]);
    }
}
