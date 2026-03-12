<?php

namespace App\Command;

use App\Entity\User;
use App\Repository\UserRepository;
use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-admin',
    description: 'Создание администратора',
)]
class CreateAdminCommand extends Command
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Создание администратора');

        $username = $io->ask('Имя пользователя', null, function ($value) {
            if (empty($value)) {
                throw new RuntimeException('Имя пользователя не может быть пустым');
            }
            return $value;
        });

        $email = $io->ask('Email', null, function ($value) {
            if (empty($value) || !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                throw new RuntimeException('Введите корректный email');
            }
            return $value;
        });

        $password = $io->askHidden('Пароль', function ($value) {
            if (empty($value) || strlen($value) < 6) {
                throw new RuntimeException('Пароль должен быть не менее 6 символов');
            }
            return $value;
        });

        $existingUser = $this->userRepository->findOneBy(['username' => $username]);
        if ($existingUser) {
            $io->error('Пользователь с таким именем уже существует');
            return Command::FAILURE;
        }

        $user = new User();
        $user->setUsername($username);
        $user->setEmail($email);
        $user->setRoles(['ROLE_ADMIN']);
        $user->setPassword($this->passwordHasher->hashPassword($user, $password));

        $this->userRepository->save($user, true);

        $io->success('Администратор создан!');
        $io->table(
            ['Параметр', 'Значение'],
            [
                ['Имя пользователя', $username],
                ['Email', $email],
                ['Роли', implode(', ', $user->getRoles())],
            ]
        );

        return Command::SUCCESS;
    }
}
