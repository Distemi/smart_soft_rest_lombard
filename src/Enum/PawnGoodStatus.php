<?php

namespace App\Enum;

enum PawnGoodStatus: int
{
    case FOR_SALE = 1;
    case IN_PAWN = 2;
    case IN_PAWN_OVERDUE = 3;
    case BOUGHT_OUT = 4;
    case READY_FOR_SALE = 5;
    case WITHDRAWN_FROM_PAWN = 6;
    case WITHDRAWN = 7;
    case UNKNOWN = 0;

    public function label(): string
    {
        return match ($this) {
            self::FOR_SALE => 'На реализации',
            self::IN_PAWN => 'В залоге',
            self::IN_PAWN_OVERDUE => 'В залоге (просрочен)',
            self::BOUGHT_OUT => 'Выкуплен',
            self::READY_FOR_SALE => 'Готов к продаже',
            self::WITHDRAWN_FROM_PAWN => 'Снят с залога',
            self::WITHDRAWN => 'Изъят',
            self::UNKNOWN => 'Неизвестный статус',
        };
    }
}
