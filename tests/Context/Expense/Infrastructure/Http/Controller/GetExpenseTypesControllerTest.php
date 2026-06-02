<?php

declare(strict_types=1);

namespace App\Tests\Context\Expense\Infrastructure\Http\Controller;

use App\Tests\Context\Expense\Domain\ExpenseTypeMother;
use App\Tests\Shared\Domain\UuidMother;
use App\Tests\Shared\Infrastructure\PhpUnit\ApiTestCase;
use Symfony\Component\HttpFoundation\Response;

use function array_column;
use function json_decode;

final class GetExpenseTypesControllerTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->createAuthenticatedClient();
    }

    public function testItShouldReturnAllExpenseTypes(): void
    {
        // 1. Create a couple of expense types to ensure the list is not empty
        $type1 = ExpenseTypeMother::create(id: UuidMother::create());
        $type2 = ExpenseTypeMother::create(id: UuidMother::create());

        $this->entityManager->persist($type1);
        $this->entityManager->persist($type2);
        $this->entityManager->flush();

        // 2. Send the GET request
        $this->client->request('GET', '/api/v1/expense-types');

        // 3. Assert the response
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $responseContent = $this->client->getResponse()->getContent();
        $data = json_decode($responseContent, true);

        // 4. Assert the content of the response
        $this->assertIsArray($data);
        $this->assertCount(2, $data);

        // Check that the IDs of the created types are in the response
        $responseIds = array_column($data, 'id');
        $this->assertContains($type1->id(), $responseIds);
        $this->assertContains($type2->id(), $responseIds);
    }
}
