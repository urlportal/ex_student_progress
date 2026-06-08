<?php

namespace App\Presentation\CLI;

use App\Application\Service\ScoreSeederService;
use Monolog\Handler\NullHandler;
use Monolog\Logger as MonologLogger;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(
    name: 'app:fixtures:seed-scores',
    description: 'Опубликовать события оценок в очередь для демонстрации работы Symfony Messenger',
)]
class SeedScoresCommand extends Command
{
    public function __construct(
        private readonly ScoreSeederService $scoreSeederService,
        #[Autowire(service: 'monolog.logger.messenger')]
        private readonly LoggerInterface $messengerLogger,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        [$students, $tasksByCourse] = $this->scoreSeederService->loadData();

        if ([] === $students || [] === $tasksByCourse) {
            $io->warning('Нет данных для публикации. Сначала выполните app:fixtures:load.');

            return Command::SUCCESS;
        }

        $total = $this->scoreSeederService->countTotal($students, $tasksByCourse);

        $io->writeln('Публикация событий оценок...');

        $progress = new ProgressBar($output, $total);
        $progress->start();

        if ($this->messengerLogger instanceof MonologLogger) {
            $this->messengerLogger->pushHandler(new NullHandler());
        }

        try {
            [$published, $failed] = $this->scoreSeederService->seed($students, $tasksByCourse, $progress);
        } finally {
            if ($this->messengerLogger instanceof MonologLogger) {
                $this->messengerLogger->popHandler();
            }
        }

        $progress->finish();
        $io->newLine(2);

        if ($failed > 0) {
            $io->error(\sprintf('Завершено с ошибками: опубликовано %d, не удалось %d.', $published, $failed));

            return Command::FAILURE;
        }

        $io->success(\sprintf('Опубликовано %d событий!', $published));

        return Command::SUCCESS;
    }
}
