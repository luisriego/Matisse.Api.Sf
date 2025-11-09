<?php

declare(strict_types=1);

namespace App\Context\Expense\Application\UseCase\AddExpenseAttachment;

use App\Context\Account\Domain\Exception\ExpenseNotFoundException;
use App\Context\Expense\Domain\Expense;
use App\Context\Expense\Domain\ExpenseRepository;
use App\Shared\Application\CommandHandler;
use App\Shared\Domain\Exception\ResourceNotFoundException;
use Symfony\Component\String\Slugger\SluggerInterface;

final class AddExpenseAttachmentCommandHandler implements CommandHandler
{
    public function __construct(
        private readonly ExpenseRepository $repository,
        private readonly SluggerInterface $slugger,
        private readonly string $uploadsPath
    ) {
    }

    public function __invoke(AddExpenseAttachmentCommand $command): void
    {
        $expense = $this->repository->find($command->expenseId);

        if (null === $expense) {
            throw ResourceNotFoundException::createFromClassAndId(Expense::class, $command->expenseId);
        }

        $attachment = $command->attachment;
        $originalFilename = pathinfo($attachment->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($expense->description() ?? $originalFilename)->lower();
        $newFilename = $safeFilename . '_' . time() . '.' . $attachment->guessExtension();

        $attachment->move(
            $this->uploadsPath,
            $newFilename
        );

        $expense->updateAttachment($newFilename);

        $this->repository->save($expense);
    }
}
