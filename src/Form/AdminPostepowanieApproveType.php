<?php

namespace App\Form;

use App\Entity\Pracownik;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AdminPostepowanieApproveType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Pracownik[] $pracownicy */
        $pracownicy = $options['pracownicy'];

        /** @var Pracownik[] $defaultAssigned */
        $defaultAssigned = $options['default_assigned'] ?? [];

        /** @var ?Pracownik $defaultProwadzacy */
        $defaultProwadzacy = $options['default_prowadzacy'] ?? null;

        $builder
            ->add('assignedPracownicy', EntityType::class, [
                'class' => Pracownik::class,
                'choices' => $pracownicy,
                'multiple' => true,
                'expanded' => true, // ✅ checkboxy
                'choice_label' => fn(Pracownik $p) => $p->getImie().' '.$p->getNazwisko().' ('.$p->getEmail().')',
                'required' => true,
                'label' => 'Przypisani pracownicy',
                'data' => $defaultAssigned, // ✅ domyślne zaznaczenia
            ])
            ->add('prowadzacy', EntityType::class, [
                'class' => Pracownik::class,
                'choices' => $pracownicy,
                'multiple' => false,
                'expanded' => false, // select
                'choice_label' => fn(Pracownik $p) => $p->getImie().' '.$p->getNazwisko().' ('.$p->getEmail().')',
                'required' => true,
                'label' => 'Prowadzący',
                'data' => $defaultProwadzacy, // ✅ domyślny prowadzący
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'pracownicy' => [],
            'default_prowadzacy' => null,
            'default_assigned' => [],
        ]);

        $resolver->setAllowedTypes('pracownicy', 'array');
        $resolver->setAllowedTypes('default_assigned', 'array');
        $resolver->setAllowedTypes('default_prowadzacy', ['null', Pracownik::class]);
    }
}
