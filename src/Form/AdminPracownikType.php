<?php

namespace App\Form;

use App\Entity\Pracownik;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
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

            ->add('przelozony', EntityType::class, [
                'class' => Pracownik::class,
                'choice_label' => fn(Pracownik $p) => $p->getImie().' '.$p->getNazwisko().' ('.$p->getEmail().')',
                'required' => false,
                'placeholder' => '— brak przełożonego —',
            ])

            ->add('isActive', CheckboxType::class, [
                'required' => false,
                'label' => 'Aktywne',
            ])
            ->add('isVerified', CheckboxType::class, [
                'required' => false,
                'label' => 'Zatwierdzone',
            ])

            ->add('roles', ChoiceType::class, [
                'multiple' => true,
                'expanded' => true,
                'choices' => [
                    'User' => 'ROLE_USER',
                    'Admin' => 'ROLE_ADMIN',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Pracownik::class,
        ]);
    }
}
