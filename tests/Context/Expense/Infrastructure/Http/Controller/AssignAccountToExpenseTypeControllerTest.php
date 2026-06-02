<?php

declare(strict_types=1);

namespace App\Tests\Context\Expense\Infrastructure\Http\Controller;

use App\Context\Expense\Domain\ExpenseType;
use App\Context\Expense\Infrastructure\Http\Controller\AssignAccountToExpenseTypeController;
use App\Shared\Domain\Exception\InvalidArgumentException;
use App\Tests\Context\Account\Domain\AccountMother;
use App\Tests\Context\Expense\Domain\ExpenseTypeMother;
use App\Tests\Shared\Infrastructure\PhpUnit\ApiTestCase;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Symfony\Component\HttpFoundation\Response;

use function sprintf;

/**
 * @covers \App\Context\Expense\Infrastructure\Http\Controller\AssignAccountToExpenseTypeController
 */
final class AssignAccountToExpenseTypeControllerTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->createAuthenticatedClient();
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function testItShouldAssignAccountToExpenseType(): void
    {
        $expenseType = ExpenseTypeMother::create();
        $this->entityManager->persist($expenseType);

        $account = AccountMother::create();
        $this->entityManager->persist($account);

        $this->entityManager->flush();

        $this->client->request(
            'PATCH',
            sprintf('/api/v1/expense-types/%s/assign-account/%s', $expenseType->id(), $account->id()),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        // Assert that the account was correctly assigned in the database
        $this->entityManager->clear();
        $updatedExpenseType = $this->entityManager->find(ExpenseType::class, $expenseType->id());

        $this->assertNotNull($updatedExpenseType->account());
        $this->assertEquals($account->id(), $updatedExpenseType->account()->id());
    }

    public function testItMapsExceptionsCorrectly(): void
    {
        $controller = $this->getContainer()->get(AssignAccountToExpenseTypeController::class);
        $exceptions = $controller->exceptions();

        $this->assertArrayHasKey(InvalidArgumentException::class, $exceptions);
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $exceptions[InvalidArgumentException::class]);
    }
}
