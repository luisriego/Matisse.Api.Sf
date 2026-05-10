<?php

declare(strict_types=1);

namespace App\Context\Setup\Application\UseCase\ConfirmInitialBalances;

use App\Context\Account\Domain\AccountRepository;
use App\Context\Account\Domain\Event\InitialBalanceSet;
use App\Context\Setup\Application\UseCase\PreviewInitialBalances\PreviewInitialBalancesQuery;
use App\Context\Setup\Application\UseCase\PreviewInitialBalances\PreviewInitialBalancesQueryHandler;
use App\Shared\Application\CommandHandler;
use App\Shared\Application\EventStore;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class ConfirmInitialBalancesCommandHandler implements CommandHandler
{
    public function __construct(
        private AccountRepository $accountRepository,
        private EventStore $eventStore,
        private PreviewInitialBalancesQueryHandler $previewHandler,
    ) {}

    public function __invoke(ConfirmInitialBalancesCommand $command): void
    {
        $preview = ($this->previewHandler)(new PreviewInitialBalancesQuery(
            $command->cutoffDate,
            $command->confirmedBankBalanceCents,
            $command->balances,
            $command->adjustmentPriority,
        ));

        foreach ($preview['adjustedBalances'] as $entry) {
            $this->accountRepository->findOneByIdOrFail($entry['accountId']);

            $this->eventStore->append(new InitialBalanceSet(
                $entry['accountId'],
                $entry['amountCents'],
                $command->cutoffDate,
            ));
        }
    }
}
