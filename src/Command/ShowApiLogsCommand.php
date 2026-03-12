<?php

namespace App\Command;

use App\Repository\ApiLogRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:show-api-logs',
    description: 'Показать логи API запросов',
)]
class ShowApiLogsCommand extends Command
{
    public function __construct(
        private readonly ApiLogRepository $apiLogRepository
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('limit', 'l', InputOption::VALUE_OPTIONAL, 'Количество записей', 5)
            ->addOption('endpoint', null, InputOption::VALUE_OPTIONAL, 'Фильтр по endpoint');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $limit = (int) $input->getOption('limit');
        $endpointFilter = $input->getOption('endpoint');

        $qb = $this->apiLogRepository->createQueryBuilder('al')
            ->orderBy('al.createdAt', 'DESC')
            ->setMaxResults($limit);

        if ($endpointFilter) {
            $qb->andWhere('al.endpoint LIKE :endpoint')
                ->setParameter('endpoint', '%' . $endpointFilter . '%');
        }

        $logs = $qb->getQuery()->getResult();

        if (empty($logs)) {
            $io->warning('Логи не найдены');
            return Command::SUCCESS;
        }

        $io->title('Логи API запросов');

        foreach ($logs as $log) {
            $io->section(sprintf(
                'ID: %d | %s | Status: %s',
                $log->getId(),
                $log->getEndpoint(),
                $log->getStatusCode() ?? '-'
            ));
            $io->text(sprintf('<info>Дата:</info> %s', $log->getCreatedAt()->format('d.m.Y H:i:s')));

            if ($log->getRequestSummary()) {
                $io->text(sprintf('<info>Запрос:</info> %s', $log->getRequestSummary()));
            }

            if ($log->getResponseSummary()) {
                $io->text(sprintf('<info>Ответ:</info> %s', $log->getResponseSummary()));
            }

            $io->newLine();
        }

        return Command::SUCCESS;
    }
}
