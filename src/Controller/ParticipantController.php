<?php

namespace App\Controller;

use App\Entity\Participant;
use App\Form\ParticipantType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/participant', name: 'participant_')]
class ParticipantController extends AbstractController{

    #[Route('/monProfil/{id}', name:'profil', methods: ['GET'])]
    public function afficherProfil(EntityManagerInterface $entityManager, int $id): Response {

        $participant = $entityManager->getRepository(Participant::class)->find($id);

        if (!$participant) {
            throw $this->createNotFoundException(
                'No participant found for id '.$id
            );
        }
        return $this->render('participant/profil.html.twig', [
            'participant' => $participant
        ]);
    }

    #[Route('/modifierProfil/{id}', name: 'modifierProfil')]
    public function modifierProfil(Request $request, EntityManagerInterface $entityManager, int $id): Response
    {
        $participant = $entityManager->getRepository(Participant::class)->find($id);
        if (!$participant) {
            throw $this->createNotFoundException('Le participant n\'existe pas');
        }

        $participantForm = $this->createForm(ParticipantType::class, $participant);
        $participantForm-> handleRequest($request);

        if($participantForm->isSubmitted() && $participantForm->isValid()){
            $motDePasseActuel = $participantForm->get('motDePasseActuel')->getData();
            $nouveauMotDePasse = $participantForm->get('nouveauMotDePasse')->getData();
            $confirmationNouveauMotDePasse = $participantForm->get('confirmationNouveauMotDePasse')->getData();

            if ($motDePasseActuel !== $participant->getMotDePasse()) {
                $this->addFlash('error', 'Le mot de passe actuel est incorrect.');
            } elseif (empty($nouveauMotDePasse) || $nouveauMotDePasse !== $confirmationNouveauMotDePasse) {
                $this->addFlash('error', 'Le nouveau mot de passe et la confirmation ne correspondent pas.');
            } else {
                // Remplacez le mot de passe par le nouveau mot de passe
                $participant->setMotDePasse($nouveauMotDePasse);
                $this->addFlash('success', 'Mot de passe mis à jour avec succès.');

                // Persistez les modifications
                $entityManager->persist($participant);
                $entityManager->flush();

                // Redirection après mise à jour réussie
                return $this->redirectToRoute('participant_profil', ['id' => $id]);
            }
        }
        return $this->render('participant/modifier.html.twig', [
            'participantForm' => $participantForm->createView(),
        ]);
    }
}
