<?php

namespace App\Controller;

use App\Form\ParticipantType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/participant', name: 'participant_')]
class ParticipantController extends AbstractController{


    public function profil(): Response {

        return $this->render('participant/profil.html.twig');
    }

    #[Route('/modifierProfil', name: 'modifierProfil')]
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
