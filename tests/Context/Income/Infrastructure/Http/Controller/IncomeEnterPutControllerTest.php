<?php

declare(strict_types=1);

namespace App\Tests\Context\Income\Infrastructure\Http\Controller;

use App\Context\EventStore\Domain\StoredEventRepository;
use App\Tests\Context\Account\Domain\AccountMother;
use App\Tests\Context\Income\Domain\IncomeTypeMother;
use App\Tests\Context\ResidentUnit\Domain\ResidentUnitMother;
use App\Tests\Shared\Domain\UuidMother;
use App\Tests\Shared\Infrastructure\PhpUnit\ApiTestCase;
use DateMalformedStringException;
use DateTimeImmutable;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Symfony\Component\HttpFoundation\Response;

use function json_encode;

final class IncomeEnterPutControllerTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->createAuthenticatedClient();
    }

    /**
     * @throws DateMalformedStringException
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function testItShouldEnterIncomeAndStoreEvent(): void
    {
        $incomeId = UuidMother::create();
        $residentUnit = ResidentUnitMother::create();
        $this->entityManager->persist($residentUnit);
        $incomeType = IncomeTypeMother::create();
        $this->entityManager->persist($incomeType);
        $account = AccountMother::create();
        $this->entityManager->persist($account);
        $this->entityManager->flush();

        $futureDate = (new DateTimeImmutable())->modify('+1 day')->format('Y-m-d');

        $payload = [
            'id' => $incomeId,
            'amount' => 5000,
            'residentUnitId' => $residentUnit->id(),
            'type' => $incomeType->id(),
            'accountId' => $account->id(),
            'dueDate' => $futureDate,
            'description' => 'Test Income Description',
        ];

        $this->client->request(
            'PUT',
            '/api/v1/incomes/enter',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $container = self::getContainer();

        /** @var StoredEventRepository $storedEventRepository */
        $storedEventRepository = $container->get(StoredEventRepository::class);
        $events = $storedEventRepository->findByEventType('income.entered');

        $this->assertCount(1, $events);
        $this->assertEquals('income.entered', $events[0]->eventType());
    }
}
