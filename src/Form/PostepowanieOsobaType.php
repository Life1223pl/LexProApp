<?php

namespace App\Form;

use App\Entity\Osoba;
use App\Entity\PostepowanieOsoba;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PostepowanieOsobaType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('osoba', EntityType::class, [
                'class' => Osoba::class,
                'choice_label' => fn(Osoba $o) => (string) $o,
                'placeholder' => '— wybierz osobę —',
                'required' => true,
                'label' => 'Osoba',
                'attr' => ['class' => 'form-select'],
            ])
            ->add('rola', ChoiceType::class, [
                'choices' => [
                    'Podejrzany' => PostepowanieOsoba::ROLA_PODEJRZANY,
                    'Świadek' => PostepowanieOsoba::ROLA_SWIADEK,
                    'Pokrzywdzony' => PostepowanieOsoba::ROLA_POKRZYWDZONY,
                    'Inna' => PostepowanieOsoba::ROLA_INNA,
                ],
                'required' => true,
                'label' => 'Rola w tym postępowaniu',
                'attr' => ['class' => 'form-select'],
            ])
            ->add('stosunekDoPodejrzanego', TextareaType::class, [
                'required' => false,
                'label' => 'Stosunek do podejrzanego (opcjonalnie)',
                'attr' => ['rows' => 2, 'class' => 'form-control'],
            ])
            ->add('stosunekDoPokrzywdzonego', TextareaType::class, [
                'required' => false,
                'label' => 'Stosunek do pokrzywdzonego (opcjonalnie)',
                'attr' => ['rows' => 2, 'class' => 'form-control'],
            ])
            ->add('pouczonyOdpowiedzialnoscKarna', CheckboxType::class, [
                'required' => false,
                'label' => 'Pouczony o odpowiedzialności karnej (np. świadek)',
            ])
            ->add('notatki', TextareaType::class, [
                'required' => false,
                'label' => 'Notatki (opcjonalnie)',
                'attr' => ['rows' => 3, 'class' => 'form-control'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PostepowanieOsoba::class,
        ]);
    }
}
