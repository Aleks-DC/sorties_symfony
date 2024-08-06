<?php

namespace App\Form;

use App\Entity\Campus;
use App\Entity\Participant;
use App\Entity\Sortie;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ParticipantType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
        ->add('nom', TextType::class)
        ->add('prenom', TextType::class)
        ->add('telephone', TextType::class)
        ->add('mail', EmailType::class)
        ->add('motDePasse', TextType::class)
        ->add('administrateur')
        ->add('actif', ChoiceType::class, [
            'choices' => ['Oui', 'Non'],
            'choices_label' => ['Oui', 'Non'],
        ])

        ->add('pseudo', TextType::class)
        ->add('campusAffilie', EntityType::class, [
            'class' => Campus::class,
            'choice_label' => 'nom',
        ])
        ->add('sortiesPrevues', EntityType::class, [
            'class' => Sortie::class,
            'choice_label' => 'nom',
            'multiple' => true,
        ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Participant::class,
        ]);
    }
}
