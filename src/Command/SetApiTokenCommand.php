<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\Cache\CacheInterface;

#[AsCommand(
    name: 'app:set-api-token',
    description: 'Установить токен доступа SmartLombard в кеш',
)]
class SetApiTokenCommand extends Command
{
    private const string CACHE_KEY = 'smartlombard_access_token';
    private const int TOKEN_TTL = 3300;

    public function __construct(
        private readonly CacheInterface $cache
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('token', InputArgument::REQUIRED, 'Токен доступа');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $token = $input->getArgument('token');

        $this->cache->delete(self::CACHE_KEY);
        $this->cache->get(self::CACHE_KEY, function ($item) use ($token) {
            $item->expiresAfter(self::TOKEN_TTL);
            return $token;
        });

        $io->success(sprintf('Токен установлен (TTL: %d мин)', self::TOKEN_TTL / 60));
        return Command::SUCCESS;
    }
}
