<?php

declare(strict_types=1);

namespace App\Tests\Context\BankStatement\Infrastructure\Http\Controller;

use App\Tests\Shared\Infrastructure\PhpUnit\ApiTestCase;
use Symfony\Component\HttpFoundation\Response;

final class OfxMatchingContextGetControllerTest extends ApiTestCase
{
    public function test_it_returns_matching_context_json(): void
    {
        $this->createAuthenticatedClient();
        $this->client->request('GET', '/api/v1/bank/ofx-matching-context');

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        $data = json_decode((string) $this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(12, $data['historyWindowMonths']);
        self::assertArrayHasKey('windowStartDate', $data);
        self::assertArrayHasKey('windowEndDate', $data);
        self::assertArrayHasKey('activeExpenseCountInWindow', $data);
        self::assertArrayHasKey('activeExpenseWithDescriptionCountInWindow', $data);
        self::assertArrayHasKey('incomeRecordedCountInWindow', $data);
        self::assertArrayHasKey('incomeWithDescriptionCountInWindow', $data);
        self::assertArrayHasKey('expenseEmbeddingIndexedCount', $data);
        self::assertArrayHasKey('debitSqlHistoryAvailable', $data);
        self::assertArrayHasKey('debitSemanticIndexAvailable', $data);
        self::assertArrayHasKey('creditSqlHistoryAvailable', $data);
        self::assertArrayHasKey('manualDebitClassificationExpected', $data);
    }
}
