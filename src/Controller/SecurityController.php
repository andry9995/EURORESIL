<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route('/auth/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        return $this->render('security/login.html.twig', [
            'last_username' => $authenticationUtils->getLastUsername(),
            'error'         => $authenticationUtils->getLastAuthenticationError(),
        ]);
    }

    #[Route('/auth/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('Cette route est interceptée par le firewall Symfony.');
    }

    #[Route('/', name: 'app_home')]
    public function home(): Response
    {
        $user = $this->getUser();
        if (!$user) return $this->redirectToRoute('app_login');

        if ($user->isPro()) {
            return $this->redirectToRoute('app_dashboard');
        }
        return $this->redirectToRoute('app_wizard_souscripteur');
    }
}
