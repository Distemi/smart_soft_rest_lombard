<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\Cache\CacheInterface;

#[AsCommand(
    name: 'app:clear-api-cache',
    description: 'Очистить кеш токена SmartLombard',
)]
class ClearApiCacheCommand extends Command
{
    private const string CACHE_KEY = 'smartlombard_access_token';

    public function __construct(
        private readonly CacheInterface $cache
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $this->cache->delete(self::CACHE_KEY);
        $io->success('Кеш токена очищен');
        return Command::SUCCESS;
    }
}
