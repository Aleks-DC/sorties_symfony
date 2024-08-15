<?php

namespace App\Controller;

use App\Entity\Etat;
use App\Entity\Participant;
use App\Entity\Sortie;
use App\Form\AnnulerSortieType;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/sortie', name: 'sortie_')]
class SortieController extends AbstractController
{
    #[Route('/annuler/{id}', name: 'annulation')]
    public function annulerSortie(Request $request, EntityManagerInterface $entityManager, int $id ): Response
    {
        //Récupérer la sortie
        $sortie = $entityManager->getRepository(Sortie::class)->find($id);
        if (!$sortie) {
            throw $this->createNotFoundException('Sortie non trouvée.');
        }

        $currentUser = $this->getUser();
        if (!$currentUser instanceof Participant || $currentUser->getId() !== $sortie->getOrganisateur()->getId()) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à annuler cette sortie.');
        }

        $etatActuel = $sortie->getEtat()->getLibelle();
        if ($etatActuel !== Etat::ETAT_CREEE && $etatActuel !== Etat::ETAT_OUVERTE) {
            $this->addFlash('error', 'La sortie ne peut pas être annulée.');
            return $this->redirectToRoute('app_accueil');
        }

        $annulerSortieForm = $this-> createForm(AnnulerSortieType::class, $sortie);
        $annulerSortieForm->handleRequest($request);

        if ($annulerSortieForm->isSubmitted() && $annulerSortieForm->isValid()) {

            if ($sortie->getDateHeureDebut() <= new DateTime()) {
                $this->addFlash('error', 'La sortie a déjà eu lieu et ne peut pas être annulée.');
                return $this->redirectToRoute('app_accueil');
            }

            // Récupérer l'Etat "Annulée"
            $etatAnnulee = $entityManager->getRepository(Etat::class)->findOneBy(['libelle' => Etat::ETAT_ANNULEE]);
            if (!$etatAnnulee) {
                throw $this->createNotFoundException('État "Annulée" non trouvé.');
            }

            $sortie->setEtat($etatAnnulee);
            // Enregistrer le motif d'annulation
            $motif = $annulerSortieForm->get('motifAnnulation')->getData();
            $sortie->setMotifAnnulation($motif);

            $entityManager->flush();

            $this->addFlash('success', 'La sortie a été annulée avec succès.');
            return $this->redirectToRoute('app_accueil');
        }

        return $this->render('sortie/annulerSortie.html.twig', [
            'sortie' => $sortie,
            'form' => $annulerSortieForm->createView(),
        ]);
    }

    #[Route('/desister/{id}', name: 'desistement')]
    public function desister(EntityManagerInterface $entityManager, int $id): Response
    {
        $sortie = $entityManager->getRepository(Sortie::class)->find($id);
        if (!$sortie) {
            throw $this->createNotFoundException('Sortie non trouvée.');
        }

        $participant = $this->getUser();

        if ($participant instanceof Participant && $participant->estInscrit($sortie))  {
            if ($sortie->getDateHeureDebut() > new DateTime()) {
                $participant->removeSortiesPrevue($sortie);
                $entityManager->flush();

                 $this->addFlash('success', 'Vous vous êtes désisté avec succès.');
            } else {
                $this->addFlash('error', 'La date de cette sortie est déjà passée. Vous ne pouvez plus vous désister.');
            }
        } else {
            $this->addFlash('error', 'Vous n\'êtes pas inscrit à cette sortie.');
        }

        return $this->redirectToRoute('app_accueil', ['id' => $id]);
    }

    #[Route('/inscrire/{id}', name: 'inscription')]
    public function inscription(EntityManagerInterface $entityManager, int $id): Response
    {
        $sortie = $entityManager->getRepository(Sortie::class)->find($id);
        if (!$sortie) {
            throw $this->createNotFoundException('Sortie non trouvée.');
        }

        $participant = $this->getUser();
        if (!$participant instanceof Participant) {
            throw $this->createAccessDeniedException('Vous devez être connecté pour vous inscrire.');
        }

        if ($sortie->getEtat()->getLibelle() !== Etat::ETAT_OUVERTE || $sortie->getDateLimiteInscription() <= new DateTime()) {
            $this->addFlash('error', 'Vous ne pouvez pas vous inscrire à cette sortie.');
            return $this->redirectToRoute('app_accueil');
        }

        if ($sortie->getOrganisateur()->getId() !== $participant->getId() && !$participant->estInscrit($sortie)) {
            $participant->addSortiesPrevue($sortie);
            $entityManager->flush();
            $this->addFlash('success', 'Vous vous êtes inscrit avec succès.');
        } else {
            $this->addFlash('error', 'Vous ne pouvez pas vous inscrire à cette sortie.');
        }
        return $this->redirectToRoute('app_accueil');
    }



    //Indiquer le bon lien vers la page de formulaire
    #[Route('/modifier/{id}', name: 'modifier')]
    public function modifier(Request $request, EntityManagerInterface $entityManager, int $id): Response
    {
        $sortie = $entityManager->getRepository(Sortie::class)->find($id);
        if (!$sortie) {
            throw $this->createNotFoundException('Sortie non trouvée.');
        }

        $currentUser = $this->getUser();
        if (!$currentUser instanceof Participant || $currentUser->getId() !== $sortie->getOrganisateur()->getId()) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à modifier cette sortie.');
        }

        $etatActuel = $sortie->getEtat()->getLibelle();
        if ($etatActuel !== Etat::ETAT_CREEE && $etatActuel !== Etat::ETAT_OUVERTE) {
            $this->addFlash('error', 'La sortie ne peut pas être modifiée.');
            return $this->redirectToRoute('app_accueil');
        }

        $form = $this->createForm(SortieType::class, $sortie);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'La sortie a été modifiée avec succès.');
            return $this->redirectToRoute('app_accueil');
        }

        return $this->render('sortie/modifierSortie.html.twig', [
            'form' => $form->createView(),
        ]);
    }



    //indiquer le lien vers bonne page détails de la sortie
    #[Route('/afficher/{id}', name: 'afficher')]
    public function afficher(EntityManagerInterface $entityManager, int $id): Response
    {
        $sortie = $entityManager->getRepository(Sortie::class)->find($id);
        if (!$sortie) {
            throw $this->createNotFoundException('Sortie non trouvée.');
        }

        return $this->render('sortie/afficherSortie.html.twig', [
            'sortie' => $sortie,
        ]);
    }

    #[Route('/publier/{id}', name: 'publier')]
    public function publier(EntityManagerInterface $entityManager, int $id): Response
    {
        $sortie = $entityManager->getRepository(Sortie::class)->find($id);
        if (!$sortie) {
            throw $this->createNotFoundException('Sortie non trouvée.');
        }

        $currentUser = $this->getUser();
        if (!$currentUser instanceof Participant || $currentUser->getId() !== $sortie->getOrganisateur()->getId()) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à publier cette sortie.');
        }

        // Vérifier que la sortie est en état "Créée"
        if ($sortie->getEtat()->getLibelle() === Etat::ETAT_CREEE) {
            $etatOuverte = $entityManager->getRepository(Etat::class)->findOneBy(['libelle' => Etat::ETAT_OUVERTE]);
            if (!$etatOuverte) {
                throw $this->createNotFoundException('État "Ouverte" non trouvé.');
            }

            $sortie->setEtat($etatOuverte);
            $entityManager->flush();

            $this->addFlash('success', 'La sortie a été publiée avec succès.');
        } else {
            $this->addFlash('error', 'La sortie ne peut pas être publiée.');
        }

        return $this->redirectToRoute('app_accueil');
    }

}
