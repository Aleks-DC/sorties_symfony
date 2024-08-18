<?php

namespace App\Controller;

use App\Entity\Etat;
use App\Entity\Sortie;
use App\Form\SortieCreationType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Util\TargetPathTrait;


class SortieCreationController extends AbstractController
{
    use TargetPathTrait;
    #[Route('/create/sortie', name: 'app_sortie_creation')]
    public function index(Request $request, EntityManagerInterface $entityManager, Security $security): Response
    {
        // Vérifie si l'utilisateur est connecté
        if (!$this->getUser()) {
            // Sauvegarde l'URL actuelle (la page de création) avant redirection
            $this->saveTargetPath($request->getSession(), 'main', $this->generateUrl('app_sortie_creation'));
            // Redirige vers la page de login
            return $this->redirectToRoute('app_login');
        }

        $sortie = new Sortie();

        $form = $this->createForm(SortieCreationType::class, $sortie);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $currentUser = $security->getUser();

            $sortie->setOrganisateur($currentUser);

            // Associer le campus de l'utilisateur comme site organisateur
            $sortie->setSiteOrganisateur($currentUser->getCampusAffilie());

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
