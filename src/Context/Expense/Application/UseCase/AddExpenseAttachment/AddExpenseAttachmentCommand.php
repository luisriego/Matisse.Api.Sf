<?php

declare(strict_types=1);

namespace App\Context\Expense\Application\UseCase\AddExpenseAttachment;

use App\Shared\Application\Command;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final readonly class AddExpenseAttachmentCommand implements Command
{
    public function __construct(
        public string $expenseId,
        public UploadedFile $attachment,
    ) {}
}
