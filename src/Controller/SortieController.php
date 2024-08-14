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
}
