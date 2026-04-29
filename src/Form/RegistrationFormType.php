<?php

namespace App\Form;

use App\Entity\Pracownik;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('imie', null, [
                'constraints' => [
                    new NotBlank(['message' => 'Podaj imię']),
                ],
            ])
            ->add('nazwisko', null, [
                'constraints' => [
                    new NotBlank(['message' => 'Podaj nazwisko']),
                ],
            ])
            ->add('email', EmailType::class, [
                'constraints' => [
                    new NotBlank(['message' => 'Podaj adres e-mail']),
                ],
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'mapped' => false,
                'first_options' => [
                    'label' => 'Hasło',
                    'attr' => ['class' => 'form-control', 'autocomplete' => 'new-password'],
                ],
                'second_options' => [
                    'label' => 'Powtórz hasło',
                    'attr' => ['class' => 'form-control'],
                ],
                'invalid_message' => 'Hasła muszą być takie same.',
                'constraints' => [
                    new NotBlank(['message' => 'Podaj hasło']),
                    new Length([
                        'min' => 6,
                        'minMessage' => 'Hasło musi mieć co najmniej {{ limit }} znaków',
                        'max' => 4096,
                    ]),
                ],
            ])
            ->add('agreeTerms', CheckboxType::class, [
                'mapped' => false,
                'label' => 'Akceptuję regulamin',
                'constraints' => [
                    new IsTrue(['message' => 'Musisz zaakceptować regulamin.']),
                ],
            ])
            ->add('miejsceZatrudnienia', TextType::class, [
                'label' => 'Miejsce zatrudnienia',
                'required' => false,
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
