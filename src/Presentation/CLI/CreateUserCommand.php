<?php

namespace App\Presentation\CLI;

use App\Domain\Entity\User;
use App\Domain\Repository\UserRepositoryInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:user:create',
    description: 'Создать пользователя',
)]
class CreateUserCommand extends Command
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'Email пользователя')
            ->addArgument('password', InputArgument::REQUIRED, 'Пароль в открытом виде')
            ->addArgument('firstName', InputArgument::REQUIRED, 'Имя')
            ->addArgument('lastName', InputArgument::REQUIRED, 'Фамилия');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $email = $input->getArgument('email');

        if (null !== $this->userRepository->findByEmail($email)) {
            $output->writeln("<error>Пользователь с email {$email} уже существует.</error>");

            return Command::FAILURE;
        }

        $user = (new User())
            ->setEmail($email)
            ->setFirstName($input->getArgument('firstName'))
            ->setLastName($input->getArgument('lastName'));

        $user->setPassword($this->passwordHasher->hashPassword($user, $input->getArgument('password')));

        $this->userRepository->save($user);

        $output->writeln("<info>Пользователь {$email} успешно создан (ID: {$user->getId()}).</info>");

        return Command::SUCCESS;
    }
}
