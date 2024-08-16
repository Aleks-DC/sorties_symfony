<?php

namespace App\Controller;

use App\Entity\Participant;
use App\Entity\Sortie;
use App\Form\ParticipantType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/participant', name: 'participant_')]
class ParticipantController extends AbstractController{

    #[Route('/profil/{sortieId}', name:'profil', methods: ['GET'])]
    public function afficherProfil(EntityManagerInterface $entityManager, int $sortieId): Response {

        $sortie = $entityManager->getRepository(Sortie::class)->find($sortieId);
        if (!$sortie) {
            throw $this->createNotFoundException('No participant found for id '.$sortieId);
        }

        $participant = $sortie->getOrganisateur();
        if (!$participant) {
            throw $this->createNotFoundException('No participant found for the sortie with id ' . $sortieId);
        }

        return $this->render('participant/profil.html.twig', [
            'participant' => $participant
        ]);
    }

    #[Route('/modifierProfil/{id}', name: 'modifierProfil')]
    public function modifierProfil(Request $request,
                                   EntityManagerInterface $entityManager,
                                   UserPasswordHasherInterface $passwordHasher,
                                   Participant $participant): Response
    {
        $currentUser = $this->getUser();
        if (!$currentUser instanceof Participant || $currentUser->getId() !== $participant->getId()) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas modifier ce profil.');
        }

        $participantForm = $this->createForm(ParticipantType::class, $participant);
        $participantForm-> handleRequest($request);

        if($participantForm->isSubmitted() && $participantForm->isValid()){
            $motDePasseActuel = $participantForm->get('motDePasseActuel')->getData();
            $nouveauMotDePasse = $participantForm->get('nouveauMotDePasse')->getData();
            $confirmationNouveauMotDePasse = $participantForm->get('confirmationNouveauMotDePasse')->getData();

            if (!$passwordHasher->isPasswordValid($participant, $motDePasseActuel)) {
                $this->addFlash('error', 'Le mot de passe actuel est incorrect.');
            } elseif (empty($nouveauMotDePasse) || $nouveauMotDePasse !== $confirmationNouveauMotDePasse) {
                $this->addFlash('error', 'Le nouveau mot de passe et la confirmation ne correspondent pas.');
            } else {
                // Hasher et Remplacer le mot de passe par le nouveau mot de passe
                $hashedPassword = $passwordHasher->hashPassword($participant, $nouveauMotDePasse);
                $participant->setMotDePasse($hashedPassword);
                $this->addFlash('success', 'Mot de passe mis à jour avec succès.');

                // Persistez les modifications
                $entityManager->persist($participant);
                $entityManager->flush();

                // Redirection après mise à jour réussie
                $sortie = $entityManager->getRepository(Sortie::class)->findOneBy(['organisateur' => $participant]);

                if ($sortie) {
                    return $this->redirectToRoute('participant_profil', ['sortieId' => $sortie->getId()]);
                } else {
                    // Si aucune sortie n'est trouvée
                    return $this->redirectToRoute('app_accueil');
                }
            }
        }
        return $this->render('participant/modifier.html.twig', [
            'participantForm' => $participantForm->createView(),
        ]);
    }

}
