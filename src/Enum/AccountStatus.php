<?php

namespace App\Enum;

enum AccountStatus: int
{
    case ACTIVE = 1;
    case DISMISSED = 2;
    case SUSPENDED = 3;
}
