<?php

namespace App\Service;

use App\Entity\PawnGoodCategory;
use App\Entity\Workplace;
use App\Entity\Client;
use App\Entity\PawnTicket;
use App\Entity\PawnGood;
use App\Repository\WorkplaceRepository;
use App\Repository\ClientRepository;
use App\Repository\PawnTicketRepository;
use App\Repository\PawnGoodCategoryRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Log\LoggerInterface;
use RuntimeException;

class DataSyncService
{
    private const array TICKET_EXTERNAL_ID_KEYS = ['id', 'pawn_chain_id'];
    private const array PAWN_TICKET_ID_KEYS = ['id', 'ticket_id', 'pawn_ticket_id', 'external_id'];
    private const array DATE_FORMATS = ['d.m.Y, H:i', 'd.m.Y'];

    private const array GOODS_TYPE_MAPPING = [
        'jewelry' => 'jewelry',
        'vehicle' => 'auto',
        'auto' => 'auto',
        'mobile' => 'electronics',
        'tablet' => 'electronics',
        'tv_video' => 'electronics',
        'watch' => 'watches',
        'watches' => 'watches',
        'clothes' => 'electronics',
        'pc_component' => 'electronics',
        'other' => 'other',
    ];

    public function __construct(
        private readonly SmartLombardApiService $apiService,
        private readonly WorkplaceRepository $workplaceRepository,
        private readonly ClientRepository $clientRepository,
        private readonly PawnTicketRepository $pawnTicketRepository,
        private readonly PawnGoodCategoryRepository $categoryRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger
    ) {
    }

