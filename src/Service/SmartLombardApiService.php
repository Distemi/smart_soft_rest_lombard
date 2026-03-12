<?php

namespace App\Service;

use App\Entity\ApiLog;
use App\Repository\ApiLogRepository;
use Psr\Log\LoggerInterface;
use Exception;
use RuntimeException;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use function is_string;

class SmartLombardApiService
{
    // Немного констант для настройки и удобства, чтобы не плодить магические строки и числа по всему коду
    private const string BASE_URL = 'https://online.smartlombard.ru/api/exchange/v1';
    private const string CACHE_KEY = 'smartlombard_access_token';
    private const int TOKEN_TTL = 3300;
    private const array PAWN_TICKET_ID_KEYS = ['id', 'ticket_id', 'pawn_ticket_id', 'external_id'];

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly ApiLogRepository $apiLogRepository,
        private readonly LoggerInterface $logger,
        private readonly CacheInterface $cache,
        private readonly string $secretKey,
        private readonly string $accountId
    ) {
    }

    public function getAccessToken(): string
    {
        return $this->cache->get(self::CACHE_KEY, function (ItemInterface $item) {
            $item->expiresAfter(self::TOKEN_TTL);
            return $this->requestAccessToken();
        });
    }

    private function requestAccessToken(): string
    {
        $endpoint = '/auth/access_token';
        $requestData = [
            'secret_key' => $this->secretKey,
            'account_id' => $this->accountId,
        ];

        $response = $this->httpClient->request('POST', self::BASE_URL . $endpoint, [
            'body' => $requestData,
        ]);

        $statusCode = $response->getStatusCode();
        $content = $response->getContent(false);
        $this->logApiRequest($endpoint, $requestData, $content, $statusCode);
        $data = json_decode($content, true);

        if ($statusCode === 200
            && ($data['status'] ?? false) === true
            && isset($data['result']['access_token']['token'])
        ) {
            return $data['result']['access_token']['token'];
        }

        throw new RuntimeException(sprintf(
            'Ошибка получения токена: %s (HTTP %d)',
            $data['error']['message'] ?? $data['message'] ?? 'Неизвестная ошибка',
            $statusCode
        ));
    }

    public function getPawnTickets(int $page = 1, int $limit = 100, array $params = []): array
    {
        $query = array_merge(['page' => $page, 'limit' => $limit], $params);
        return $this->apiGet('/pawn_tickets', $query);
    }

    public function getAllPawnTickets(array $params = []): array
    {
        return $this->collectPaginatedResults(
            fn (int $page): array => $this->getPawnTickets($page, 100, $params),
            'pawn_tickets'
        );
    }

    public function getPawnTicket(int $id): array
    {
        $data = $this->apiGet("/pawn_tickets/$id");
        return $data['result']['pawn_ticket'] ?? [];
    }

    public function getPawnGoods(int $pawnTicketId, int $page = 1, int $limit = 100): array
    {
        $query = ['page' => $page, 'limit' => $limit];
        return $this->apiGet("/pawn_tickets/$pawnTicketId/pawn_goods", $query);
    }

    public function getAllPawnGoods(int $pawnTicketId): array
    {
        return $this->collectPaginatedResults(
            fn (int $page): array => $this->getPawnGoods($pawnTicketId, $page, 100),
            'pawn_goods'
        );
    }

    public function getClient(int $clientId): array
    {
        $data = $this->apiGet("/clients/natural_persons/$clientId");
        return $data['result']['client_natural_person'] ?? [];
    }

    public function getClients(int $page = 1, int $limit = 100): array
    {
        return $this->apiGet('/clients/natural_persons', ['page' => $page, 'limit' => $limit]);
    }

    public function getOperations(int $page = 1, int $limit = 100, array $params = []): array
    {
        $query = array_merge(['page' => $page, 'limit' => $limit], $params);
        return $this->apiGet('/operations', $query);
    }

    public function getWorkplaces(): array
    {
        $data = $this->apiGet('/workplaces', ['limit' => 1000]);
        return $data['result']['workplaces'] ?? [];
    }

    public function getCategories(int $page = 1, int $limit = 100): array
    {
        $query = ['page' => $page, 'limit' => $limit];
        return $this->apiGet('/categories', $query);
    }

    public function getAllCategories(): array
    {
        return $this->collectPaginatedResults(
            fn (int $page): array => $this->getCategories($page, 100),
            'categories'
        );
    }

    public function getPawnTicketsByWorkplace(int $workplaceId, int $page = 1, int $limit = 100, array $params = []): array
    {
        $query = array_merge(['page' => $page, 'limit' => $limit, 'workplace_id' => $workplaceId], $params);
        return $this->apiGet('/pawn_tickets', $query);
    }

    public function getAllPawnTicketsByWorkplace(int $workplaceId, array $params = []): array
    {
        return $this->collectPaginatedResults(
            fn (int $page): array => $this->getPawnTicketsByWorkplace($workplaceId, $page, 100, $params),
            'pawn_tickets'
        );
    }

    public function resolvePawnTicketId(int $workplaceId, string $documentNumber, ?int $pawnChainId = null): ?int
    {
        $documentNumber = trim($documentNumber);
        if ($documentNumber == '') {
            return null;
        }

        $queries = [
            ['workplace_id' => $workplaceId, 'document_number' => $documentNumber],
            ['workplace_id' => $workplaceId, 'number' => $documentNumber],
        ];

        if ($pawnChainId !== null) {
            $queries[] = ['workplace_id' => $workplaceId, 'pawn_chain_id' => $pawnChainId];
        }

        foreach ($queries as $query) {
            $data = $this->apiGet('/pawn_tickets', ['page' => 1, 'limit' => 100, ...$query]);
            $tickets = $data['result']['pawn_tickets'] ?? [];

            foreach ($tickets as $ticket) {
                $ticketNumber = (string) ($ticket['document_number'] ?? '');
                if ($ticketNumber !== $documentNumber) {
                    continue;
                }

                $resolvedId = $this->extractNumericValue($ticket, self::PAWN_TICKET_ID_KEYS);
                if ($resolvedId !== null) {
                    return $resolvedId;
                }
            }
        }

        return null;
    }

    private function apiGet(string $endpoint, array $query = []): array
    {
        $token = $this->getAccessToken();

        $response = $this->httpClient->request('GET', self::BASE_URL . $endpoint, [
            'headers' => ['Authorization' => "Bearer $token"],
            'query' => $query,
        ]);

        $statusCode = $response->getStatusCode();
        $content = $response->getContent(false);
        $this->logApiRequest($endpoint, $query, $content, $statusCode);
        $data = json_decode($content, true);

        if ($statusCode === 200 && ($data['status'] ?? false) === true) {
            return $data;
        }

        throw new RuntimeException(sprintf(
            'Ошибка API %s: %s (HTTP %d)',
            $endpoint,
            $data['error']['message'] ?? $data['message'] ?? 'Неизвестная ошибка',
            $statusCode
        ));
    }

    private function logApiRequest(string $endpoint, array $requestData, string|array $responseData, int $statusCode): void
    {
        try {
            $apiLog = new ApiLog();
            $apiLog->setEndpoint($endpoint);
            $apiLog->setRequestSummary($this->summarizeRequest($requestData));
            $apiLog->setResponseSummary($this->summarizeResponse($responseData, $statusCode));
            $apiLog->setStatusCode($statusCode);
            $this->apiLogRepository->save($apiLog, true);
        } catch (Exception $e) {
            $this->logger->error('Ошибка логирования API', ['error' => $e->getMessage()]);
        }
    }

    private function summarizeRequest(array $data): string
    {
        $parts = [];
        foreach ($data as $key => $value) {
            if (in_array($key, ['secret_key', 'password', 'token', 'access_token'], true)) {
                continue;
            }
            if (is_scalar($value)) {
                $parts[] = "$key=$value";
            }
        }
        return implode(', ', $parts) ?: '-';
    }

    private function summarizeResponse(string|array $responseData, int $statusCode): string
    {
        // Вот вроде по русски всё должно быть, но пусть в БД меньше данных хранится, а то в логах будет много мусора и так :)
        if ($statusCode >= 400) {
            if (is_string($responseData)) {
                $decoded = json_decode($responseData, true);
                if ($decoded && isset($decoded['error']['message'])) {
                    return 'Error: ' . $decoded['error']['message'];
                }
                return 'Error';
            }
            if (isset($responseData['error']['message'])) {
                return 'Error: ' . $responseData['error']['message'];
            }
            return 'Error';
        }

        if (is_string($responseData)) {
            $decoded = json_decode($responseData, true);
            $responseData = $decoded ?? [];
        }

        if (isset($responseData['result']['access_token'])) {
            return 'Token received';
        }

        if (isset($responseData['result']['pawn_tickets'])) {
            return 'Tickets: ' . count($responseData['result']['pawn_tickets']);
        }

        if (isset($responseData['result']['pawn_ticket'])) {
            return 'Ticket #' . ($responseData['result']['pawn_ticket']['document_number'] ?? '?');
        }

        if (isset($responseData['result']['pawn_goods'])) {
            return 'Goods: ' . count($responseData['result']['pawn_goods']);
        }

        if (isset($responseData['result']['client_natural_person'])) {
            return 'Client loaded';
        }

        return 'Success';
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

    private function collectPaginatedResults(callable $fetchPage, string $resultKey): array
    {
        $allItems = [];
        $page = 1;

        do {
            $data = $fetchPage($page);
            $items = $data['result'][$resultKey] ?? [];
            if ($items !== []) {
                array_push($allItems, ...$items);
            }

            $totalPages = $data['metadata']['pagination']['count_pages'] ?? 1;
            $page++;
        } while ($page <= $totalPages);

        return $allItems;
    }
}
