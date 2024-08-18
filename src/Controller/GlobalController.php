<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class GlobalController extends AbstractController
{
    #[Route('/global', name: 'app_global')]
    public function index(): Response
    {
        return $this->render('global/annulerSortie.html.twig', [
            'controller_name' => 'GlobalController',
        ]);
    }
}
