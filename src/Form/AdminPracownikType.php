<?php

namespace App\Form;

use App\Entity\Pracownik;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AdminPracownikType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('imie')
            ->add('nazwisko')
            ->add('email')

            ->add('stopien', null, [
                'required' => false,
            ])

            ->add('funkcja', null, [
                'required' => false,
            ])

            ->add('miejsceZatrudnienia', TextType::class, [
                'label' => 'Miejsce zatrudnienia',
                'required' => false,
            ])

            ->add('przelozony', EntityType::class, [
                'class' => Pracownik::class,
                'choice_label' => fn(Pracownik $p) =>
                    $p->getImie().' '.$p->getNazwisko().' ('.$p->getEmail().')',
                'required' => false,
                'placeholder' => '— brak przełożonego —',
            ])

            ->add('isActive', CheckboxType::class, [
                'required' => false,
                'label' => 'Konto aktywne',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Pracownik::class,
        ]);
    }
}
