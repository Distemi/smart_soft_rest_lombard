<?php

namespace App\Controller;

use App\Form\AdminLoginFormType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use LogicException;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route('/admin/login', name: 'app_admin_login', methods: ['GET', 'POST'])]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_admin_dashboard');
        }

        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        $form = $this->createForm(AdminLoginFormType::class, [
            '_username' => $lastUsername,
        ]);

        return $this->render('security/login.html.twig', [
            'form' => $form,
            'error' => $error,
        ]);
    }

    #[Route('/admin/logout', name: 'app_admin_logout', methods: ['GET'])]
    public function logout(): void
    {
        throw new LogicException('Some error occurred.');
    }
}
