<?php

namespace App\Controller;

use App\Entity\Participant;
use App\Entity\Etat;
use App\Repository\CampusRepository;
use App\Repository\ParticipantRepository;
use App\Repository\SortieRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use DateTime;
use Symfony\Bundle\SecurityBundle\Security;
use Doctrine\ORM\EntityManagerInterface;
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

        if (!$user) {
            // Redirige ou affiche un message si l'utilisateur n'est pas connecté
            return $this->redirectToRoute('app_login');
        }

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
        $qb->orderBy('s.dateHeureDebut', 'ASC');

        // Exclure les sorties en état archivé
        $qb->join('s.etat', 'e')
            ->andWhere('e.libelle != :archived')
            ->setParameter('archived', Etat::ETAT_ARCHIVEE);

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
            $qb->andWhere(':user MEMBER OF s.estInscrit')
               ->setParameter('user', $user);
        }

        if (!empty($nonInscrit)) {
            $qb->andWhere(':user NOT MEMBER OF s.estInscrit')
               ->setParameter('user', $user);
        }

        if (!empty($passees)) {
            $qb->andWhere('s.dateHeureDebut < :now')
               ->setParameter('now', new DateTime());
        }

        if (!empty($campus)) {
            $qb->andWhere('s.siteOrganisateur = :campus')
               ->setParameter('campus', $campus);
        }

        // Ajout du tri sur la date de début dans l'ordre ascendant
        $qb->orderBy('s.dateHeureDebut', 'ASC');

        $sorties = $qb->getQuery()->getResult();

        $campusList = $campusRepository->findAll();
        $inscrit = $user->getSortiesPrevues();

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
    private function updateEtatSorties(array $sorties, EntityManagerInterface $entityManager): void
    {
        $etatRepository = $entityManager->getRepository(Etat::class);
        $now = new DateTime();

        foreach ($sorties as $sortie) {
            $currentEtat = $sortie->getEtat(); // Obtenez l'objet Etat au lieu de juste le libellé
            $currentEtatLibelle = $currentEtat->getLibelle();

            if ($currentEtatLibelle === Etat::ETAT_ANNULEE || $currentEtatLibelle === Etat::ETAT_ARCHIVEE) {
                continue;
            }

            // Réouvrir les sorties "Activité en cours", "Clôturée" ou "Passée" si date modifiée
            if (($currentEtatLibelle === Etat::ETAT_CLOTUREE || $currentEtatLibelle === Etat::ETAT_PASSEE || $currentEtatLibelle === Etat::ETAT_EN_COURS)
                && $sortie->getDateLimiteInscription() > $now) {
                $etatOuverte = $etatRepository->findOneBy(['libelle' => Etat::ETAT_OUVERTE]);
                if ($etatOuverte) {
                    $sortie->setEtat($etatOuverte);
                    $entityManager->persist($sortie);
                    continue; // Passez à la sortie suivante après avoir mis à jour l'état
                }
            }

            // Gestion de l'état "Clôturée"
            if ($sortie->getDateLimiteInscription() < $now && $currentEtatLibelle === Etat::ETAT_OUVERTE) {
                $etatCloturee = $etatRepository->findOneBy(['libelle' => Etat::ETAT_CLOTUREE]);
                if ($etatCloturee) {
                    $sortie->setEtat($etatCloturee);
                    $entityManager->persist($sortie);
                    continue;
                }
            }

            // Gestion des états "En Cours" et "Passée"
            if ($sortie->getDateHeureDebut() < $now) {
                $dateFin = (clone $sortie->getDateHeureDebut())->modify('+' . $sortie->getDuree() . ' minutes');

                // L'activité est en cours
                if ($now >= $sortie->getDateHeureDebut() && $now <= $dateFin) {
                    $etatActivite = $etatRepository->findOneBy(['libelle' => Etat::ETAT_EN_COURS]);
                    if ($etatActivite) {
                        $sortie->setEtat($etatActivite);
                        $entityManager->persist($sortie);
                    }
                // L'activité est passée
                } elseif ($now > $dateFin) {
                    $etatPasse = $etatRepository->findOneBy(['libelle' => Etat::ETAT_PASSEE]);
                    if ($etatPasse) {
                        $sortie->setEtat($etatPasse);
                        $entityManager->persist($sortie);
                    }
                }
            }
        }
        $entityManager->flush();
    }

    private function archiverSorties(array $sorties, EntityManagerInterface $entityManager): void
    {
        $etatRepository = $entityManager->getRepository(Etat::class);
        $now = new DateTime();

        foreach ($sorties as $sortie) {
            $currentEtat = $sortie->getEtat(); // Obtenez l'objet Etat
            $currentEtatLibelle = $currentEtat->getLibelle();

            if ($currentEtatLibelle === Etat::ETAT_PASSEE || $currentEtatLibelle === Etat::ETAT_ANNULEE) {
                $dateFin = (clone $sortie->getDateHeureDebut())->modify('+' . $sortie->getDuree() . ' minutes');
                $dateLimiteArchivage = (clone $dateFin)->modify('+30 days');

                if ($now > $dateLimiteArchivage) {
                    $etatArchive = $etatRepository->findOneBy(['libelle' => Etat::ETAT_ARCHIVEE]);
                    if ($etatArchive) {
                        $sortie->setEtat($etatArchive);
                        $entityManager->persist($sortie);
                    }
                }
            }
        }
        $entityManager->flush();
    }
}
