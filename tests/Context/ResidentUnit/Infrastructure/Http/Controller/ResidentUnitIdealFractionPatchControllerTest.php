<?php

declare(strict_types=1);

namespace App\Tests\Context\ResidentUnit\Infrastructure\Http\Controller;

use App\Context\ResidentUnit\Domain\Exception\IdealFractionSumExceedsLimitException;
use App\Context\ResidentUnit\Domain\ResidentUnit;
use App\Context\ResidentUnit\Domain\ResidentUnitIdealFraction; // Added this line
use App\Context\ResidentUnit\Infrastructure\Http\Controller\ResidentUnitIdealFractionPatchController;
use App\Shared\Domain\Exception\InvalidArgumentException;
use App\Shared\Domain\Exception\ResourceNotFoundException;
use App\Tests\Context\ResidentUnit\Domain\ResidentUnitMother;
use App\Tests\Shared\Domain\UuidMother;
use App\Tests\Shared\Infrastructure\PhpUnit\ApiTestCase;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Symfony\Component\HttpFoundation\Response;

use function json_decode;
use function json_encode;

/**
 * @covers \App\Context\ResidentUnit\Infrastructure\Http\Controller\ResidentUnitIdealFractionPatchController
 */
final class ResidentUnitIdealFractionPatchControllerTest extends ApiTestCase
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
    public function testItShouldPatchIdealFractionSuccessfully(): void
    {
        // 1. Create a resident unit
        $residentUnit = ResidentUnitMother::create();
        $this->entityManager->persist($residentUnit);
        $this->entityManager->flush();

        // 2. Define the payload
        $newIdealFraction = 0.25;
        $payload = ['idealFraction' => $newIdealFraction];

        // 3. Send the PATCH request
        $this->client->request(
            'PATCH',
            '/api/v1/resident-unit/' . $residentUnit->id() . '/ideal-fraction',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload),
        );

        // 4. Assert the response
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        // 5. Assert that the ideal fraction was updated in the database
        $this->entityManager->clear();
        /** @var ResidentUnit|null $updatedResidentUnit */
        $updatedResidentUnit = $this->entityManager->find(ResidentUnit::class, $residentUnit->id());

        $this->assertNotNull($updatedResidentUnit);
        self::assertEquals($newIdealFraction, $updatedResidentUnit->idealFraction());
    }

    public function testItShouldReturnNotFoundWhenResidentUnitDoesNotExist(): void
    {
        $nonExistentId = UuidMother::create();
        $payload = ['idealFraction' => 0.5];

        $this->client->request(
            'PATCH',
            '/api/v1/resident-unit/' . $nonExistentId . '/ideal-fraction',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function testItShouldReturnBadRequestForInvalidIdealFractionValue(): void
    {
        $residentUnit = ResidentUnitMother::create();
        $this->entityManager->persist($residentUnit);
        $this->entityManager->flush();

        $payload = ['idealFraction' => 1.5]; // Invalid value

        $this->client->request(
            'PATCH',
            '/api/v1/resident-unit/' . $residentUnit->id() . '/ideal-fraction',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $responseContent = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertStringContainsString('A fração ideal deve ser maior ou igual a zero e menor ou igual a um.', $responseContent['message']);
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function testItShouldReturnConflictWhenIdealFractionSumExceedsLimit(): void
    {
        // 1. Create a resident unit with a high ideal fraction
        $residentUnit1 = ResidentUnitMother::create(idealFraction: new ResidentUnitIdealFraction(0.8));
        $this->entityManager->persist($residentUnit1);

        // 2. Create another resident unit
        $residentUnit2 = ResidentUnitMother::create(idealFraction: new ResidentUnitIdealFraction(0.1));
        $this->entityManager->persist($residentUnit2);
        $this->entityManager->flush();

        // 3. Try to update residentUnit2 with a value that makes the total sum > 1
        $payload = ['idealFraction' => 0.3]; // 0.8 + 0.3 = 1.1 > 1.0

        $this->client->request(
            'PATCH',
            '/api/v1/resident-unit/' . $residentUnit2->id() . '/ideal-fraction',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_CONFLICT);
        $responseContent = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertStringContainsString('A soma das frações ideais não pode ser maior que 1.', $responseContent['message']);
    }

    public function testItMapsExceptionsCorrectly(): void
    {
        $controller = $this->getContainer()->get(ResidentUnitIdealFractionPatchController::class);
        $exceptions = $controller->exceptions();

        $this->assertArrayHasKey(ResourceNotFoundException::class, $exceptions);
        self::assertEquals(Response::HTTP_NOT_FOUND, $exceptions[ResourceNotFoundException::class]);

        $this->assertArrayHasKey(IdealFractionSumExceedsLimitException::class, $exceptions);
        self::assertEquals(Response::HTTP_CONFLICT, $exceptions[IdealFractionSumExceedsLimitException::class]);

        $this->assertArrayHasKey(InvalidArgumentException::class, $exceptions);
        self::assertEquals(Response::HTTP_BAD_REQUEST, $exceptions[InvalidArgumentException::class]);
    }
}
