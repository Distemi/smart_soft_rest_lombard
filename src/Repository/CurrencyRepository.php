<?php

namespace App\Repository;

use App\Entity\Currency;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Currency>
 */
class CurrencyRepository extends ServiceEntityRepository
{
    /**
     * @var array<string, Currency>
     */
    private array $currenciesByCode = [];

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Currency::class);
    }

    public function findByCode(string $code): ?Currency
    {
        $normalizedCode = $this->normalizeCode($code);

        if ($normalizedCode === '') {
            return null;
        }

        if (array_key_exists($normalizedCode, $this->currenciesByCode)) {
            return $this->currenciesByCode[$normalizedCode];
        }

        $currency = $this->findOneBy(['code' => $normalizedCode]);
        if ($currency !== null) {
            $this->currenciesByCode[$normalizedCode] = $currency;
        }

        return $currency;
    }

    public function getOrCreateByCode(?string $code): Currency
    {
        $normalizedCode = $this->normalizeCode($code ?? '');
        if ($normalizedCode === '') {
            $normalizedCode = 'RUB';
        }

        $currency = $this->findByCode($normalizedCode);
        if ($currency !== null) {
            return $currency;
        }

        $currency = new Currency();
        $currency->setCode($normalizedCode)
            ->setName($normalizedCode);

        $this->getEntityManager()->persist($currency);
        $this->currenciesByCode[$normalizedCode] = $currency;

        return $currency;
    }

    private function normalizeCode(string $code): string
    {
        return strtoupper(trim($code));
    }
}
