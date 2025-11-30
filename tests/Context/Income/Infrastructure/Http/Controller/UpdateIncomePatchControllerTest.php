<?php

declare(strict_types=1);

namespace App\Tests\Context\Income\Infrastructure\Http\Controller;

use App\Context\Income\Domain\Income;
use App\Context\Income\Infrastructure\Http\Controller\UpdateIncomePatchController;
use App\Shared\Domain\Exception\ResourceNotFoundException;
use App\Tests\Context\Income\Domain\IncomeMother;
use App\Tests\Shared\Domain\UuidMother; // Added this line
use App\Tests\Shared\Infrastructure\PhpUnit\ApiTestCase;
use DateTime;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Symfony\Component\HttpFoundation\Response;

/**
 * @covers \App\Context\Income\Infrastructure\Http\Controller\UpdateIncomePatchController
 */
final class UpdateIncomePatchControllerTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->createAuthenticatedClient();
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function test_it_should_update_income(): void
    {
        // 1. Create an initial income to be updated
        $income = IncomeMother::create();
        $this->entityManager->persist($income->residentUnit());
        $this->entityManager->persist($income->incomeType());
        $this->entityManager->persist($income);
        $this->entityManager->flush();

        // 2. Define the update payload
        $updatedDescription = 'This is the updated income description.';
        $updatedDueDate = (new DateTime('+10 days'))->format('Y-m-d');
        $payload = [
            'description' => $updatedDescription,
            'dueDate' => $updatedDueDate,
        ];

        // 3. Send the PATCH request
        $this->client->request(
            'PATCH',
            '/api/v1/incomes/update/' . $income->id(),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload)
        );

        // 4. Assert the response
        $this->assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);

        // 5. Assert the changes in the database
        $this->entityManager->clear();
        /** @var Income|null $updatedIncome */
        $updatedIncome = $this->entityManager->find(Income::class, $income->id());

        $this->assertNotNull($updatedIncome);
        $this->assertEquals($updatedDescription, $updatedIncome->description());
        $this->assertEquals($updatedDueDate, $updatedIncome->dueDate()->format('Y-m-d'));
    }

    public function test_it_should_return_not_found_if_income_does_not_exist(): void
    {
        $nonExistentId = UuidMother::create();
        $updatedDescription = 'Non-existent income update.';
        $updatedDueDate = (new DateTime('+10 days'))->format('Y-m-d');
        $payload = [
            'description' => $updatedDescription,
            'dueDate' => $updatedDueDate,
        ];

        $this->client->request(
            'PATCH',
            '/api/v1/incomes/update/' . $nonExistentId,
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload)
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function test_it_maps_exceptions_correctly(): void
    {
        $controller = $this->getContainer()->get(UpdateIncomePatchController::class);
        $exceptions = $controller->exceptions();

        $this->assertArrayHasKey(ResourceNotFoundException::class, $exceptions);
        $this->assertEquals(Response::HTTP_NOT_FOUND, $exceptions[ResourceNotFoundException::class]);
    }
}
