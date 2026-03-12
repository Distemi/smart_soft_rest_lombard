<?php

namespace App\Request;

use Symfony\Component\Validator\Constraints as Assert;

class ClientTicketsQuery
{
    #[Assert\NotBlank(message: 'Параметр fullName обязателен')]
    #[Assert\Length(
        min: 3,
        max: 255,
        minMessage: 'fullName слишком короткий',
        maxMessage: 'fullName слишком длинный'
    )]
    #[Assert\Regex(
        pattern: '/^\S+(?:\s+\S+)+$/u',
        message: 'fullName должен содержать минимум фамилию и имя'
    )]
    public string $fullName = '';

    #[Assert\NotBlank(message: 'Параметр ticketNumber обязателен')]
    #[Assert\Length(
        min: 1,
        max: 50,
        minMessage: 'ticketNumber слишком короткий',
        maxMessage: 'ticketNumber слишком длинный'
    )]
    public string $ticketNumber = '';

    public static function fromArray(array $query): self
    {
        $dto = new self();
        $dto->fullName = trim((string) ($query['fullName'] ?? ''));
        $dto->ticketNumber = trim((string) ($query['ticketNumber'] ?? ''));

        return $dto;
    }
}
