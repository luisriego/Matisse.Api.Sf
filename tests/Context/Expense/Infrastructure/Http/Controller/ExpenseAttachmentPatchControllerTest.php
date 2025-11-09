<?php

declare(strict_types=1);

namespace App\Tests\Context\Expense\Infrastructure\Http\Controller;

use App\Context\Expense\Domain\Expense;
use App\Tests\Context\Expense\Domain\ExpenseMother;
use App\Tests\Shared\Infrastructure\PhpUnit\ApiTestCase;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;

class ExpenseAttachmentPatchControllerTest extends ApiTestCase
{
    private string $uploadsPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createAuthenticatedClient();
        $this->uploadsPath = self::getContainer()->getParameter('expense_uploads_path');

        if (!is_dir($this->uploadsPath)) {
            mkdir($this->uploadsPath, 0777, true);
        }
    }

    protected function tearDown(): void
    {
        $files = glob($this->uploadsPath . '/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        parent::tearDown();
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function test_it_should_upload_an_attachment_for_an_expense(): void
    {
        $expense = ExpenseMother::create(description: 'Groceries for the month');
        $this->entityManager->persist($expense->account());
        $this->entityManager->persist($expense->type());
        $this->entityManager->persist($expense);
        $this->entityManager->flush();

        // Create a real, empty PDF file to ensure guessExtension() works correctly.
        $filePath = sys_get_temp_dir() . '/test_document.pdf';
        file_put_contents($filePath, '%PDF-1.0'); // Minimal PDF header

        $uploadedFile = new UploadedFile($filePath, 'test_document.pdf', 'application/pdf', null, true);

        $this->client->request(
            'POST',
            '/api/v1/expenses/attachment/' . $expense->id(),
            [],
            ['attachment' => $uploadedFile]
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $this->entityManager->clear();
        $updatedExpense = $this->entityManager->find(Expense::class, $expense->id());
        $this->assertNotNull($updatedExpense->attachment());
        $this->assertStringContainsString('groceries-for-the-month', $updatedExpense->attachment());
        $this->assertStringEndsWith('.pdf', $updatedExpense->attachment());
        $this->assertFileExists($this->uploadsPath . '/' . $updatedExpense->attachment());
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function test_it_should_save_with_correct_extension_when_mimetype_mismatches(): void
    {
        $expense = ExpenseMother::create(description: 'Security test');
        $this->entityManager->persist($expense->account());
        $this->entityManager->persist($expense->type());
        $this->entityManager->persist($expense);
        $this->entityManager->flush();

        // Create a .txt file but name it .pdf (a disguised file)
        $filePath = sys_get_temp_dir() . '/malicious.pdf';
        file_put_contents($filePath, 'This is just plain text.');

        $uploadedFile = new UploadedFile($filePath, 'malicious.pdf', 'application/pdf', null, true);

        $this->client->request(
            'POST',
            '/api/v1/expenses/attachment/' . $expense->id(),
            [],
            ['attachment' => $uploadedFile]
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $this->entityManager->clear();
        $updatedExpense = $this->entityManager->find(Expense::class, $expense->id());
        $this->assertNotNull($updatedExpense->attachment());
        $this->assertStringEndsWith('.txt', $updatedExpense->attachment(), "Failed to prove security: File was saved with .pdf extension despite being a text file.");
        $this->assertFileExists($this->uploadsPath . '/' . $updatedExpense->attachment());
    }

    public function test_it_should_return_404_if_expense_not_found(): void
    {
        $nonExistentId = 'f47ac10b-58cc-4372-a567-0e02b2c3d479';
        $filePath = sys_get_temp_dir() . '/test.txt';
        file_put_contents($filePath, 'dummy content');
        $uploadedFile = new UploadedFile($filePath, 'test.txt', 'text/plain', null, true);

        $this->client->request(
            'POST',
            '/api/v1/expenses/attachment/' . $nonExistentId,
            [],
            ['attachment' => $uploadedFile]
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function test_it_should_return_400_if_no_file_is_uploaded(): void
    {
        $expense = ExpenseMother::create();
        $this->entityManager->persist($expense->account());
        $this->entityManager->persist($expense->type());
        $this->entityManager->persist($expense);
        $this->entityManager->flush();

        $this->client->request(
            'POST',
            '/api/v1/expenses/attachment/' . $expense->id()
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }
}
