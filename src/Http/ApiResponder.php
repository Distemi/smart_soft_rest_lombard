<?php

namespace App\Http;

use Symfony\Component\HttpFoundation\JsonResponse;

class ApiResponder
{
    public function success(array $data = [], int $statusCode = 200, ?string $message = null): JsonResponse
    {
        $payload = [
            'success' => true,
            'data' => $data,
        ];

        if ($message !== null) {
            $payload['message'] = $message;
        }

        return new JsonResponse($payload, $statusCode);
    }

    public function error(string $message, int $statusCode = 400, array $errors = []): JsonResponse
    {
        $payload = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== []) {
            $payload['errors'] = $errors;
        }

        return new JsonResponse($payload, $statusCode);
    }
}
