<?php

namespace App\Presentation\CLI;

use App\Infrastructure\Fixtures\FixtureLoaderService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:fixtures:load',
    description: 'Загрузить тестовые данные в базу',
)]
class LoadFixturesCommand extends Command
{
    public function __construct(
        private readonly FixtureLoaderService $fixtureLoaderService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('reset', null, InputOption::VALUE_NONE, 'Сбросить данные перед загрузкой');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if ($input->getOption('reset')) {
            $io->writeln('Сброс данных...');
            $this->fixtureLoaderService->reset();
            $io->writeln('  ✓ Данные удалены');
        }

        try {
            $this->fixtureLoaderService->load($io);
        } catch (\RuntimeException $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }

        $io->success('Фикстуры успешно загружены.');
        $io->section('Предопределённые пароли');
        $io->listing([
            'admin@example.com — Admin@12345',
            'teacher1–2@example.com — Teacher@12345',
            'student1–17@example.com — Student@12345',
        ]);

        return Command::SUCCESS;
    }
}
