<?php

namespace App\Controller;

use App\Entity\Etat;
use App\Entity\Participant;
use App\Entity\Sortie;
use App\Form\SortieCreationType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SortieCreationController extends AbstractController
{
    #[Route('/create/sortie', name: 'app_sortie_creation')]
    public function index(Request $request, EntityManagerInterface $entityManager, Security $security): Response
    {
        $sortie = new Sortie();

        $form = $this->createForm(SortieCreationType::class, $sortie);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $currentUser = $security->getUser();

            $sortie->setOrganisateur($currentUser);

            if ($form->get('enregistrer')->isClicked()) {
                return $this->handleSortieAction($sortie, $entityManager, 'enregistrer');
            } elseif ($form->get('publier')->isClicked()) {
                return $this->handleSortieAction($sortie, $entityManager, 'publier');
            }
        }

        return $this->render('sortie_creation/sortie-creation.html.twig', [
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
            $message = 'Sortie enregistrée avec succès.';
            $redirectRoute = 'app_sortie_creation';
        }

        if ($etat) {
            $sortie->setEtat($etat);
        }

        $entityManager->persist($sortie);
        $entityManager->flush();

        $this->addFlash('success', $message);

        return $this->redirectToRoute($redirectRoute);
    }
}
