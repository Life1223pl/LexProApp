<?php

namespace App\Form;

use Symfony\Component\Validator\Constraints\Length;
use App\Entity\Pracownik;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;

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

                // 🔥 FILTR PHP (pewny sposób)
                'choices' => array_filter(
                    $options['pracownicy'] ?? [],
                    fn(Pracownik $p) => in_array('ROLE_SUPERVISOR', $p->getRoles())
                ),
            ])

            ->add('isActive', CheckboxType::class, [
                'required' => false,
                'label' => 'Konto aktywne',
            ])

            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'mapped' => false,
                'required' => false,
                'invalid_message' => 'Hasła muszą być takie same.',
                'first_options' => [
                    'label' => 'Nowe hasło',
                    'attr' => [
                        'class' => 'form-control',
                        'placeholder' => 'Nowe hasło'
                    ],
                ],
                'second_options' => [
                    'label' => 'Powtórz hasło',
                    'attr' => [
                        'class' => 'form-control',
                        'placeholder' => 'Powtórz hasło'
                    ],
                ],
                'constraints' => [
                    new Length([
                        'min' => 6,
                        'minMessage' => 'Hasło musi mieć min. {{ limit }} znaków',
                    ]),
                ],
            ]);

    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Pracownik::class,
            'pracownicy' => [], // 🔥 DODANE
        ]);
    }
}
