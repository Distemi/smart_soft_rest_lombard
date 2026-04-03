<?php

namespace App\Controller;

use App\Repository\ClientRepository;
use App\Repository\WorkplaceRepository;
use App\Repository\PawnTicketRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    private const int WORKPLACE_RELATIONS_PAGE_SIZE = 10;

    #[Route('/', name: 'app_admin_dashboard', methods: ['GET'])]
    public function dashboard(
        ClientRepository $clientRepository,
        PawnTicketRepository $pawnTicketRepository
    ): Response {
        $totalClients = $clientRepository->countAll();
        $totalTickets = $pawnTicketRepository->countAll();
        $openTickets = $pawnTicketRepository->countOpen();

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
    public function workplaceView(int $id, Request $request, WorkplaceRepository $workplaceRepository): Response
    {
        $workplace = $workplaceRepository->find($id);

        if (!$workplace) {
            throw $this->createNotFoundException('Филиал не найден');
        }

        $uniqueClientsCount = $workplaceRepository->getUniqueClientsCount($id);
        $pawnTicketsCount = $workplaceRepository->getPawnTicketsCount($id);
        $clientsPage = max(1, $request->query->getInt('clientsPage', 1));
        $ticketsPage = max(1, $request->query->getInt('ticketsPage', 1));
        $clientsPagesCount = max(1, (int) ceil($uniqueClientsCount / self::WORKPLACE_RELATIONS_PAGE_SIZE));
        $ticketsPagesCount = max(1, (int) ceil($pawnTicketsCount / self::WORKPLACE_RELATIONS_PAGE_SIZE));
        $clientsPage = min($clientsPage, $clientsPagesCount);
        $ticketsPage = min($ticketsPage, $ticketsPagesCount);

        return $this->render('admin/workplace_view.html.twig', [
            'workplace' => $workplace,
            'uniqueClientsCount' => $uniqueClientsCount,
            'workplaceClients' => $workplaceRepository->findUniqueClientsPage(
                $id,
                $clientsPage,
                self::WORKPLACE_RELATIONS_PAGE_SIZE
            ),
            'workplaceTickets' => $workplaceRepository->findPawnTicketsPage(
                $id,
                $ticketsPage,
                self::WORKPLACE_RELATIONS_PAGE_SIZE
            ),
            'clientsPagination' => [
                'page' => $clientsPage,
                'pagesCount' => $clientsPagesCount,
                'pageSize' => self::WORKPLACE_RELATIONS_PAGE_SIZE,
            ],
            'ticketsPagination' => [
                'page' => $ticketsPage,
                'pagesCount' => $ticketsPagesCount,
                'pageSize' => self::WORKPLACE_RELATIONS_PAGE_SIZE,
                'totalCount' => $pawnTicketsCount,
            ],
        ]);
    }

    #[Route(
        '/client/{clientType}/{externalId}',
        name: 'app_admin_client_view',
        requirements: ['clientType' => 'client|natural_person|legal_person', 'externalId' => '\d+'],
        methods: ['GET']
    )]
    public function clientView(string $clientType, int $externalId, ClientRepository $clientRepository): Response
    {
        $client = $clientRepository->findOneByTypeAndExternalId($clientType, $externalId);

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

}
