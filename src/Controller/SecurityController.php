<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\HttpFoundation\RedirectResponse;


class SecurityController extends AbstractController
{
    use TargetPathTrait;
    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): \Symfony\Component\HttpFoundation\Response
    {
        $error = $authenticationUtils->getLastAuthenticationError();
    
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
