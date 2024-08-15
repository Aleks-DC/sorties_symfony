<?php

namespace App\Controller;

use App\Entity\Participant;
use App\Repository\CampusRepository;
use App\Repository\ParticipantRepository;
use App\Repository\SortieRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use DateTime;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AccueilController extends AbstractController
{
    #[Route('/', name: 'app_accueil')]
    public function index(ParticipantRepository $participantRepository, SortieRepository $sortieRepository, CampusRepository $campusRepository, Security $security): Response
    {
        $date = new DateTime();
        $frDate = $date->format('d/m/Y');

        // Obtenir l'utilisateur connecté
        $user = $security->getUser();
        $username = $user instanceof Participant ? $user->getPseudo() : 'Utilisateur non connecté';

        $participants = $participantRepository->findAll();
        $sorties = $sortieRepository->findBy([], ['dateLimiteInscription' => 'ASC']);
        $campus = $campusRepository->findBy([]);

        return $this->render('global/accueil.html.twig', [
            'controller_name' => 'AccueilController',
            'date' => $frDate,
            'username' => $username,
            'participants' => $participants,
            'sorties' => $sorties,
            'campus' => $campus,
        ]);
    }

    #[Route('/search', name: 'app_search')]
    public function search(ParticipantRepository $participantRepository, SortieRepository $sortieRepository): Response
    {
        $date = new DateTime();
        $frDate = $date->format('d/m/Y');
        $participants = $participantRepository->findAll()[2]->getNom();
        $sortie = $sortieRepository->findAll();

        return $this->render('global/search.html.twig', [
            'controller_name' => 'AccueilController',
            'date' => $frDate,
            'participants' => $participants,
            'sortie' => $sortie,
        ]);
    }
}
