<?php

declare(strict_types=1);

namespace App\Tests\Context\BankStatement\Infrastructure\Http\Controller;

use App\Tests\Context\ResidentUnit\Domain\ResidentUnitMother;
use App\Tests\Context\Slip\Domain\SlipMother;
use App\Tests\Context\Slip\Domain\ValueObject\SlipAmountMother;
use App\Tests\Context\Slip\Domain\ValueObject\SlipDueDateMother;
use App\Tests\Shared\Infrastructure\PhpUnit\ApiTestCase;
use Symfony\Component\HttpFoundation\Response;

final class OfxVerifyIncomePostControllerTest extends ApiTestCase
{
    private const MONTH = 3;
    private const YEAR  = 2026;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createAuthenticatedClient();
    }

    public function test_it_returns_balanced_when_credits_match_slips(): void
    {
        $unit  = ResidentUnitMother::create();
        $slip1 = SlipMother::create(
            amount:      SlipAmountMother::create(25000),
            residentUnit: $unit,
            dueDate:     SlipDueDateMother::create('2026-03-10'),
        );
        $slip1->markAsSubmitted();

        $slip2 = SlipMother::create(
            amount:      SlipAmountMother::create(25000),
            residentUnit: $unit,
            dueDate:     SlipDueDateMother::create('2026-03-10'),
        );
        $slip2->markAsSubmitted();
        $slip2->markAsPaid();

        $this->entityManager->persist($unit);
        $this->entityManager->persist($slip1);
        $this->entityManager->persist($slip2);
        $this->entityManager->flush();

        $payload = [
            'month'       => self::MONTH,
            'year'        => self::YEAR,
            'creditLines' => [
                ['fitId' => 'FIT-CR-001', 'amountInCents' => 25000, 'memo' => 'BOLETOS RECEBIDOS 04/03S'],
                ['fitId' => 'FIT-CR-002', 'amountInCents' => 25000, 'memo' => 'BOLETOS RECEBIDOS'],
            ],
        ];

        $this->client->request('POST', '/api/v1/bank/ofx-verify-income', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($payload, JSON_THROW_ON_ERROR));

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $data = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertSame(50000, $data['expectedInCents']);
        $this->assertSame(50000, $data['receivedInCents']);
        $this->assertSame(0, $data['differenceInCents']);
        $this->assertSame('balanced', $data['status']);
        $this->assertSame(2, $data['totalSlips']);
        $this->assertSame(1, $data['paidSlips']);
        $this->assertCount(1, $data['unpaidSlips']);
    }

    public function test_it_returns_shortfall_when_credits_are_less_than_expected(): void
    {
        $unit = ResidentUnitMother::create();
        $slip = SlipMother::create(
            amount:      SlipAmountMother::create(50000),
            residentUnit: $unit,
            dueDate:     SlipDueDateMother::create('2026-03-10'),
        );

        $this->entityManager->persist($unit);
        $this->entityManager->persist($slip);
        $this->entityManager->flush();

        $payload = [
            'month'       => self::MONTH,
            'year'        => self::YEAR,
            'creditLines' => [
                ['fitId' => 'FIT-CR-001', 'amountInCents' => 30000, 'memo' => 'BOLETOS RECEBIDOS'],
            ],
        ];

        $this->client->request('POST', '/api/v1/bank/ofx-verify-income', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($payload, JSON_THROW_ON_ERROR));

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $data = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertSame(50000, $data['expectedInCents']);
        $this->assertSame(30000, $data['receivedInCents']);
        $this->assertSame(-20000, $data['differenceInCents']);
        $this->assertSame('shortfall', $data['status']);
        $this->assertCount(1, $data['unpaidSlips']);
    }

    public function test_it_returns_balanced_with_no_slips_and_no_credits(): void
    {
        $payload = [
            'month'       => self::MONTH,
            'year'        => self::YEAR,
            'creditLines' => [],
        ];

        $this->client->request('POST', '/api/v1/bank/ofx-verify-income', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($payload, JSON_THROW_ON_ERROR));

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $data = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertSame(0, $data['expectedInCents']);
        $this->assertSame(0, $data['receivedInCents']);
        $this->assertSame(0, $data['differenceInCents']);
        $this->assertSame('balanced', $data['status']);
        $this->assertCount(0, $data['unpaidSlips']);
    }
}
