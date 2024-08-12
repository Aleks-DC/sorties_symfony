<?php

namespace App\DataFixtures;

use App\Entity\Etat;
use App\Entity\Lieu;
use App\Entity\Participant;
use App\Entity\Campus;
use App\Entity\Ville;
use App\Entity\Sortie;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Faker\Factory;

class AppFixtures extends Fixture
{

    private UserPasswordHasherInterface $hasher;

    public function __construct(UserPasswordHasherInterface $hasher)
    {
        $this->hasher = $hasher;
    }

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

        // Création des états
        $etats = [
            'Créée',
            'Ouverte',
            'Clôturée',
            'Annulée',
            'Passée',
        ];

        foreach ($etats as $etatName) {
            $etat = new Etat();
            $etat->setLibelle($etatName);
            $manager->persist($etat);
            $manager->flush();
        }

        // Création des campus
        $campusNames = ['Nante', 'Renne', 'Quimper', 'Niort'];

        foreach ($campusNames as $campusName) {
            $campus = new Campus();
            $campus->setNom($campusName);
            $manager->persist($campus);
            $manager->flush();
        }

        // Création des villes
        $villes = [
            ['nom' => 'Paris', 'codePostal' => 75000],
            ['nom' => 'Lyon', 'codePostal' => 69000],
            ['nom' => 'Marseille', 'codePostal' => 13000],
        ];

        foreach ($villes as $villeData) {
            $ville = new Ville();
            $ville->setNom($villeData['nom']);
            $ville->setCodePostal($villeData['codePostal']);
            $manager->persist($ville);
            $manager->flush();
        }

        // Création des participants
        for ($i = 0; $i < 10; $i++) {
            $participant = new Participant();
            $participant->setNom($faker->lastName);
            $participant->setPrenom($faker->firstName);
            $participant->setTelephone($faker->phoneNumber);
            $participant->setMail($faker->email);
            $campus = $manager->getRepository(Campus::class)->findOneBy([]);
                if (!$campus) {
                    throw new \Exception('Aucune instance de Campus trouvée');
                }
            $participant->setCampusAffilie($campus);
            $password = $this->hasher->hashPassword($participant, 'password');
            $participant->setMotDePasse($password);
            $participant->setAdministrateur(false);
            $participant->setActif(true);
            $participant->setPseudo($faker->userName);
            $manager->persist($participant);
            $manager->flush();
        }

        for ($i = 0; $i < 20; $i++) {
            $sortie = new Sortie();
            $sortie->setNom($faker->sentence);
            $sortie->setInfosSortie($faker->paragraph);
        
            // Date et heure de début aléatoire
            $startDate = $faker->dateTimeBetween('+2 week');
            $sortie->setDateHeureDebut($startDate);
        
            // Durée aléatoire entre 1 et 5 heures
            $duration = $faker->numberBetween(1, 5);
            $endDate = clone $startDate;
            $endDate->modify("+{$duration} hours");
            $endDate->modify("+1 day");
            $sortie->setDuree($endDate);
        
            // Date limite d'inscription aléatoire avant la date de début
            $inscriptionDeadline = $startDate->modify('-'.rand(1, 7).' days');
            $sortie->setDateLimiteInscription($inscriptionDeadline);
        
            // Nombre d'inscriptions maximum aléatoire
            $maxInscriptions = $faker->numberBetween(5, 20);
            $sortie->setNbInscriptionsMax($maxInscriptions);
        
            // Infos supplémentaires aléatoires
            $sortie->setInfosSortie($faker->text);
        
            $lieu = new Lieu();
            $lieu->setNom($faker->streetName);
            $lieu->setVilles($ville);
            $lieu->setRue($faker->address);
            $lieu->setLatitude($faker->latitude);
            $lieu->setLongitude($faker->longitude);
            $manager->persist($lieu);
            $manager->flush();

            // Lieu aléatoire
            $lieu = $manager->getRepository(Lieu::class)->findOneBy([]);
                if (!$lieu) {
                    throw new \Exception('Aucun lieu trouvé');
                }
            $sortie->setLieu($lieu);

            // Organisateur aléatoire
            $organisateur = $manager->getRepository(Participant::class)->findOneBy([]);
            if (!$organisateur) {
                throw new \Exception('Aucun participant trouvé');
            }
            $sortie->setOrganisateur($organisateur);

            $campus = $manager->getRepository(Campus::class)->findOneBy([]);
            if (!$campus) {
                throw new \Exception('Aucune instance de Campus trouvée');
            }
            $sortie->setSiteOrganisateur($campus);
        
            // Etat aléatoire (sauf "Passée")
            $etat = null;
            $currentDate = new \DateTime();
            if ($currentDate < $sortie->getDateHeureDebut()) {
                $etat = $manager->getRepository(Etat::class)->findOneBy(['libelle' => 'Créée']);
                if (!$etat) {
                    $etat = $manager->getRepository(Etat::class)->findOneBy(['libelle' => 'Ouverte']);
                }
            } else {
                $etat = $manager->getRepository(Etat::class)->findOneBy(['libelle' => 'Clôturée']);
            }
            $sortie->setEtat($etat);
        
            $manager->persist($sortie);
            $manager->flush();
        }
    }
}
