<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class PhoneExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('format_phone', [$this, 'formatPhone']),
        ];
    }

    public function formatPhone(?string $phone): string
    {
        if ($phone === null || $phone === '') {
            return '-';
        }

        $digits = preg_replace('/\D/', '', $phone);

        if ($digits === null || $digits === '') {
            return $phone;
        }

        $len = strlen($digits);

        if ($len < 10 || $len > 15) {
            return $phone;
        }

        $suffix  = substr($digits, -4);
        $prefix  = substr($digits, -7, 3);
        $area    = substr($digits, -10, 3);
        $country = substr($digits, 0, $len - 10);

        if ($country === '') {
            return sprintf('(%s)%s-%s', $area, $prefix, $suffix);
        }

        return sprintf('+%s(%s)%s-%s', $country, $area, $prefix, $suffix);
    }
}
