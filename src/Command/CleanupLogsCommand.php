<?php

namespace App\Command;

use App\Repository\ApiLogRepository;
use DateTime;
use Exception;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:cleanup-logs',
    description: 'Очистка старых API логов',
    usages: [
        "app:cleanup-logs --days=30",
        "app:cleanup-logs -d 60"
    ]
)]
class CleanupLogsCommand extends Command
{
    public function __construct(
        private readonly ApiLogRepository $apiLogRepository
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('days', 'd', InputOption::VALUE_OPTIONAL, 'Количество дней для хранения логов', 30);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $days = (int) $input->getOption('days');

        if ($days < 1) {
            $io->error('Количество дней должно быть больше 0');
            return Command::FAILURE;
        }

        $date = new DateTime(sprintf("-%d days", $days));

        $io->title('Очистка API логов');
        $io->text(sprintf('Удаление логов старше %d дней (до %s)', $days, $date->format('Y-m-d H:i:s')));

        try {
            $deletedCount = $this->apiLogRepository->deleteOlderThan($date);

            $io->success(sprintf('Удалено логов: %d', $deletedCount));

            return Command::SUCCESS;
        } catch (Exception $e) {
            $io->error('Ошибка при очистке логов: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
