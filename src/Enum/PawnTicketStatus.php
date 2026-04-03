<?php

namespace App\Enum;

enum PawnTicketStatus: int
{
    case FOR_SALE = 1;
    case OPEN = 2;
    case OVERDUE = 3;
    case OVERDUE_READY_FOR_SALE = 4;
    case CLOSED = 5;

    public function isOpen(): bool
    {
        return match ($this) {
            self::OPEN, self::OVERDUE, self::OVERDUE_READY_FOR_SALE => true,
            default => false,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::FOR_SALE => 'На реализации',
            self::OPEN => 'Открыт',
            self::OVERDUE => 'Просрочен',
            self::OVERDUE_READY_FOR_SALE => 'Просрочен (готов к продаже)',
            self::CLOSED => 'Закрыт',
        };
    }
}
