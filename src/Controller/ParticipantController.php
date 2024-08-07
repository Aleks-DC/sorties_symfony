<?php

namespace App\Controller;

use App\Entity\Participant;
use App\Form\ParticipantType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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

    #[Route('/modifierProfil', name: 'modifierProfil', methods: ['POST'])]
    public function modifierProfil(Request $request): Response
    {
        $participantForm = $this->createForm(ParticipantType::class);

        $participantForm-> handleRequest($request);

        if($participantForm->isSubmitted() && $participantForm->isValid()){
            //faire qqch avec les données
            //dump($participant)

            return $this->redirectToRoute('modifierProfil');
        }

        return $this->render('participant/modifier.html.twig', [
            'participantForm' => $participantForm->createView(),
        ]);
    }
}
