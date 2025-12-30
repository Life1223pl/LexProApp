<?php

namespace App\Form;

use App\Entity\Embeddable\Adres;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AdresType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $requiredMain = (bool) $options['required_main'];

        $builder
            ->add('kraj', TextType::class, [
                'required' => false,
                'label' => 'Kraj',
            ])
            ->add('miejscowosc', TextType::class, [
                'required' => $requiredMain,
                'label' => 'Miejscowość',
            ])
            ->add('kodPocztowy', TextType::class, [
                'required' => false,
                'label' => 'Kod pocztowy',
            ])
            ->add('ulica', TextType::class, [
                'required' => false,
                'label' => 'Ulica',
            ])
            ->add('nrDomu', TextType::class, [
                'required' => $requiredMain,
                'label' => 'Nr domu',
            ])
            ->add('nrLokalu', TextType::class, [
                'required' => false,
                'label' => 'Nr lokalu',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Adres::class,
            'required_main' => false,
        ]);

        $resolver->setAllowedTypes('required_main', 'bool');
    }
}
