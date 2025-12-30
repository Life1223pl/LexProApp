<?php

namespace App\Form;

use App\Entity\CzynnoscUczestnik;
use App\Entity\Osoba;
use App\Entity\Postepowanie;
use App\Entity\Pracownik;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class CzynnoscUczestnikType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Postepowanie $postepowanie */
        $postepowanie = $options['postepowanie'];

        $builder
            ->add('rola', ChoiceType::class, [
                'label' => 'Rola',
                'choices' => array_combine(
                    CzynnoscUczestnik::getDostepneRole(),
                    CzynnoscUczestnik::getDostepneRole()
                ),
                'placeholder' => '— wybierz —',
            ])
            ->add('pracownik', EntityType::class, [
                'label' => 'Pracownik (jeśli dotyczy)',
                'class' => Pracownik::class,
                'required' => false,
                'placeholder' => '— brak —',
                'choice_label' => fn(Pracownik $p) => trim(($p->getStopien() ?? '').' '.$p->getImie().' '.$p->getNazwisko()),
            ])
            ->add('osoba', EntityType::class, [
                'label' => 'Osoba z postępowania (jeśli dotyczy)',
                'class' => Osoba::class,
                'required' => false,
                'placeholder' => '— brak —',
                'query_builder' => function (EntityRepository $r) use ($postepowanie) {
                    return $r->createQueryBuilder('o')
                        ->join('o.udzialy', 'u')
                        ->andWhere('u.postepowanie = :p')
                        ->setParameter('p', $postepowanie)
                        ->orderBy('o.nazwisko', 'ASC')
                        ->addOrderBy('o.imie', 'ASC');
                },
            ])
            ->add('opisRoli', TextareaType::class, [
                'label' => 'Opis roli (opcjonalnie)',
                'required' => false,
                'attr' => ['rows' => 2],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CzynnoscUczestnik::class,
        ]);

        $resolver->setRequired(['postepowanie']);
        $resolver->setAllowedTypes('postepowanie', Postepowanie::class);
    }
}
