<?php

namespace App\Service;

use App\Entity\PawnGoodCategory;
use App\Entity\Workplace;
use App\Entity\Client;
use App\Entity\Currency;
use App\Entity\LegalPerson;
use App\Entity\NaturalPerson;
use App\Entity\PawnTicket;
use App\Entity\PawnGood;
use App\Repository\CurrencyRepository;
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
    private const string CLIENT_TYPE_NATURAL_PERSON = 'natural_person';
    private const string CLIENT_TYPE_LEGAL_PERSON = 'legal_person';
    private const array TICKET_EXTERNAL_ID_KEYS = ['id', 'pawn_chain_id'];
    private const array PAWN_TICKET_ID_KEYS = ['id', 'ticket_id', 'pawn_ticket_id', 'external_id'];
    private const array DATE_FORMATS = ['d.m.Y, H:i', 'd.m.Y'];

    private const array GOODS_TYPES = [
        'jewelry' => 'jewelry',
        'vehicle' => 'vehicle',
        'mobile' => 'mobile',
        'tablet' => 'tablet',
        'tv_video' => 'tv_video',
        'watch' => 'watch',
        'clothes' => 'clothes',
        'kids_clothes' => 'kids_clothes',
        'pc_component' => 'pc_component',
        'other' => 'other',
    ];

    private const int DEFAULT_CATEGORY_ID = 0;
    private const string DEFAULT_CATEGORY_CODE = 'other';

    public function __construct(
        private readonly SmartLombardApiService $apiService,
        private readonly WorkplaceRepository $workplaceRepository,
        private readonly ClientRepository $clientRepository,
        private readonly PawnTicketRepository $pawnTicketRepository,
        private readonly PawnGoodCategoryRepository $categoryRepository,
        private readonly CurrencyRepository $currencyRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger
    ) {
    }

    public function syncAll(): array
    {
        $stats = [
            'workplaces_created' => 0,
            'workplaces_updated' => 0,
            'workplaces_deleted' => 0,
            'workplaces_deactivated' => 0,
            'clients_created' => 0,
            'clients_updated' => 0,
            'clients_deleted' => 0,
            'tickets_created' => 0,
            'tickets_updated' => 0,
            'tickets_deleted' => 0,
            'items_synced' => 0,
            'categories_created' => 0,
            'categories_updated' => 0,
            'errors' => [],
        ];

        $this->syncCategories($stats);
        $activeExternalIds = $this->syncWorkplaces($stats);
        if ($activeExternalIds !== null) {
            $this->cleanupDeletedWorkplaces($activeExternalIds, $stats);
        }
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

        $this->cleanupOrphanClients($stats);

        $this->entityManager->flush();

        return $stats;
    }

    private function syncCategories(array &$stats): void
    {
        try {
            $categories = $this->apiService->getAllCategories();

            foreach ($categories as $categoryData) {
                $categoryId = (int) $categoryData['id'];
                $category = $this->categoryRepository->findById($categoryId);

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

                $category->setId($categoryId)
                    ->setCode($categoryData['name'] ?? 'category_' . $categoryId)
                    ->setName($categoryData['name'] ?? 'Категория ' . $categoryId)
                    ->setSystemCategory((int) ($categoryData['system_category'] ?? 0));

                $this->entityManager->persist($category);
            }

            $this->ensureFallbackCategory();
            $this->entityManager->flush();
        } catch (Exception $e) {
            $stats['errors'][] = 'Ошибка синхронизации категорий: ' . $e->getMessage();
            $this->logger->error('Ошибка синхронизации категорий', ['error' => $e->getMessage()]);
        }
    }

    private function syncWorkplaces(array &$stats): ?array
    {
        try {
            $workplaces = $this->apiService->getWorkplaces();
            $activeWorkplaceIds = [];

            foreach ($workplaces as $workplaceData) {
                $workplaceId = (int) $workplaceData['id'];
                $activeWorkplaceIds[] = $workplaceId;
                $workplace = $this->workplaceRepository->findById($workplaceId);

                if (!$workplace) {
                    $workplace = new Workplace();
                    $workplace->setId($workplaceId);
                    $stats['workplaces_created']++;
                } else {
                    $stats['workplaces_updated']++;
                }

                $workplace->setTitle($workplaceData['title'] ?? null)
                    ->setCity($workplaceData['city'] ?? null)
                    ->setAddress($workplaceData['address'] ?? null)
                    ->setOkato($workplaceData['okato'] ?? null)
                    ->setPhone($workplaceData['phone'] ?? null)
                    ->setState((int) ($workplaceData['state'] ?? 1))
                    ->setImageLinks($this->normalizeArrayValue($workplaceData['image_links'] ?? null))
                    ->setIsActive(((int) ($workplaceData['state'] ?? 1)) === 1)
                    ->setUpdatedAt(new DateTime());

                $this->entityManager->persist($workplace);
            }

            $this->entityManager->flush();
            return array_values(array_unique($activeWorkplaceIds));
        } catch (Exception $e) {
            $stats['errors'][] = 'Ошибка синхронизации workplaces: ' . $e->getMessage();
            $this->logger->error('Ошибка синхронизации workplaces', ['error' => $e->getMessage()]);
            return null;
        }
    }

    private function syncWorkplaceData(Workplace $workplace, array &$stats): void
    {
        $workplaceId = (int) $workplace->getId();
        try {
            $tickets = $this->apiService->getAllPawnTicketsByWorkplace($workplaceId);
        } catch (RuntimeException $e) {
            if (str_contains($e->getMessage(), 'HTTP 412')) {
                $this->logger->warning('Пропуск филиала из-за API 412 при загрузке билетов', [
                    'workplace_external_id' => $workplaceId,
                    'error' => $e->getMessage(),
                ]);

                return;
            }

            throw $e;
        }

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

        $this->cleanupDeletedTickets($workplace, $tickets, $stats);
    }

    private function cleanupDeletedWorkplaces(array $activeWorkplaceIds, array &$stats): void
    {
        $removedWorkplaces = $this->workplaceRepository->findByIdsNotIn($activeWorkplaceIds);

        foreach ($removedWorkplaces as $workplace) {
            if ($workplace->getPawnTickets()->count() === 0) {
                $this->entityManager->remove($workplace);
                $stats['workplaces_deleted']++;
                continue;
            }

            if ($workplace->isActive()) {
                $workplace->setIsActive(false)
                    ->setUpdatedAt(new DateTime());
                $this->entityManager->persist($workplace);
                $stats['workplaces_deactivated']++;
            }
        }
    }

    private function cleanupDeletedTickets(Workplace $workplace, array $tickets, array &$stats): void
    {
        $actualTicketNumbers = [];
        foreach ($tickets as $ticketData) {
            $ticketNumber = trim((string) ($ticketData['document_number'] ?? ''));
            if ($ticketNumber !== '') {
                $actualTicketNumbers[] = $ticketNumber;
            }
        }

        $actualTicketNumbers = array_values(array_unique($actualTicketNumbers));
        $staleTickets = $this->pawnTicketRepository->findByWorkplaceMissingTicketNumbers($workplace, $actualTicketNumbers);

        foreach ($staleTickets as $staleTicket) {
            $this->entityManager->remove($staleTicket);
            $stats['tickets_deleted']++;
        }
    }

    private function cleanupOrphanClients(array &$stats): void
    {
        $orphanClients = $this->clientRepository->findClientsWithoutTickets();

        foreach ($orphanClients as $client) {
            $this->entityManager->remove($client);
            $stats['clients_deleted']++;
        }
    }

    private function syncClients(array $clientsData, array &$stats): array
    {
        $clients = [];
        $existingClients = $this->clientRepository->findIndexedByTypedExternalIds($this->collectClientRefsByType($clientsData));

        foreach ($clientsData as $clientKey => $clientData) {
            $clients[$clientKey] = $this->syncClient($clientKey, $clientData, $stats, $existingClients);
        }

        return $clients;
    }

    private function collectClientIds(array $tickets): array
    {
        $ids = [
            self::CLIENT_TYPE_NATURAL_PERSON => [],
            self::CLIENT_TYPE_LEGAL_PERSON => [],
        ];

        foreach ($tickets as $ticket) {
            if (!empty($ticket['client_natural_person_id'])) {
                $ids[self::CLIENT_TYPE_NATURAL_PERSON][(int) $ticket['client_natural_person_id']] = true;
            }

            if (!empty($ticket['client_legal_person_id'])) {
                $ids[self::CLIENT_TYPE_LEGAL_PERSON][(int) $ticket['client_legal_person_id']] = true;
            }
        }

        return [
            self::CLIENT_TYPE_NATURAL_PERSON => array_keys($ids[self::CLIENT_TYPE_NATURAL_PERSON]),
            self::CLIENT_TYPE_LEGAL_PERSON => array_keys($ids[self::CLIENT_TYPE_LEGAL_PERSON]),
        ];
    }

    private function fetchClients(array $clientRefs, array &$stats): array
    {
        $clientsData = [];

        foreach ($clientRefs[self::CLIENT_TYPE_NATURAL_PERSON] ?? [] as $clientId) {
            try {
                $clientsData[$this->buildClientKey(self::CLIENT_TYPE_NATURAL_PERSON, (int) $clientId)] = $this->apiService->getNaturalPersonClient((int) $clientId);
            } catch (Exception $e) {
                $stats['errors'][] = sprintf('Клиент-физлицо %d: %s', $clientId, $e->getMessage());
            }
        }

        foreach ($clientRefs[self::CLIENT_TYPE_LEGAL_PERSON] ?? [] as $clientId) {
            try {
                $clientsData[$this->buildClientKey(self::CLIENT_TYPE_LEGAL_PERSON, (int) $clientId)] = $this->apiService->getLegalPersonClient((int) $clientId);
            } catch (Exception $e) {
                $stats['errors'][] = sprintf('Клиент-юрлицо %d: %s', $clientId, $e->getMessage());
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

        $ticket = $this->pawnTicketRepository->findOneByWorkplaceAndTicketNumber($workplace, $ticketNumber);
        if (!$ticket && $externalId !== null) {
            $ticket = $this->pawnTicketRepository->findByExternalId($externalId);
        }

        if (!$ticket) {
            $ticket = new PawnTicket();
            $stats['tickets_created']++;
            $isNewTicket = true;
        } else {
            $stats['tickets_updated']++;
            $isNewTicket = false;
        }

        if ($externalId !== null) {
            $ticket->setExternalId($externalId);
        }

        $pledgeAmountRaw = $data['pledge_amount'] ?? $data['loan_amount'] ?? null;

        $ticket->setTicketNumber($ticketNumber)
            ->setPawnChainId($this->extractNumericValue($data, ['pawn_chain_id']))
            ->setTariffId($this->extractNumericValue($data, ['tariff_id']))
            ->setStatus((int) ($data['status'] ?? 0))
            ->setDuration($this->extractNumericValue($data, ['duration']))
            ->setLoanAmount($this->normalizeDecimalValue($data['loan_amount'] ?? null, 10, 2) ?? '0.00')
            ->setPledgeAmount($this->normalizeDecimalValue($pledgeAmountRaw, 10, 2) ?? '0.00')
            ->setInterestRate($this->normalizeDecimalValue($data['accrued_percent'] ?? null, 5, 2) ?? '0.00')
            ->setPaidPercents($this->normalizeDecimalValue($data['paid_percents'] ?? null, 10, 2) ?? '0.00')
            ->setCurrentDebt($this->normalizeDecimalValue($data['pawn_ticket_debt']['sum_debt'] ?? null, 10, 2) ?? '0.00')
            ->setPawnTicketDebt($this->normalizeArrayValue($data['pawn_ticket_debt'] ?? null))
            ->setComment($data['comment'] ?? null)
            ->setEntityId($this->extractNumericValue($data, ['entity_id']))
            ->setTestOperation($data['test_operation'] ?? false)
            ->setCurrency($this->resolveCurrency($data['currency_code'] ?? null))
            ->setWorkplace($workplace)
            ->setIssueDate($this->parseApiDate($data['open_date'] ?? null))
            ->setDueDate($this->parseApiDate($data['end_date'] ?? null))
            ->setCloseDate($this->parseApiDate($data['close_date'] ?? null))
            ->setUpdatedAt(new DateTime());

        $clientKey = $this->resolveTicketClientKey($data);
        $clientAssigned = false;
        if ($clientKey !== null && isset($clients[$clientKey])) {
            $ticket->setClient($clients[$clientKey]);
            $clientAssigned = true;
        }

        if ($isNewTicket && !$clientAssigned) {
            throw new RuntimeException(sprintf(
                'Не найден клиент для нового билета %s (client_natural_person_id=%s, client_legal_person_id=%s)',
                $ticketNumber,
                (string) ($data['client_natural_person_id'] ?? 'null'),
                (string) ($data['client_legal_person_id'] ?? 'null')
            ));
        }

        $this->entityManager->persist($ticket);

        $pawnTicketId = $this->extractPawnTicketId($data);

        if ($pawnTicketId === null) {
            $resolvedTicketId = $this->apiService->resolvePawnTicketId(
                (int) $workplace->getId(),
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
                    $existingGoodsByExternalId = [];
                    $legacyGoods = [];

                    foreach ($ticket->getPawnGoods() as $existingGood) {
                        $goodExternalId = $existingGood->getExternalId();
                        if ($goodExternalId !== null) {
                            $existingGoodsByExternalId[$goodExternalId][] = $existingGood;
                            continue;
                        }

                        $legacyGoods[] = $existingGood;
                    }

                    $syncedExternalIds = [];
                    $addedCount = 0;

                    foreach ($goods as $goodData) {
                        try {
                            $goodExternalId = $this->extractNumericValue($goodData, ['id']);
                            $existingGood = null;

                            if ($goodExternalId !== null && !empty($existingGoodsByExternalId[$goodExternalId])) {
                                $existingGood = array_shift($existingGoodsByExternalId[$goodExternalId]);
                            } elseif ($goodExternalId === null) {
                                $existingGood = array_shift($legacyGoods);
                            }

                            $this->syncPawnGood($ticket, $goodData, $existingGood);

                            if ($goodExternalId !== null) {
                                $syncedExternalIds[$goodExternalId] = true;
                            }

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

                    foreach ($ticket->getPawnGoods()->toArray() as $existingGood) {
                        $goodExternalId = $existingGood->getExternalId();
                        if ($goodExternalId !== null) {
                            if (!isset($syncedExternalIds[$goodExternalId])
                                || in_array($existingGood, $existingGoodsByExternalId[$goodExternalId] ?? [], true)
                            ) {
                                $ticket->removePawnGood($existingGood);
                            }

                            continue;
                        }

                        if (in_array($existingGood, $legacyGoods, true)) {
                            $ticket->removePawnGood($existingGood);
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

    private function syncClient(string $clientKey, array $data, array &$stats, array &$existingClients): Client
    {
        $externalId = (int) $data['id'];
        [$clientType] = explode(':', $clientKey, 2);
        $client = $existingClients[$clientKey] ?? null;

        if (!$client) {
            $client = $clientType === self::CLIENT_TYPE_LEGAL_PERSON
                ? new LegalPerson()
                : new NaturalPerson();
            $client->setExternalId($externalId);
            $stats['clients_created']++;
            $existingClients[$clientKey] = $client;
        } else {
            $stats['clients_updated']++;
        }

        $client->setSurname($data['last_name'] ?? '')
            ->setName($data['name'] ?? '')
            ->setPatronymic($data['patronymic'] ?? null)
            ->setPhone($data['phone'] ?? null)
            ->setEmail($data['email'] ?? null)
            ->setInn($data['inn'] ?? null)
            ->setDateAdded($this->parseApiDate($data['date_added'] ?? null))
            ->setUpdatedAt(new DateTime());

        if ($client instanceof NaturalPerson) {
            $client->setBirthDate($this->parseApiDate($data['birth_date'] ?? null))
                ->setAddress($data['address'] ?? null)
                ->setActualAddress($data['actual_address'] ?? null)
                ->setPlaceOfBirth($data['place_of_birth'] ?? null)
                ->setPhotoLink($this->normalizeArrayValue($data['photo_link'] ?? null))
                ->setNationality($this->extractNumericValue($data, ['nationality']))
                ->setSnils($data['snils'] ?? null)
                ->setAdditionalInfo($data['additional_info'] ?? null)
                ->setWarningMessage($data['warning_message'] ?? null)
                ->setLoyaltyCardNumber($data['loyalty_card_number'] ?? null)
                ->setLoyaltyCardDiscount($this->normalizeArrayValue($data['loyalty_card_discount'] ?? null))
                ->setBonuses((int) ($data['bonuses'] ?? 0))
                ->setAdChannelId($this->extractNumericValue($data, ['ad_channel_id']));
        }

        if ($client instanceof LegalPerson) {
            $client->setLegalAddress($data['legal_address'] ?? null)
                ->setDirectorFio($data['director_fio'] ?? null)
                ->setChiefAccountantFio($data['chief_accountant_fio'] ?? null)
                ->setKpp($data['kpp'] ?? null)
                ->setOgrn($data['ogrn'] ?? null)
                ->setOkpo($data['okpo'] ?? null)
                ->setOktmo($data['oktmo'] ?? null)
                ->setAdChannelId($this->extractNumericValue($data, ['ad_channel_id']));
        }

        $this->entityManager->persist($client);

        return $client;
    }

    private function syncPawnGood(PawnTicket $ticket, array $data, ?PawnGood $good = null): void
    {
        if ($good === null) {
            $good = new PawnGood();
            $ticket->addPawnGood($good);
        }

        $good->setPawnTicket($ticket)
            ->setExternalId($this->extractNumericValue($data, ['id']))
            ->setArticle($this->extractNumericValue($data, ['article']))
            ->setName($data['name'] ?? 'Без названия')
            ->setSerialNumber($data['serial_number'] ?? null)
            ->setDescription($data['description'] ?? null)
            ->setEstimatedValue($this->normalizeDecimalValue($data['estimate_price'] ?? null, 10, 2) ?? '0.00')
            ->setCurrency($this->resolveCurrency($data['currency_code'] ?? null))
            ->setWorkplace($ticket->getWorkplace())
            ->setStorage($data['storage'] ?? null)
            ->setStatus((int) ($data['status'] ?? 0))
            ->setTestOperation($data['test_operation'] ?? false)
            ->setComment($data['comment'] ?? null)
            ->setImagesLinks($this->normalizeArrayValue($data['images_links'] ?? null))
            ->setJewelryExtra($this->normalizeArrayValue($data['jewelry_extra'] ?? null))
            ->setVehicleExtra($this->normalizeArrayValue($data['vehicle_extra'] ?? null));

        if (!empty($data['type'])) {
            $apiType = (string) $data['type'];
            $goodType = self::GOODS_TYPES[$apiType] ?? self::DEFAULT_CATEGORY_CODE;
            $good->setGoodType($goodType);
        } else {
            $good->setGoodType(self::DEFAULT_CATEGORY_CODE);
        }

        if (!empty($data['category_id'])) {
            $category = $this->categoryRepository->findById((int) $data['category_id']);
            if ($category) {
                $good->setCategory($category);
            } else {
                $this->logger->warning('Категория не найдена по id', [
                    'category_id' => $data['category_id'],
                    'good_name' => $data['name'] ?? 'unknown'
                ]);
                $good->setCategory($this->getFallbackCategory());
            }
        } else {
            $good->setCategory($this->getFallbackCategory());
        }

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

    private function normalizeArrayValue(mixed $value): ?array
    {
        return is_array($value) ? $value : null;
    }

    private function ensureFallbackCategory(): PawnGoodCategory
    {
        $category = $this->categoryRepository->findById(self::DEFAULT_CATEGORY_ID)
            ?? $this->categoryRepository->findOneBy(['code' => self::DEFAULT_CATEGORY_CODE]);

        if (!$category) {
            $category = new PawnGoodCategory();
            $category->setId(self::DEFAULT_CATEGORY_ID);
        }

        $category->setCode(self::DEFAULT_CATEGORY_CODE)
            ->setName('Прочее')
            ->setDescription('Fallback категория для имущества без известной категории из API')
            ->setSystemCategory(true);

        $this->entityManager->persist($category);

        return $category;
    }

    private function getFallbackCategory(): PawnGoodCategory
    {
        $category = $this->categoryRepository->findById(self::DEFAULT_CATEGORY_ID)
            ?? $this->categoryRepository->findOneBy(['code' => self::DEFAULT_CATEGORY_CODE]);

        if ($category) {
            return $category;
        }

        return $this->ensureFallbackCategory();
    }

    private function buildClientKey(string $clientType, int $clientId): string
    {
        return sprintf('%s:%d', $clientType, $clientId);
    }

    private function resolveTicketClientKey(array $ticketData): ?string
    {
        if (!empty($ticketData['client_natural_person_id'])) {
            return $this->buildClientKey(
                self::CLIENT_TYPE_NATURAL_PERSON,
                (int) $ticketData['client_natural_person_id']
            );
        }

        if (!empty($ticketData['client_legal_person_id'])) {
            return $this->buildClientKey(
                self::CLIENT_TYPE_LEGAL_PERSON,
                (int) $ticketData['client_legal_person_id']
            );
        }

        return null;
    }

    private function collectClientRefsByType(array $clientsData): array
    {
        $clientRefs = [
            self::CLIENT_TYPE_NATURAL_PERSON => [],
            self::CLIENT_TYPE_LEGAL_PERSON => [],
        ];

        foreach (array_keys($clientsData) as $clientKey) {
            [$clientType, $clientId] = explode(':', (string) $clientKey, 2);

            if (isset($clientRefs[$clientType]) && is_numeric($clientId)) {
                $clientRefs[$clientType][] = (int) $clientId;
            }
        }

        return $clientRefs;
    }

    private function resolveCurrency(mixed $currencyCode): ?Currency
    {
        return $this->currencyRepository->getOrCreateByCode(
            is_string($currencyCode) ? $currencyCode : null
        );
    }

    private function normalizeDecimalValue(mixed $value, int $precision, int $scale): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $normalized = str_replace(',', '.', trim((string) $value));
        if (!is_numeric($normalized)) {
            return null;
        }

        $number = (float) $normalized;
        $maxIntegerPart = (10 ** ($precision - $scale)) - (10 ** (-$scale));

        if ($number > $maxIntegerPart) {
            $this->logger->warning('Ограничение decimal-значения по precision/scale', [
                'input' => $normalized,
                'precision' => $precision,
                'scale' => $scale,
                'clamped_to' => $maxIntegerPart,
            ]);
            $number = $maxIntegerPart;
        } elseif ($number < -$maxIntegerPart) {
            $this->logger->warning('Ограничение decimal-значения по precision/scale', [
                'input' => $normalized,
                'precision' => $precision,
                'scale' => $scale,
                'clamped_to' => -$maxIntegerPart,
            ]);
            $number = -$maxIntegerPart;
        }

        return number_format($number, $scale, '.', '');
    }

}
