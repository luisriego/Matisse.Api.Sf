<?php

declare(strict_types=1);

namespace App\Tests\Context\Expense\Application\UseCase\AddExpenseAttachment;

use App\Context\Expense\Application\UseCase\AddExpenseAttachment\AddExpenseAttachmentCommand;
use App\Context\Expense\Application\UseCase\AddExpenseAttachment\AddExpenseAttachmentCommandHandler;
use App\Context\Expense\Domain\ExpenseRepository;
use App\Shared\Domain\Exception\ResourceNotFoundException;
use App\Tests\Context\Expense\Domain\ExpenseMother;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\String\UnicodeString;

class AddExpenseAttachmentCommandHandlerTest extends TestCase
{
    private ExpenseRepository|MockObject $expenseRepository;
    private SluggerInterface|MockObject $slugger;
    private string $uploadsPath;
    private AddExpenseAttachmentCommandHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->expenseRepository = $this->createMock(ExpenseRepository::class);
        $this->slugger = $this->createMock(SluggerInterface::class);
        $this->uploadsPath = sys_get_temp_dir() . '/uploads_test';

        if (!is_dir($this->uploadsPath)) {
            mkdir($this->uploadsPath, 0777, true);
        }

        $this->handler = new AddExpenseAttachmentCommandHandler(
            $this->expenseRepository,
            $this->slugger,
            $this->uploadsPath
        );
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        if (is_dir($this->uploadsPath)) {
            $this->removeDirectory($this->uploadsPath);
        }
    }

    private function removeDirectory(string $path): void
    {
        $files = glob($path . '/*');
        foreach ($files as $file) {
            is_dir($file) ? $this->removeDirectory($file) : unlink($file);
        }
        rmdir($path);
    }

    public function test_it_should_add_an_attachment_to_an_expense(): void
    {
        $expenseId = 'f47ac10b-58cc-4372-a567-0e02b2c3d479';
        $expenseDescription = 'Test Expense Description';
        $originalFilename = 'document.pdf';
        $safeFilename = 'test-expense-description';
        $extension = 'pdf';

        $expense = ExpenseMother::create(id: $expenseId, description: $expenseDescription);
        $uploadedFile = $this->createMock(UploadedFile::class);
        $uploadedFile->method('getClientOriginalName')->willReturn($originalFilename);
        $uploadedFile->method('guessExtension')->willReturn($extension);
        $uploadedFile->method('move')->willReturnCallback(function ($directory, $filename) {
            $path = $directory . '/' . $filename;
            file_put_contents($path, 'dummy content');
            return new File($path); // Return a File object
        });

        $this->expenseRepository->expects($this->once())->method('findOneByIdOrFail')->with($expenseId)->willReturn($expense);
        $this->slugger->expects($this->once())->method('slug')->with($expenseDescription)->willReturn(new UnicodeString($safeFilename));
        $this->expenseRepository->expects($this->once())->method('save')->with($expense);

        $command = new AddExpenseAttachmentCommand($expenseId, $uploadedFile);
        ($this->handler)($command);

        $this->assertNotNull($expense->attachment());
        $this->assertStringContainsString($safeFilename, $expense->attachment());
        $this->assertStringEndsWith('.' . $extension, $expense->attachment());
        $this->assertFileExists($this->uploadsPath . '/' . $expense->attachment());
    }

    public function test_it_should_throw_an_exception_if_expense_not_found(): void
    {
        $this->expectException(ResourceNotFoundException::class);
        $expenseId = 'non-existent-id';
        $uploadedFile = $this->createMock(UploadedFile::class);
        $this->expenseRepository->expects($this->once())->method('findOneByIdOrFail')->with($expenseId)->willThrowException(new ResourceNotFoundException());
        $command = new AddExpenseAttachmentCommand($expenseId, $uploadedFile);
        ($this->handler)($command);
    }

    public function test_it_should_use_original_filename_if_expense_description_is_null(): void
    {
        $expenseId = 'f47ac10b-58cc-4372-a567-0e02b2c3d479';
        $originalFilenameWithExt = 'another_document.png';
        $originalFilenameWithoutExt = 'another_document';
        $safeFilename = 'another-document';
        $extension = 'png';

        $expense = ExpenseMother::create(id: $expenseId, description: null);
        $uploadedFile = $this->createMock(UploadedFile::class);
        $uploadedFile->method('getClientOriginalName')->willReturn($originalFilenameWithExt);
        $uploadedFile->method('guessExtension')->willReturn($extension);
        $uploadedFile->method('move')->willReturnCallback(function ($directory, $filename) {
            $path = $directory . '/' . $filename;
            file_put_contents($path, 'dummy content');
            return new File($path); // Return a File object
        });

        $this->expenseRepository->expects($this->once())->method('findOneByIdOrFail')->with($expenseId)->willReturn($expense);
        $this->slugger->expects($this->once())->method('slug')->with($originalFilenameWithoutExt)->willReturn(new UnicodeString($safeFilename));
        $this->expenseRepository->expects($this->once())->method('save')->with($expense);

        $command = new AddExpenseAttachmentCommand($expenseId, $uploadedFile);
        ($this->handler)($command);

        $this->assertNotNull($expense->attachment());
        $this->assertStringContainsString($safeFilename, $expense->attachment());
        $this->assertStringEndsWith('.' . $extension, $expense->attachment());
        $this->assertFileExists($this->uploadsPath . '/' . $expense->attachment());
    }

    public function test_it_should_handle_special_characters_in_description(): void
    {
        $expenseId = 'f47ac10b-58cc-4372-a567-0e02b2c3d479';
        $expenseDescription = 'Descripción con acentos y espacios';
        $originalFilename = 'file.jpg';
        $safeFilename = 'descripcion-con-acentos-y-espacios';
        $extension = 'jpg';

        $expense = ExpenseMother::create(id: $expenseId, description: $expenseDescription);
        $uploadedFile = $this->createMock(UploadedFile::class);
        $uploadedFile->method('getClientOriginalName')->willReturn($originalFilename);
        $uploadedFile->method('guessExtension')->willReturn($extension);
        $uploadedFile->method('move')->willReturnCallback(function ($directory, $filename) {
            $path = $directory . '/' . $filename;
            file_put_contents($path, 'dummy content');
            return new File($path); // Return a File object
        });

        $this->expenseRepository->expects($this->once())->method('findOneByIdOrFail')->with($expenseId)->willReturn($expense);
        $this->slugger->expects($this->once())->method('slug')->with($expenseDescription)->willReturn(new UnicodeString($safeFilename));
        $this->expenseRepository->expects($this->once())->method('save')->with($expense);

        $command = new AddExpenseAttachmentCommand($expenseId, $uploadedFile);
        ($this->handler)($command);

        $this->assertNotNull($expense->attachment());
        $this->assertStringContainsString($safeFilename, $expense->attachment());
        $this->assertStringEndsWith('.' . $extension, $expense->attachment());
        $this->assertFileExists($this->uploadsPath . '/' . $expense->attachment());
    }
}
