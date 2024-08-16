<?php

namespace App\Controller;

use App\Entity\Etat;
use App\Entity\Participant;
use App\Repository\CampusRepository;
use App\Repository\ParticipantRepository;
use App\Repository\SortieRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use DateTime;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AccueilController extends AbstractController
{
    #[Route('/', name: 'app_accueil')]
    public function index(ParticipantRepository $participantRepository, SortieRepository $sortieRepository, EntityManagerInterface $entityManager, CampusRepository $campusRepository, Security $security): Response
    {
        $date = new DateTime();
        $frDate = $date->format('d/m/Y');

        // Obtenir l'utilisateur connecté
        $user = $security->getUser();
        $username = $user instanceof Participant ? $user->getPseudo() : 'Utilisateur non connecté';

        $participants = $participantRepository->findAll();
        $campus = $campusRepository->findBy([]);
        $sorties = $sortieRepository->findBy([], ['dateLimiteInscription' => 'ASC']);

        // Mise à jour et archivage des sorties
        $this->updateEtatSorties($sorties, $entityManager);
        $this->archiverSorties($sorties, $entityManager);

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
