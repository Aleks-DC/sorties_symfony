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
use Symfony\Component\HttpFoundation\Request;

class AccueilController extends AbstractController
{
    #[Route('/', name: 'app_accueil')]
    public function index(Request $request, ParticipantRepository $participantRepository, SortieRepository $sortieRepository, CampusRepository $campusRepository, Security $security): Response
    {
        $date = new DateTime();
        $frDate = $date->format('d/m/Y');

        $user = $security->getUser();
        $username = $user instanceof Participant ? $user->getPseudo() : 'Utilisateur non connecté';

        // Récupération des paramètres de recherche
        $searchTerm = $request->query->get('searchTerm', '');
        $dateFrom = $request->query->get('dateFrom');
        $dateTo = $request->query->get('dateTo');
        $organisateur = $request->query->get('organisateur');
        $inscrit = $request->query->get('inscrit');
        $nonInscrit = $request->query->get('non_inscrit');
        $passees = $request->query->get('passees');
        $campus = $request->query->get('campus');

        // Construction des critères de filtrage
        $qb = $sortieRepository->createQueryBuilder('s');

        if (!empty($searchTerm)) {
            $qb->andWhere('s.nom LIKE :searchTerm')
               ->setParameter('searchTerm', '%' . $searchTerm . '%');
        }

        if (!empty($dateFrom)) {
            $qb->andWhere('s.dateHeureDebut >= :dateFrom')
               ->setParameter('dateFrom', new DateTime($dateFrom));
        }

        if (!empty($dateTo)) {
            $qb->andWhere('s.dateHeureDebut <= :dateTo')
               ->setParameter('dateTo', new DateTime($dateTo));
        }

        if (!empty($organisateur)) {
            $qb->andWhere('s.organisateur = :organisateur')
               ->setParameter('organisateur', $user);
        }

        if (!empty($inscrit)) {
            $qb->andWhere(':user MEMBER OF s.participants')
               ->setParameter('user', $user);
        }

        if (!empty($nonInscrit)) {
            $qb->andWhere(':user NOT MEMBER OF s.participants')
               ->setParameter('user', $user);
        }

        if (!empty($passees)) {
            $qb->andWhere('s.dateHeureDebut < :now')
               ->setParameter('now', new DateTime());
        }

        if (!empty($campus)) {
            $qb->andWhere('s.campus = :campus')
               ->setParameter('campus', $campus);
        }

        $sorties = $qb->getQuery()->getResult();

        $campusList = $campusRepository->findAll();

        return $this->render('global/accueil.html.twig', [
            'controller_name' => 'AccueilController',
            'date' => $frDate,
            'username' => $username,
            'sorties' => $sorties,
            'searchTerm' => $searchTerm,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'campusList' => $campusList,
            'selectedCampus' => $campus,
            'isOrganisateur' => $organisateur,
            'isInscrit' => $inscrit,
            'isNonInscrit' => $nonInscrit,
            'isPassees' => $passees,
        ]);
    }
}
