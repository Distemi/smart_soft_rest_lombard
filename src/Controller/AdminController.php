<?php

namespace App\Controller;

use App\Repository\ClientRepository;
use App\Repository\WorkplaceRepository;
use App\Repository\PawnTicketRepository;
use App\Repository\ApiLogRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    #[Route('/', name: 'app_admin_dashboard', methods: ['GET'])]
    public function dashboard(
        ClientRepository $clientRepository,
        PawnTicketRepository $pawnTicketRepository
    ): Response {
        $totalClients = count($clientRepository->findAll());
        $totalTickets = count($pawnTicketRepository->findAll());
        $openTickets = count($pawnTicketRepository->findAllOpen());

        return $this->render('admin/dashboard.html.twig', [
            'total_clients' => $totalClients,
            'total_tickets' => $totalTickets,
            'open_tickets' => $openTickets,
        ]);
    }

    #[Route('/clients', name: 'app_admin_clients', methods: ['GET'])]
    public function clients(ClientRepository $clientRepository): Response
    {
        $clients = $clientRepository->findAll();

        return $this->render('admin/clients.html.twig', [
            'clients' => $clients,
        ]);
    }

    #[Route('/workplaces', name: 'app_admin_workplaces', methods: ['GET'])]
    public function workplaces(WorkplaceRepository $workplaceRepository): Response
    {
        $workplaces = $workplaceRepository->findBy([], ['title' => 'ASC', 'city' => 'ASC']);
        $clientsStats = $workplaceRepository->getUniqueClientsCountByWorkplace();

        return $this->render('admin/workplaces.html.twig', [
            'workplaces' => $workplaces,
            'clientsStats' => $clientsStats,
        ]);
    }

    #[Route('/workplace/{id}', name: 'app_admin_workplace_view', methods: ['GET'])]
    public function workplaceView(int $id, WorkplaceRepository $workplaceRepository): Response
    {
        $workplace = $workplaceRepository->find($id);

        if (!$workplace) {
            throw $this->createNotFoundException('Филиал не найден');
        }

        $uniqueClientsCount = $workplaceRepository->getUniqueClientsCount($id);

        return $this->render('admin/workplace_view.html.twig', [
            'workplace' => $workplace,
            'uniqueClientsCount' => $uniqueClientsCount,
        ]);
    }

    #[Route('/client/{id}', name: 'app_admin_client_view', methods: ['GET'])]
    public function clientView(int $id, ClientRepository $clientRepository): Response
    {
        $client = $clientRepository->find($id);

        if (!$client) {
            throw $this->createNotFoundException('Клиент не найден');
        }

        return $this->render('admin/client_view.html.twig', [
            'client' => $client,
        ]);
    }

    #[Route('/tickets', name: 'app_admin_tickets', methods: ['GET'])]
    public function tickets(PawnTicketRepository $pawnTicketRepository): Response
    {
        $tickets = $pawnTicketRepository->findAllOrderedByStatus();

        return $this->render('admin/tickets.html.twig', [
            'tickets' => $tickets,
        ]);
    }

    #[Route('/ticket/{workplaceId}/{ticketNumber}', name: 'app_admin_ticket_view', methods: ['GET'])]
    public function ticketView(int $workplaceId, string $ticketNumber, PawnTicketRepository $pawnTicketRepository): Response
    {
        $ticket = $pawnTicketRepository->findOneBy([
            'workplace' => $workplaceId,
            'ticketNumber' => $ticketNumber,
        ]);

        if (!$ticket) {
            throw $this->createNotFoundException('Залоговый билет не найден');
        }

        return $this->render('admin/ticket_view.html.twig', [
            'ticket' => $ticket,
        ]);
    }

    #[Route('/api-logs', name: 'app_admin_api_logs', methods: ['GET'])]
    public function apiLogs(Request $request, ApiLogRepository $apiLogRepository): Response
    {
        $perPage = 50;
        $requestedPage = max(1, $request->query->getInt('page', 1));
        $totalLogs = $apiLogRepository->getTotalCount();
        $totalPages = max(1, (int) ceil($totalLogs / $perPage));
        $page = min($requestedPage, $totalPages);
        $logs = $apiLogRepository->findPaginatedOrderedByCreatedAt($page, $perPage);

        return $this->render('admin/api_logs.html.twig', [
            'logs' => $logs,
            'page' => $page,
            'perPage' => $perPage,
            'totalLogs' => $totalLogs,
            'totalPages' => $totalPages,
        ]);
    }
}
