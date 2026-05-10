<?php

declare(strict_types=1);

namespace App\Tests\Context\Setup\Infrastructure\Http\Controller;

use App\Tests\Shared\Infrastructure\PhpUnit\ApiTestCase;
use Symfony\Component\HttpFoundation\Response;

final class OpeningReferenceMonthPostControllerTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->createAuthenticatedClient();
    }

    public function test_it_records_opening_reference_month_and_exposes_it_in_setup_status(): void
    {
        $body = [
            'referenceMonth' => '2026-01',
            'syndicAllocationRule' => 'ideal_fraction',
            'extraFeePerUnitCents' => 25000,
            'reserveFundPerUnitCents' => 9370,
            'expectedCommonExpensesCents' => 100000,
            'expectedBoletoTotalCents' => 832428,
        ];

        $this->client->request(
            'POST',
            '/api/v1/setup/opening-reference-month',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($body, JSON_THROW_ON_ERROR),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $this->client->request('GET', '/api/v1/setup/status');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame('complete', $data['steps']['openingReferenceMonth']);
        $this->assertNotNull($data['openingReference']);
        $this->assertSame('2026-01', $data['openingReference']['referenceMonth']);
        $this->assertSame('ideal_fraction', $data['openingReference']['syndicAllocationRule']);
        $this->assertSame(25000, $data['openingReference']['extraFeePerUnitCents']);
    }

    public function test_it_rejects_invalid_allocation_rule(): void
    {
        $body = [
            'referenceMonth' => '2026-01',
            'syndicAllocationRule' => 'invalid',
            'extraFeePerUnitCents' => 0,
            'reserveFundPerUnitCents' => 0,
        ];

        $this->client->request(
            'POST',
            '/api/v1/setup/opening-reference-month',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($body, JSON_THROW_ON_ERROR),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function test_second_post_appends_and_status_shows_latest_payload(): void
    {
        $this->client->request(
            'POST',
            '/api/v1/setup/opening-reference-month',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'referenceMonth' => '2026-01',
                'syndicAllocationRule' => 'equal_parts',
                'extraFeePerUnitCents' => 100,
                'reserveFundPerUnitCents' => 200,
            ], JSON_THROW_ON_ERROR),
        );
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $this->client->request(
            'POST',
            '/api/v1/setup/opening-reference-month',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'referenceMonth' => '2026-02',
                'syndicAllocationRule' => 'equal_parts',
                'extraFeePerUnitCents' => 300,
                'reserveFundPerUnitCents' => 400,
            ], JSON_THROW_ON_ERROR),
        );
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $this->client->request('GET', '/api/v1/setup/status');
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame('2026-02', $data['openingReference']['referenceMonth']);
        $this->assertSame(300, $data['openingReference']['extraFeePerUnitCents']);
    }
}
