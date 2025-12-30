<?php

namespace App\Form;

use App\Entity\Osoba;
use App\Form\AdresType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OsobaType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('imie', TextType::class, ['required' => false, 'label' => 'Imię'])
            ->add('drugieImie', TextType::class, ['required' => false, 'label' => 'Drugie imię'])
            ->add('nazwisko', TextType::class, ['required' => false, 'label' => 'Nazwisko'])
            ->add('nazwiskoRodowe', TextType::class, ['required' => false, 'label' => 'Nazwisko rodowe'])
            ->add('imieOjca', TextType::class, ['required' => false, 'label' => 'Imię ojca'])
            ->add('imieMatki', TextType::class, ['required' => false, 'label' => 'Imię matki'])
            ->add('nazwiskoRodoweMatki', TextType::class, ['required' => false, 'label' => 'Nazwisko rodowe matki'])

            ->add('pesel', TextType::class, ['required' => false, 'label' => 'PESEL'])
            ->add('numerDokumentu', TextType::class, ['required' => false, 'label' => 'Numer dokumentu (dowód/paszport)'])

            ->add('dataUrodzenia', DateType::class, [
                'required' => false,
                'label' => 'Data urodzenia',
                'widget' => 'single_text',
            ])
            ->add('miejsceUrodzenia', TextType::class, ['required' => false, 'label' => 'Miejsce urodzenia'])

            ->add('plec', ChoiceType::class, [
                'required' => false,
                'label' => 'Płeć',
                'placeholder' => '— wybierz —',
                'choices' => [
                    'Mężczyzna' => Osoba::PLEC_M,
                    'Kobieta' => Osoba::PLEC_K,
                    'Nieznana' => Osoba::PLEC_NIEZNANA,
                ],
            ])

            ->add('obywatelstwoGl', TextType::class, ['required' => false, 'label' => 'Obywatelstwo (główne)'])
            ->add('obywatelstwoDodatkowe', TextType::class, ['required' => false, 'label' => 'Obywatelstwo (dodatkowe)'])

            ->add('telefon', TextType::class, ['required' => false, 'label' => 'Telefon'])
            ->add('email', EmailType::class, ['required' => false, 'label' => 'Email'])

            ->add('wyksztalcenie', TextType::class, ['required' => false, 'label' => 'Wykształcenie'])
            ->add('stanCywilny', TextType::class, ['required' => false, 'label' => 'Stan cywilny'])
            ->add('zawod', TextType::class, ['required' => false, 'label' => 'Zawód'])
            ->add('miejscePracy', TextType::class, ['required' => false, 'label' => 'Miejsce pracy'])
            ->add('stanowisko', TextType::class, ['required' => false, 'label' => 'Stanowisko'])

            ->add('notatki', TextareaType::class, [
                'required' => false,
                'label' => 'Notatki',
                'attr' => ['rows' => 4],
            ])

            // ✅ ADRESY (Embeddable)
            ->add('adresZamieszkania', AdresType::class, [
                'label' => false,
                'required_main' => true, // minimum wymagane
            ])
            ->add('adresZameldowania', AdresType::class, [
                'label' => false,
                'required_main' => false,
            ])
            ->add('adresKorespondencyjny', AdresType::class, [
                'label' => false,
                'required_main' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Osoba::class,
        ]);
    }
}
