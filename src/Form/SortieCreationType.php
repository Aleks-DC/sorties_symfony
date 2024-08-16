<?php

namespace App\Form;

use App\Entity\Campus;
use App\Entity\Etat;
use App\Entity\Lieu;
use App\Entity\Participant;
use App\Entity\Sortie;
use App\Entity\Ville;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SortieCreationType extends AbstractType
{
    private Security $security;

    // Inject Security service to access the current user
    public function __construct(Security $security)
    {
        $this->security = $security;
    }
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {

        // Get the current user
        $currentUser = $this->security->getUser();

        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom de la sortie'
            ])
            ->add('dateHeureDebut', DateTimeType::class, [
                'label' => 'Date et heure de la sortie',
                'widget' => 'single_text'
            ])
            ->add('dateLimiteInscription', DateType::class, [
                'label' => "Date limite d'inscription",
                'widget' => 'single_text'
            ])
            ->add('nbInscriptionsMax', IntegerType::class, [
                'label' => 'Nombre de places',
                'attr' => ['min' => 1]
            ])
            ->add('duree', IntegerType::class, [
                'label' => 'Durée',
                'attr' => ['min' => 1]
            ])
            ->add('infosSortie', TextareaType::class, [
                'label' => 'Description et infos'
            ])
            ->add('lieu', EntityType::class, [
                'class' => Lieu::class,
                'choice_label' => 'nom',
                'label' => 'Lieu'
            ])
            ->add('siteOrganisateur', EntityType::class, [
                'class' => Campus::class,
                'choice_label' => 'nom',
                'label' => 'Campus',
                'disabled' => true,  // Rendre le champ désactivé
                'data' => $currentUser->getCampusAffilie() // Pré-remplir avec le campus de l'utilisateur
            ])
            ->add('enregistrer', SubmitType::class, [
                'label' => 'Enregistrer',
                'attr' => ['class' => 'btn btn-primary']
            ])
            ->add('publier', SubmitType::class, [
                'label' => 'Publier la sortie',
                'attr' => ['class' => 'btn btn-success']
            ])
            ->add('annuler', ButtonType::class, [
                'label' => 'Annuler',
                'attr' => ['class' => 'btn btn-secondary', 'onclick' => 'window.location.href="/";']
            ]);
    }
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Sortie::class,
        ]);
    }
}
