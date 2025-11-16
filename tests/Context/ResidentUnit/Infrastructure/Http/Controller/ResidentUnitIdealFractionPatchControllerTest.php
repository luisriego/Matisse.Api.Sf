<?php

declare(strict_types=1);

namespace App\Tests\Context\ResidentUnit\Infrastructure\Http\Controller;

use App\Context\ResidentUnit\Domain\ResidentUnit;
use App\Context\ResidentUnit\Domain\ResidentUnitRepository;
use App\Tests\Shared\Infrastructure\PhpUnit\ApiTestCase;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\Response;

final class ResidentUnitIdealFractionPatchControllerTest extends ApiTestCase
{
    private ResidentUnitRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createAuthenticatedClient();
        $this->repository = $this->getContainer()->get(ResidentUnitRepository::class);
    }

    public function test_patch_ideal_fraction_successfully(): void
    {
        $residentUnit = $this->givenThereIsAResidentUnit('U1', 0.1);
        $newIdealFraction = 0.15;

        $this->client->request(
            'PATCH',
            '/api/v1/resident-unit/' . $residentUnit->id() . '/ideal-fraction',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['idealFraction' => $newIdealFraction])
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $updatedResidentUnit = $this->repository->findOneById($residentUnit->id());
        $this->assertEquals($newIdealFraction, $updatedResidentUnit->idealFraction());
    }

    public function test_returns_404_when_resident_unit_not_found(): void
    {
        $this->client->request(
            'PATCH',
            '/api/v1/resident-unit/' . Uuid::uuid4()->toString() . '/ideal-fraction',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['idealFraction' => 0.1])
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function test_returns_409_when_ideal_fraction_sum_exceeds_limit(): void
    {
        $this->givenThereIsAResidentUnit('U1', 0.5);
        $residentUnit2 = $this->givenThereIsAResidentUnit('U2', 0.5);

        $this->client->request(
            'PATCH',
            '/api/v1/resident-unit/' . $residentUnit2->id() . '/ideal-fraction',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['idealFraction' => 0.6])
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_CONFLICT);
    }

    public function test_returns_422_for_invalid_payload(): void
    {
        $residentUnit = $this->givenThereIsAResidentUnit('U3', 0.1);

        $this->client->request(
            'PATCH',
            '/api/v1/resident-unit/' . $residentUnit->id() . '/ideal-fraction',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['idealFraction' => -0.1])
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    private function givenThereIsAResidentUnit(string $unit, float $idealFraction): ResidentUnit
    {
        /** @var ResidentUnit $residentUnit */
        $residentUnit = ResidentUnit::create(
            new \App\Context\ResidentUnit\Domain\ResidentUnitId(Uuid::uuid4()->toString()),
            new \App\Context\ResidentUnit\Domain\ResidentUnitVO($unit),
            new \App\Context\ResidentUnit\Domain\ResidentUnitIdealFraction($idealFraction)
        );

        $this->repository->save($residentUnit);

        return $residentUnit;
    }
}
