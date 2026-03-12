<?php

namespace App\Command;

use App\Service\DataSyncService;
use Exception;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:sync-data',
    description: 'Синхронизация данных с API SmartLombard',
)]
class SyncDataCommand extends Command
{
    public function __construct(
        private readonly DataSyncService $dataSyncService
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Синхронизация данных с API SmartLombard');

        try {
            $stats = $this->dataSyncService->syncAll();

            $io->success('Синхронизация завершена!');
            $io->table(
                ['Параметр', 'Значение'],
                [
                    ['Создано категорий', $stats['categories_created']],
                    ['Обновлено категорий', $stats['categories_updated']],
                    ['Создано филиалов', $stats['workplaces_created']],
                    ['Обновлено филиалов', $stats['workplaces_updated']],
                    ['Создано клиентов', $stats['clients_created']],
                    ['Обновлено клиентов', $stats['clients_updated']],
                    ['Создано билетов', $stats['tickets_created']],
                    ['Обновлено билетов', $stats['tickets_updated']],
                    ['Синхронизировано имущества', $stats['items_synced']],
                ]
            );

            if (!empty($stats['errors'])) {
                $io->warning('Ошибки:');
                $io->listing($stats['errors']);
            }

            return Command::SUCCESS;
        } catch (Exception $e) {
            $io->error('Ошибка: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
