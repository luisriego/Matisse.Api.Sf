<?php

declare(strict_types=1);

namespace App\Context\User\Infrastructure\Console;

use App\Context\User\Domain\UserRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use function sprintf;

#[AsCommand(
    name: 'app:syndic:set',
    description: 'Sets the single syndic of the condominium by email, demoting any previous syndic.',
)]
final class SetSyndicCommand extends Command
{
    public function __construct(private readonly UserRepository $userRepository)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('email', InputArgument::REQUIRED, 'Email of the user to promote to syndic');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $email = (string) $input->getArgument('email');

        $target = $this->userRepository->findByEmail($email);

        if (null === $target) {
            $io->error(sprintf('No user found with email <%s>.', $email));

            return Command::FAILURE;
        }

        foreach ($this->userRepository->findSyndics() as $currentSyndic) {
            if ($currentSyndic->getId() !== $target->getId()) {
                $currentSyndic->demoteToResident();
                $this->userRepository->save($currentSyndic, false);
                $io->note(sprintf('Demoted previous syndic <%s>.', $currentSyndic->getEmail()));
            }
        }

        $target->promoteToSyndic();
        $this->userRepository->save($target, true);

        $io->success(sprintf('User <%s> is now the syndic.', $email));

        return Command::SUCCESS;
    }
}
