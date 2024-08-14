<?php

namespace App\Controller;

use App\Entity\Sortie;
use App\Form\SortieCreationType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SortieCreationController extends AbstractController
{
    #[Route('/create/sortie', name: 'app_sortie_creation')]
    public function index(Request $request, EntityManagerInterface $entityManager): Response
    {
        $sortie = new Sortie();

        $form = $this->createForm(SortieCreationType::class, $sortie);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($form->get('enregistrer')->isClicked()) {
                return $this->handleSortieAction($sortie, $entityManager, 'enregistrer');
            } elseif ($form->get('publier')->isClicked()) {
                return $this->handleSortieAction($sortie, $entityManager, 'publier');
            }
        }

        return $this->render('sortie_creation/index.html.twig', [
            'sortieCreationForm' => $form->createView()
        ]);
    }

    private function handleSortieAction(Sortie $sortie, EntityManagerInterface $entityManager, string $action): Response
    {
        if ($action === 'publier') {
            $sortie->setStatus('published'); // Exemple : changement de statut
            $message = 'Sortie publiée avec succès.';
            $redirectRoute = 'app_sortie_liste'; // Redirection vers la liste des sorties
        } else {
            $message = 'Sortie enregistrée avec succès.';
            $redirectRoute = 'app_sortie_creation'; // Rester sur la page de création
        }

        $entityManager->persist($sortie);
        $entityManager->flush();

        $this->addFlash('success', $message);

        return $this->redirectToRoute($redirectRoute);
    }
}
