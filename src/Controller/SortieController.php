<?php

namespace App\Controller;

use App\Entity\Etat;
use App\Entity\Participant;
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
use Symfony\Component\Security\Http\Util\TargetPathTrait;

#[Route('/sortie', name: 'app_sortie_')]
class SortieController extends AbstractController
{
    use TargetPathTrait; // Assure-toi que ce trait est bien inclus
    #[Route('/details/{id}', name: 'details', methods: ['GET'])]
    public function afficherSortie(EntityManagerInterface $entityManager, int $id, Request $request): Response
    {
        // Vérifie si l'utilisateur est connecté
        if (!$this->getUser()) {
            // Génère l'URL complète pour la page de détails avec l'ID spécifique
            $targetUrl = $this->generateUrl('app_sortie_details', ['id' => $id]);

            // Sauvegarde l'URL actuelle (la page de détails avec l'ID) avant redirection
            $this->saveTargetPath($request->getSession(), 'main', $targetUrl);

            // Redirige vers la page de login
            return $this->redirectToRoute('app_login');
        }

        //Récupérer la sortie
        $sortie = $entityManager->getRepository(Sortie::class)->find($id);
        if (!$sortie) {
            throw $this->createNotFoundException('Sortie non trouvée.');
        }
        return $this->render('sortie/details.html.twig', [
            'sortie' => $sortie,
        ]);
    }
    #[Route('/modif/{id}', name: 'modif')]
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
            $redirectRoute = 'app_accueil';
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
