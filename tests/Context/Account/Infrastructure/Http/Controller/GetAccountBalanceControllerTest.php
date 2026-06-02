<?php

declare(strict_types=1);

namespace App\Tests\Context\Account\Infrastructure\Http\Controller;

use App\Context\Account\Domain\Event\InitialBalanceSet;
use App\Context\EventStore\Domain\StoredEventRepository;
use App\Context\Expense\Domain\Event\ExpenseWasEntered;
use App\Tests\Context\Account\Domain\AccountMother;
use App\Tests\Context\Expense\Domain\ExpenseTypeMother;
use App\Tests\Shared\Domain\UuidMother;
use App\Tests\Shared\Infrastructure\PhpUnit\ApiTestCase;
use DateMalformedStringException;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Symfony\Component\HttpFoundation\Response;

use function json_decode;

final class GetAccountBalanceControllerTest extends ApiTestCase
{
    private ?StoredEventRepository $storedEventRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createAuthenticatedClient();
        $this->storedEventRepository = $this->getContainer()->get(StoredEventRepository::class);
    }

    protected function tearDown(): void
    {
        $this->storedEventRepository = null;
        parent::tearDown();
    }

    /**
     * @throws OptimisticLockException
     * @throws DateMalformedStringException
     * @throws ORMException
     */
    public function testItShouldReturnCorrectAccountBalance(): void
    {
        // 1. Create necessary entities
        $account = AccountMother::create();
        $expenseType = ExpenseTypeMother::create();
        $this->entityManager->persist($account);
        $this->entityManager->persist($expenseType);
        $this->entityManager->flush();

        // 2. Create domain events to establish a balance
        $initialBalance = 10000; // 100.00
        $expenseAmount = 3000;   // 30.00
        $expectedBalance = $initialBalance - $expenseAmount;

        // Initial Balance Event
        $initialBalanceEvent = new InitialBalanceSet(
            $account->id(),
            $initialBalance,
            '2025-01-01',
        );
        $this->storedEventRepository->append($initialBalanceEvent);

        // Expense Event
        $expenseEvent = new ExpenseWasEntered(
            UuidMother::create(), // The aggregate ID for the expense itself must be a valid UUID
            $expenseAmount,
            $expenseType->id(),
            $account->id(), // The account we are testing
            '2025-01-15',
            'Test Expense',
        );
        $this->storedEventRepository->append($expenseEvent);

        // 3. Send the GET request
        $this->client->request('GET', '/api/v1/accounts/' . $account->id() . '/balance');

        // 4. Assert the response
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $responseContent = $this->client->getResponse()->getContent();
        $data = json_decode($responseContent, true);

        $this->assertIsArray($data);
        $this->assertArrayHasKey('balance', $data);
        $this->assertEquals($expectedBalance, $data['balance']);
    }
}
