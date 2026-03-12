<?php

namespace App\Controller;

use App\Entity\Client;
use App\Form\ClientLoginFormType;
use App\Repository\PawnTicketRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use LogicException;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class ClientPortalController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_tickets_list');
        }

        return $this->redirectToRoute('app_client_auth');
    }

    #[Route('/auth', name: 'app_client_auth', methods: ['GET', 'POST'])]
    public function auth(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_tickets_list');
        }

        $form = $this->createForm(ClientLoginFormType::class, [
            'ticket_number' => $authenticationUtils->getLastUsername(),
        ]);

        return $this->render('client_portal/auth.html.twig', [
            'form' => $form,
            'error' => $authenticationUtils->getLastAuthenticationError(),
        ]);
    }

    #[Route('/logout', name: 'app_client_logout', methods: ['GET'])]
    public function logout(): void
    {
        throw new LogicException('Some error occurred.');
    }

    #[Route('/tickets', name: 'app_tickets_list', methods: ['GET'])]
    public function ticketsList(PawnTicketRepository $pawnTicketRepository): Response
    {
        /** @var Client $client */
        $client = $this->getUser();

        $openTickets = $pawnTicketRepository->findOpenTicketsByClient($client);

        return $this->render('client_portal/tickets_list.html.twig', [
            'client' => $client,
            'tickets' => $openTickets,
        ]);
    }

    #[Route('/ticket/{workplaceId}/{ticketNumber}', name: 'app_ticket_view', methods: ['GET'])]
    public function ticketView(int $workplaceId, string $ticketNumber, PawnTicketRepository $pawnTicketRepository): Response
    {
        /** @var Client $client */
        $client = $this->getUser();

        $ticket = $pawnTicketRepository->findOneBy([
            'workplace' => $workplaceId,
            'ticketNumber' => $ticketNumber,
        ]);

        if (!$ticket || $ticket->getClient() !== $client) {
            $this->addFlash('error', 'Залоговый билет не найден');
            return $this->redirectToRoute('app_tickets_list');
        }

        return $this->render('client_portal/ticket_view.html.twig', [
            'client' => $client,
            'ticket' => $ticket,
        ]);
    }

    #[Route('/client-profile', name: 'app_client_profile', methods: ['GET'])]
    public function clientProfile(): Response
    {
        /** @var Client $client */
        $client = $this->getUser();

        return $this->render('client_portal/client_profile.html.twig', [
            'client' => $client,
        ]);
    }
}
