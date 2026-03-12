<?php

namespace App\Controller;

use App\Entity\PawnTicket;
use App\Http\ApiResponder;
use App\Request\ClientTicketsQuery;
use App\Repository\ClientRepository;
use App\Repository\PawnTicketRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api', name: 'api_')]
class ClientTicketsApiController extends AbstractController
{
    #[Route('/client/tickets', name: 'client_tickets', methods: ['GET'])]
    public function clientTickets(
        Request $request,
        ClientRepository $clientRepository,
        PawnTicketRepository $pawnTicketRepository,
        ValidatorInterface $validator,
        ApiResponder $apiResponder
    ) {
        $queryDto = ClientTicketsQuery::fromArray($request->query->all());
        $violations = $validator->validate($queryDto);

        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $errors[] = [
                    'field' => $violation->getPropertyPath(),
                    'message' => $violation->getMessage(),
                ];
            }

            return $apiResponder->error('Ошибка валидации входных параметров', 400, $errors);
        }

        $fullName = $queryDto->fullName;
        $ticketNumber = $queryDto->ticketNumber;

        $nameParts = preg_split('/\s+/u', $fullName, -1, PREG_SPLIT_NO_EMPTY);
        if ($nameParts === false) {
            return $apiResponder->error('Не удалось обработать fullName', 400);
        }

        $surname = $nameParts[0];
        $name = $nameParts[1];
        $patronymic = count($nameParts) > 2 ? implode(' ', array_slice($nameParts, 2)) : null;

        $matchedClient = $clientRepository->findOneByFullNameAndTicketNumber(
            $surname,
            $name,
            $patronymic,
            $ticketNumber
        );

        if (!$matchedClient) {
            return $apiResponder->error('Клиент с указанным ФИО и номером билета не найден', 404);
        }

        $tickets = $pawnTicketRepository->findAllByClientOrdered($matchedClient);

        return $apiResponder->success([
            'client' => [
                'id' => $matchedClient->getId(),
                'externalId' => $matchedClient->getExternalId(),
                'fullName' => $matchedClient->getFullName(),
                'phone' => $matchedClient->getPhone(),
            ],
            'tickets' => array_map([$this, 'normalizeTicket'], $tickets),
        ]);
    }

    private function normalizeTicket(PawnTicket $ticket): array
    {
        $workplace = $ticket->getWorkplace();

        return [
            'ticketNumber' => $ticket->getTicketNumber(),
            'externalId' => $ticket->getExternalId(),
            'status' => $ticket->getStatus(),
            'statusLabel' => $ticket->getStatusLabel(),
            'issueDate' => $ticket->getIssueDate()?->format('Y-m-d'),
            'dueDate' => $ticket->getDueDate()?->format('Y-m-d'),
            'closeDate' => $ticket->getCloseDate()?->format('Y-m-d'),
            'loanAmount' => $ticket->getLoanAmount(),
            'pledgeAmount' => $ticket->getPledgeAmount(),
            'interestRate' => $ticket->getInterestRate(),
            'currentDebt' => $ticket->getCurrentDebt(),
            'workplace' => [
                'id' => $workplace->getId(),
                'externalId' => $workplace->getExternalId(),
                'title' => $workplace->getTitle(),
                'city' => $workplace->getCity(),
                'address' => $workplace->getAddress(),
                'phone' => $workplace->getPhone(),
                'displayTitle' => $workplace->getDisplayTitle(),
            ],
        ];
    }
}
