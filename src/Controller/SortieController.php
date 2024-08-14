<?php

namespace App\Controller;

use App\Entity\Etat;
use App\Entity\Sortie;
use App\Form\AnnulerSortieType;
use App\Form\SortieCreationType;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/sortie', name: 'app_sortie_')]
class SortieController extends AbstractController
{
    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function afficherSortie(EntityManagerInterface $entityManager, int $id, Request $request): Response
    {
        //Récupérer la sortie
        $sortie = $entityManager->getRepository(Sortie::class)->find($id);
        if (!$sortie) {
            throw $this->createNotFoundException('Sortie non trouvée.');
        }
        return $this->render('sortie/show.html.twig', [
            'sortie' => $sortie,
        ]);
    }
    #[Route('/modifier/{id}', name: 'modifier')]
    public function modifierSortie(EntityManagerInterface $entityManager, int $id, Request $request, Security $security): Response
    {
        // Récupération de la sortie à modifier
        $sortie = $entityManager->getRepository(Sortie::class)->find($id);

        // Vérification si la sortie existe
        if (!$sortie) {
            throw $this->createNotFoundException('Sortie non trouvée');
        }

        // Vérification si l'utilisateur est bien l'organisateur de la sortie
        $currentUser = $security->getUser();
        if ($sortie->getOrganisateur() !== $currentUser) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas l\'organisateur de cette sortie');
        }

        // Création du formulaire en utilisant la sortie récupérée
        $form = $this->createForm(SortieCreationType::class, $sortie);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Vérification des actions du formulaire
            if ($form->get('enregistrer')->isClicked()) {
                return $this->handleSortieAction($sortie, $entityManager, 'enregistrer');
            } elseif ($form->get('publier')->isClicked()) {
                return $this->handleSortieAction($sortie, $entityManager, 'publier');
            }
        }

        // Rendu du formulaire de modification
        return $this->render('sortie/modif.html.twig', [
            'sortieCreationForm' => $form->createView()
        ]);
    }

    private function handleSortieAction(Sortie $sortie, EntityManagerInterface $entityManager, string $action): Response
    {
        $etatRepository = $entityManager->getRepository(Etat::class);

        if ($action === 'publier') {
            $etat = $etatRepository->findOneBy(['libelle' => Etat::ETAT_OUVERTE]);
            $message = 'Sortie publiée avec succès.';
            $redirectRoute = 'app_accueil';
        } else {
            $etat = $etatRepository->findOneBy(['libelle' => Etat::ETAT_CREEE]);
            $message = 'Sortie modifiée et enregistrée avec succès.';
            $redirectRoute = 'app_sortie_modification';
        }

        if ($etat) {
            $sortie->setEtat($etat);
        }

        $entityManager->persist($sortie);
        $entityManager->flush();

        $this->addFlash('success', $message);

        return $this->redirectToRoute($redirectRoute, ['id' => $sortie->getId()]);
    }
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
            return $this->redirectToRoute('sortie_annulation', ['id' => $id]);
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
