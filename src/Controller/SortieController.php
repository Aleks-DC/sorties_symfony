<?php

namespace App\Controller;

use App\Entity\Etat;
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
    public function annulerSortie(EntityManagerInterface $entityManager, int $id, Request $request): Response
    {
        //Récupérer la sortie
        $sortie = $entityManager->getRepository(Sortie::class)->find($id);
        if (!$sortie) {
            throw $this->createNotFoundException('Sortie non trouvée.');
        }

        //Annulation impossible si la sortie a eu lieu
        if ($sortie->getDateHeureDebut() <= new DateTime()) {
            $this->addFlash('error', 'La sortie a déjà eu lieu et ne peut pas être annulée.');
            return $this->redirectToRoute('sortie_detail', ['id' => $id]);
        }

        $annulerSortieForm = $this-> createForm(AnnulerSortieType::class, $sortie);
        $annulerSortieForm->handleRequest($request);

        if ($annulerSortieForm->isSubmitted() && $annulerSortieForm->isValid()) {

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
}
