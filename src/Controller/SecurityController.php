<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();

        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }
    #[Route('/login/success', name: 'login_success')]
    public function onLoginSuccess(Request $request): RedirectResponse
    {
        // Récupère l'URL cible depuis la session
        $targetPath = $this->getTargetPath($request->getSession(), 'main');

        // Si une URL cible est enregistrée, redirige l'utilisateur vers cette URL
        if ($targetPath) {
            return new RedirectResponse($targetPath);
        }

        // Sinon, redirige vers la page d'accueil
        return $this->redirectToRoute('app_accueil');
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
