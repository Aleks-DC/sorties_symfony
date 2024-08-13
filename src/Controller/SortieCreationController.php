<?php

namespace App\Controller;

use App\Entity\Sortie;
use App\Form\SortieCreationType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SortieCreationController extends AbstractController
{
    #[Route('/create/sortie', name: 'app_sortie_creation')]
    public function index(Request $request, EntityManagerInterface $entityManager): Response
    {
        $sortie = new Sortie();

        $form = $this->createForm(SortieCreationType::class, $sortie);

        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($sortie);
            $entityManager->flush();
        }
        // Si le formulaire est soumis alors :
        // Tu enregistres les datas en BDD
        // Tu envoies un message de confirmation de création de compte
        // Tu rediriges l'utilisateur vers la page de connexion

        return $this->render('sortie_creation/index.html.twig', [
            'sortieCreationForm' => $form->createView()
        ]);
    }
}
