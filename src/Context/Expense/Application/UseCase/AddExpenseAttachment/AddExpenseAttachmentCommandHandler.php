<?php

declare(strict_types=1);

namespace App\Context\Expense\Application\UseCase\AddExpenseAttachment;

use App\Context\Expense\Domain\ExpenseRepository;
use App\Shared\Application\CommandHandler;
use Symfony\Component\String\Slugger\SluggerInterface;

use function pathinfo;
use function time;

use const PATHINFO_FILENAME;

final class AddExpenseAttachmentCommandHandler implements CommandHandler
{
    public function __construct(
        private readonly ExpenseRepository $repository,
        private readonly SluggerInterface $slugger,
        private readonly string $uploadsPath,
    ) {}

    public function __invoke(AddExpenseAttachmentCommand $command): void
    {
        $expense = $this->repository->findOneByIdOrFail($command->expenseId);

        $attachment = $command->attachment;
        $originalFilename = pathinfo($attachment->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($expense->description() ?? $originalFilename)->lower()->toString();
        $newFilename = $safeFilename . '_' . time() . '.' . $attachment->guessExtension();

        $attachment->move(
            $this->uploadsPath,
            $newFilename,
        );

        $expense->updateAttachment($newFilename);

        $this->repository->save($expense);
    }
}
