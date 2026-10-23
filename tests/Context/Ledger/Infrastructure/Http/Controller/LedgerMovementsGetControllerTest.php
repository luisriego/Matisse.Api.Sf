<?php

declare(strict_types=1);

namespace App\Tests\Context\Ledger\Infrastructure\Http\Controller;

use App\Tests\Shared\Infrastructure\PhpUnit\ApiTestCase;
use Symfony\Component\HttpFoundation\Response;

use function json_decode;

use const JSON_THROW_ON_ERROR;

final class LedgerMovementsGetControllerTest extends ApiTestCase
{
    public function testItReturnsEmptyListWhenMonthHasNoMovements(): void
    {
        $this->createAuthenticatedClient();

        $this->client->request('GET', '/api/v1/ledger/movements/2025/1');

        self::assertResponseStatusCodeSame(Response::HTTP_OK);

        $data = json_decode((string) $this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame('2025-01', $data['month']);
        self::assertSame([], $data['movements']);
    }
}