    public function syncAll(): array
    {
        $stats = [
            'workplaces_created' => 0,
            'workplaces_updated' => 0,
            'clients_created' => 0,
            'clients_updated' => 0,
            'tickets_created' => 0,
            'tickets_updated' => 0,
            'items_synced' => 0,
            'categories_created' => 0,
            'categories_updated' => 0,
            'errors' => [],
        ];

        $this->syncCategories($stats);
        $this->syncWorkplaces($stats);
        $workplaces = $this->workplaceRepository->findAllActive();

        foreach ($workplaces as $workplace) {
            try {
                $this->syncWorkplaceData($workplace, $stats);
            } catch (Exception $e) {
                $stats['errors'][] = sprintf(
                    'Workplace %s: %s',
                    $workplace->getDisplayTitle(),
                    $e->getMessage()
                );
                $this->logger->error('Ошибка синхронизации workplace', [
                    'workplace_id' => $workplace->getId(),
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->entityManager->flush();

        return $stats;
    }

    private function syncCategories(array &$stats): void
    {
        try {
            $categories = $this->apiService->getAllCategories();

            foreach ($categories as $categoryData) {
                $externalId = (int) $categoryData['id'];
                $category = $this->categoryRepository->findOneBy(['externalId' => $externalId]);

                if (!$category) {
                    $category = $this->categoryRepository->findOneBy(['code' => $categoryData['name']]);
                    if (!$category) {
                        $category = new PawnGoodCategory();
                        $stats['categories_created']++;
                    } else {
                        $stats['categories_updated']++;
                    }
                } else {
                    $stats['categories_updated']++;
                }

                $category->setExternalId($externalId)
                    ->setCode($categoryData['name'] ?? 'category_' . $externalId)
                    ->setName($categoryData['name'] ?? 'Категория ' . $externalId);

                $this->entityManager->persist($category);
            }

            $this->entityManager->flush();
        } catch (Exception $e) {
            $stats['errors'][] = 'Ошибка синхронизации категорий: ' . $e->getMessage();
            $this->logger->error('Ошибка синхронизации категорий', ['error' => $e->getMessage()]);
        }
    }

    private function syncWorkplaces(array &$stats): void
    {
        try {
            $workplaces = $this->apiService->getWorkplaces();

            foreach ($workplaces as $workplaceData) {
                $externalId = (int) $workplaceData['id'];
                $workplace = $this->workplaceRepository->findByExternalId($externalId);

                if (!$workplace) {
                    $workplace = new Workplace();
                    $workplace->setExternalId($externalId);
                    $stats['workplaces_created']++;
                } else {
                    $stats['workplaces_updated']++;
                }

                $workplace->setTitle($workplaceData['title'] ?? null)
                    ->setCity($workplaceData['city'] ?? null)
                    ->setAddress($workplaceData['address'] ?? null)
                    ->setPhone($workplaceData['phone'] ?? null)
                    ->setIsActive($workplaceData['is_active'] ?? true)
                    ->setUpdatedAt(new DateTime());

                $this->entityManager->persist($workplace);
            }

            $this->entityManager->flush();
        } catch (Exception $e) {
            $stats['errors'][] = 'Ошибка синхронизации workplaces: ' . $e->getMessage();
            $this->logger->error('Ошибка синхронизации workplaces', ['error' => $e->getMessage()]);
        }
    }

    private function syncWorkplaceData(Workplace $workplace, array &$stats): void
    {
        $workplaceId = (int) $workplace->getExternalId();
        $tickets = $this->apiService->getAllPawnTicketsByWorkplace($workplaceId);

        $clientIds = $this->collectClientIds($tickets);
        $clientsData = $this->fetchClients($clientIds, $stats);
        $syncedClients = $this->syncClients($clientsData, $stats);

        foreach ($tickets as $ticketData) {
            try {
                $this->syncPawnTicket($ticketData, $syncedClients, $workplace, $stats);
            } catch (Exception $e) {
                $stats['errors'][] = sprintf(
                    'Билет %s (филиал %s): %s',
                    $ticketData['id'] ?? '?',
                    $workplace->getDisplayTitle(),
                    $e->getMessage()
                );
                $this->logger->error('Ошибка синхронизации билета', [
                    'ticket_id' => $ticketData['id'] ?? null,
                    'workplace_id' => $workplace->getId(),
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    private function syncClients(array $clientsData, array &$stats): array
    {
        $clients = [];
        $existingClients = $this->clientRepository->findIndexedByExternalIds(array_keys($clientsData));

        foreach ($clientsData as $clientId => $clientData) {
            $clients[(string) $clientId] = $this->syncClient($clientData, $stats, $existingClients);
        }

        return $clients;
    }

    private function collectClientIds(array $tickets): array
    {
        $ids = [];
        foreach ($tickets as $ticket) {
            if (!empty($ticket['client_natural_person_id'])) {
                $ids[$ticket['client_natural_person_id']] = true;
            }
        }
        return array_keys($ids);
    }

    private function fetchClients(array $clientIds, array &$stats): array
    {
        $clientsData = [];
        foreach ($clientIds as $clientId) {
            try {
                $clientsData[$clientId] = $this->apiService->getClient($clientId);
            } catch (Exception $e) {
                $stats['errors'][] = sprintf('Клиент %d: %s', $clientId, $e->getMessage());
            }
        }
        return $clientsData;
    }

    private function syncPawnTicket(array $data, array $clients, Workplace $workplace, array &$stats): void
    {
        $externalId = $this->extractNumericValue($data, self::TICKET_EXTERNAL_ID_KEYS);
        $ticketNumber = (string) ($data['document_number'] ?? '');

        if ($ticketNumber === '') {
            throw new RuntimeException('Пустой номер билета (document_number)');
        }

        $ticket = $this->pawnTicketRepository->findOneBy([
            'workplace' => $workplace,
            'ticketNumber' => $ticketNumber,
        ]);

        if (!$ticket && $externalId !== null) {
            $ticket = $this->pawnTicketRepository->findByExternalId($externalId);
        }

        if (!$ticket) {
            $ticket = new PawnTicket();
            $stats['tickets_created']++;
        } else {
            $stats['tickets_updated']++;
        }

        if ($externalId !== null) {
            $ticket->setExternalId($externalId);
        }

        $ticket->setTicketNumber($ticketNumber)
            ->setStatus((int) ($data['status'] ?? 0))
            ->setLoanAmount((string) ($data['loan_amount'] ?? '0'))
            ->setPledgeAmount((string) ($data['loan_amount'] ?? '0'))
            ->setInterestRate((string) ($data['accrued_percent'] ?? '0'))
            ->setCurrentDebt((string) ($data['pawn_ticket_debt']['sum_debt'] ?? '0'))
            ->setWorkplace($workplace)
            ->setUpdatedAt(new DateTime());

        $issueDate = $this->parseApiDate($data['open_date'] ?? null);
        if ($issueDate !== null) {
            $ticket->setIssueDate($issueDate);
        }

        $dueDate = $this->parseApiDate($data['end_date'] ?? null);
        if ($dueDate !== null) {
            $ticket->setDueDate($dueDate);
        }

        $ticket->setCloseDate($this->parseApiDate($data['close_date'] ?? null));

        $clientId = $data['client_natural_person_id'] ?? null;
        if ($clientId && isset($clients[(string) $clientId])) {
            $ticket->setClient($clients[(string) $clientId]);
        }

        $this->entityManager->persist($ticket);

        $pawnTicketId = $this->extractPawnTicketId($data);

        if ($pawnTicketId === null) {
            $resolvedTicketId = $this->apiService->resolvePawnTicketId(
                (int) $workplace->getExternalId(),
                $ticketNumber,
                isset($data['pawn_chain_id']) && is_numeric((string) $data['pawn_chain_id'])
                    ? (int) $data['pawn_chain_id']
                    : null
            );

            if ($resolvedTicketId !== null) {
                $pawnTicketId = $resolvedTicketId;
            }
        }
        $this->logger->info('Попытка получить предметы билета', [
            'ticket_id' => $pawnTicketId,
            'ticket_number' => $ticketNumber,
            'id_isset' => isset($data['id']),
            'id_value' => $data['id'] ?? null,
        ]);
        
        if ($pawnTicketId) {
            try {
                $goods = $this->apiService->getAllPawnGoods($pawnTicketId);
                
                if (empty($goods)) {
                    $this->logger->info('Нет предметов для билета', [
                        'ticket_id' => $pawnTicketId,
                        'ticket_number' => $ticketNumber
                    ]);
                } else {
                    foreach ($ticket->getPawnGoods()->toArray() as $oldGood) {
                        $ticket->removePawnGood($oldGood);
                    }

                    $addedCount = 0;
                    foreach ($goods as $goodData) {
                        try {
                            $this->syncPawnGood($ticket, $goodData);
                            $stats['items_synced']++;
                            $addedCount++;
                        } catch (Exception $goodError) {
                            $stats['errors'][] = sprintf(
                                'Предмет билета %s: %s',
                                $ticketNumber,
                                $goodError->getMessage()
                            );
                            $this->logger->error('Ошибка синхронизации предмета', [
                                'ticket_id' => $pawnTicketId,
                                'good_name' => $goodData['name'] ?? 'unknown',
                                'error' => $goodError->getMessage()
                            ]);
                        }
                    }
                    
                    $this->logger->info('Синхронизировано предметов для билета', [
                        'ticket_id' => $pawnTicketId,
                        'ticket_number' => $ticketNumber,
                        'count' => $addedCount,
                        'total' => count($goods)
                    ]);
                }
            } catch (Exception $e) {
                $stats['errors'][] = sprintf('Получение предметов билета %s: %s', $externalId ?? '?', $e->getMessage());
                $this->logger->error('Ошибка при получении предметов билета', [
                    'ticket_id' => $pawnTicketId,
                    'ticket_number' => $ticketNumber,
                    'error' => $e->getMessage()
                ]);
            }
        } else {
            $availableKeys = implode(', ', array_keys($data));
            $stats['errors'][] = sprintf(
                'Билет %s: не найден ID для загрузки имущества. Доступные ключи: %s',
                $ticketNumber,
                $availableKeys
            );

            $this->logger->warning('Не удалось получить ID билета для загрузки предметов', [
                'ticket_number' => $ticketNumber,
                'available_keys' => array_keys($data),
                'id_candidates' => [
                    'id' => $data['id'] ?? null,
                    'ticket_id' => $data['ticket_id'] ?? null,
                    'pawn_ticket_id' => $data['pawn_ticket_id'] ?? null,
                    'external_id' => $data['external_id'] ?? null,
                    'pawn_ticket.id' => $data['pawn_ticket']['id'] ?? null,
                    'pawn_ticket.ticket_id' => $data['pawn_ticket']['ticket_id'] ?? null,
                    'pawn_ticket.external_id' => $data['pawn_ticket']['external_id'] ?? null,
                ],
            ]);
        }
    }

    private function syncClient(array $data, array &$stats, array &$existingClients): Client
    {
        $externalId = (int) $data['id'];
        $client = $existingClients[$externalId] ?? null;

        if (!$client) {
            $client = new Client();
            $client->setExternalId($externalId);
            $stats['clients_created']++;
            $existingClients[$externalId] = $client;
        } else {
            $stats['clients_updated']++;
        }

        $client->setSurname($data['last_name'] ?? '')
            ->setName($data['name'] ?? '')
            ->setPatronymic($data['patronymic'] ?? null)
            ->setPhone($data['phone'] ?? null)
            ->setEmail($data['email'] ?? null)
            ->setUpdatedAt(new DateTime());

        $this->entityManager->persist($client);

        return $client;
    }

    private function syncPawnGood(PawnTicket $ticket, array $data): void
    {
        $good = new PawnGood();
        $good->setPawnTicket($ticket)
            ->setName($data['name'] ?? 'Без названия')
            ->setDescription($data['description'] ?? null)
            ->setEstimatedValue((string) ($data['estimate_price'] ?? '0'));

        if (!empty($data['type'])) {
            $apiType = (string) $data['type'];
            $goodType = self::GOODS_TYPE_MAPPING[$apiType] ?? 'other';
            $good->setGoodType($goodType);
        } else {
            $good->setGoodType('other');
        }

        if (!empty($data['category_id'])) {
            $category = $this->categoryRepository->findOneBy(['externalId' => (int) $data['category_id']]);
            if ($category) {
                $good->setCategory($category);
            } else {
                $this->logger->warning('Категория не найдена по externalId', [
                    'category_id' => $data['category_id'],
                    'good_name' => $data['name'] ?? 'unknown'
                ]);
                $defaultCategory = $this->categoryRepository->findOneBy(['code' => 'other']);
                if ($defaultCategory) {
                    $good->setCategory($defaultCategory);
                } else {
                    throw new RuntimeException('Стандартная категория "Прочее" не найдена в системе');
                }
            }
        } else {
            $defaultCategory = $this->categoryRepository->findOneBy(['code' => 'other']);
            if ($defaultCategory) {
                $good->setCategory($defaultCategory);
            } else {
                throw new RuntimeException('Стандартная категория "Прочое" не найдена в системе');
            }
        }

        $ticket->addPawnGood($good);
        $this->entityManager->persist($good);
    }

    private function extractNumericValue(array $data, array $keys): ?int
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $data) && $data[$key] !== null && is_numeric((string) $data[$key])) {
                return (int) $data[$key];
            }
        }

        return null;
    }

    private function extractPawnTicketId(array $data): ?int
    {
        $pawnTicketId = $this->extractNumericValue($data, self::PAWN_TICKET_ID_KEYS);
        if ($pawnTicketId !== null) {
            return $pawnTicketId;
        }

        if (!empty($data['pawn_ticket']) && is_array($data['pawn_ticket'])) {
            return $this->extractNumericValue($data['pawn_ticket'], self::PAWN_TICKET_ID_KEYS);
        }

        return null;
    }

    private function parseApiDate(mixed $rawValue): ?\DateTimeInterface
    {
        if (!is_string($rawValue) || trim($rawValue) === '') {
            return null;
        }

        foreach (self::DATE_FORMATS as $format) {
            $parsed = DateTime::createFromFormat($format, $rawValue);
            if ($parsed !== false) {
                return $parsed;
            }
        }

        return null;
    }

}
