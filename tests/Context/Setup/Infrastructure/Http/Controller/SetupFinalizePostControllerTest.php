<?php

declare(strict_types=1);

namespace App\Tests\Context\Setup\Infrastructure\Http\Controller;

use App\Context\Account\Domain\Event\InitialBalanceSet;
use App\Context\Expense\Domain\Event\ExpenseWasEntered;
use App\Context\Gas\Domain\Event\GasPriceWasDefined;
use App\Context\Gas\Domain\Event\GasReadingWasRecorded;
use App\Context\Setup\Domain\Event\OpeningReferenceMonthWasRecorded;
use App\Context\Setup\Domain\Event\SetupWasCompleted;
use App\Context\Setup\Domain\OpeningSetupAggregateId;
use App\Context\Setup\Domain\SyndicAllocationRule;
use App\Shared\Domain\ValueObject\Uuid;
use App\Tests\Context\Account\Domain\AccountIdMother;
use App\Tests\Context\Account\Domain\AccountNameMother;
use App\Tests\Context\Account\Domain\AccountMother;
use App\Tests\Context\ResidentUnit\Domain\ResidentUnitIdMother;
use App\Tests\Context\ResidentUnit\Domain\ResidentUnitIdealFractionMother;
use App\Tests\Context\ResidentUnit\Domain\ResidentUnitMother;
use App\Tests\Context\ResidentUnit\Domain\ResidentUnitVOMother;
use App\Tests\Shared\Infrastructure\PhpUnit\ApiTestCase;
use DateTimeImmutable;

use function count;

final class SetupFinalizePostControllerTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->createAuthenticatedClient();
    }

    /**
     * TEN-03: Finalizar el setup inicial es idempotente.
     *
     * Dado: un condominio cuyo setup inicial ya ha sido finalizado
     * Cuando: se vuelve a invocar POST /api/v1/setup/finalize
     * Entonces: responde 200 OK, no duplica SetupWasCompleted, status mantiene isFinalized=true
     */
    public function testItIsIdempotentWhenSetupAlreadyFinalized(): void
    {
        // --- Arrange: complete all core steps + opening reference month ---
        $this->seedCoreSetup();

        // First finalize
        $this->client->request('POST', '/api/v1/setup/finalize');
        $this->assertResponseStatusCodeSame(200);

        // Count SetupWasCompleted events after first finalize
        /** @var \App\Context\EventStore\Domain\StoredEventRepository $eventRepo */
        $eventRepo = self::getContainer()->get(\App\Context\EventStore\Domain\StoredEventRepository::class);
        $eventsAfterFirst = $eventRepo->findByEventTypesAndOccurredBetweenAndAggregateId(
            [SetupWasCompleted::eventName()],
            new DateTimeImmutable('1900-01-01'),
            null,
            OpeningSetupAggregateId::VALUE,
        );
        $countAfterFirst = count($eventsAfterFirst);

        // Verify status shows finalized
        $this->client->request('GET', '/api/v1/setup/status');
        $this->assertResponseStatusCodeSame(200);
        $status = json_decode($this->client->getResponse()->getContent(), true);
        self::assertTrue($status['setupFinalized']);

        // --- Act: second finalize (idempotent call) ---
        $this->client->request('POST', '/api/v1/setup/finalize');
        $this->assertResponseStatusCodeSame(200);

        // --- Assert: no duplicate events ---
        $eventsAfterSecond = $eventRepo->findByEventTypesAndOccurredBetweenAndAggregateId(
            [SetupWasCompleted::eventName()],
            new DateTimeImmutable('1900-01-01'),
            null,
            OpeningSetupAggregateId::VALUE,
        );
        self::assertCount($countAfterFirst, $eventsAfterSecond);

        // --- Assert: status still shows finalized ---
        $this->client->request('GET', '/api/v1/setup/status');
        $this->assertResponseStatusCodeSame(200);
        $status = json_decode($this->client->getResponse()->getContent(), true);
        self::assertTrue($status['setupFinalized']);
    }

    private function seedCoreSetup(): void
    {
        $now = new DateTimeImmutable();

        // 1. Create active accounts with initial balances
        $account1 = AccountMother::create(
            AccountIdMother::create(),
            AccountNameMother::create('Banco'),
        );
        $account2 = AccountMother::create(
            AccountIdMother::create(),
            AccountNameMother::create('Caja'),
        );

        /** @var \App\Context\Account\Domain\AccountRepository $accountRepo */
        $accountRepo = self::getContainer()->get(\App\Context\Account\Domain\AccountRepository::class);
        $accountRepo->save($account1);
        $accountRepo->save($account2);

        // Activate accounts (checkInitialBalances only counts active accounts)
        $account1->enable();
        $account2->enable();
        $accountRepo->save($account1);
        $accountRepo->save($account2);

        // Emit InitialBalanceSet for each account
        /** @var \App\Shared\Application\EventStore $eventStore */
        $eventStore = self::getContainer()->get(\App\Shared\Application\EventStore::class);
        $eventStore->append(new InitialBalanceSet(
            $account1->id(),
            1000000, // cents
            $now->format('Y-m-d'),
        ));
        $eventStore->append(new InitialBalanceSet(
            $account2->id(),
            500000, // cents
            $now->format('Y-m-d'),
        ));

        // 2. Create active resident units
        $unit1 = ResidentUnitMother::create(
            ResidentUnitIdMother::create(),
            ResidentUnitVOMother::create('A-101'),
            ResidentUnitIdealFractionMother::create(0.5),
        );
        $unit2 = ResidentUnitMother::create(
            ResidentUnitIdMother::create(),
            ResidentUnitVOMother::create('A-102'),
            ResidentUnitIdealFractionMother::create(0.5),
        );

        /** @var \App\Context\ResidentUnit\Domain\ResidentUnitRepository $unitRepo */
        $unitRepo = self::getContainer()->get(\App\Context\ResidentUnit\Domain\ResidentUnitRepository::class);
        $unitRepo->save($unit1);
        $unitRepo->save($unit2);

        // Emit GasPriceWasDefined
        $eventStore->append(new GasPriceWasDefined(
            Uuid::random()->value(),
            15000, // cents per m3
        ));

        // Emit GasReadingWasRecorded for each unit
        $eventStore->append(new GasReadingWasRecorded(
            Uuid::random()->value(),
            $unit1->id(),
            (int) $now->format('Y'),
            (int) $now->format('m'),
            100.0,
        ));
        $eventStore->append(new GasReadingWasRecorded(
            Uuid::random()->value(),
            $unit2->id(),
            (int) $now->format('Y'),
            (int) $now->format('m'),
            200.0,
        ));

        // Emit ExpenseWasEntered (at least one)
        $eventStore->append(new ExpenseWasEntered(
            Uuid::random()->value(),
            50000, // cents
            'maintenance',
            $account1->id(),
            $now->format('Y-m-d'),
            'Gastos iniciales',
        ));

        // Emit OpeningReferenceMonthWasRecorded
        $eventStore->append(OpeningReferenceMonthWasRecorded::create(
            '2025-01',
            SyndicAllocationRule::EqualParts,
            1000,  // extraFeePerUnitCents
            500,   // reserveFundPerUnitCents
        ));
    }
}
