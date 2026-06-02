<?php

declare(strict_types=1);

namespace App\Context\Setup\Infrastructure\Http\Controller;

use App\Context\Setup\Application\UseCase\RecordOpeningReferenceMonth\RecordOpeningReferenceMonthCommand;
use App\Shared\Domain\Exception\InvalidDataException;
use App\Shared\Infrastructure\Symfony\ApiController;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\Uuid;

use function array_key_exists;
use function is_array;
use function is_int;
use function json_decode;
use function sprintf;

#[OA\Post(
    path: '/api/v1/setup/opening-reference-month',
    summary: 'Record opening reference month configuration',
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['referenceMonth', 'syndicAllocationRule', 'extraFeePerUnitCents', 'reserveFundPerUnitCents'],
            properties: [
                new OA\Property(property: 'referenceMonth', type: 'string', example: '2026-01', description: 'YYYY-MM'),
                new OA\Property(property: 'syndicAllocationRule', type: 'string'),
                new OA\Property(property: 'extraFeePerUnitCents', type: 'integer'),
                new OA\Property(property: 'reserveFundPerUnitCents', type: 'integer'),
                new OA\Property(property: 'expectedCommonExpensesCents', type: 'integer', nullable: true),
                new OA\Property(property: 'expectedSyndicShareTotalCents', type: 'integer', nullable: true),
                new OA\Property(property: 'expectedBoletoTotalCents', type: 'integer', nullable: true),
                new OA\Property(property: 'optionalGasTotalCents', type: 'integer', nullable: true),
                new OA\Property(property: 'ledgerAccountId', type: 'string', format: 'uuid', nullable: true),
            ],
        ),
    ),
    tags: ['Setup'],
    security: [['bearerAuth' => []]],
    responses: [
        new OA\Response(response: 201, description: 'Opening reference month recorded.'),
        new OA\Response(response: 400, description: 'Validation error.'),
        new OA\Response(response: 401, description: 'Unauthorized'),
    ],
)]
final class OpeningReferenceMonthPostController extends ApiController
{
    public function __invoke(Request $request): JsonResponse
    {
        /** @var array<string, mixed>|null $data */
        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            throw new InvalidDataException('JSON body required.');
        }

        $required = ['referenceMonth', 'syndicAllocationRule', 'extraFeePerUnitCents', 'reserveFundPerUnitCents'];

        foreach ($required as $key) {
            if (!array_key_exists($key, $data)) {
                throw new InvalidDataException(sprintf('Missing field "%s".', $key));
            }
        }

        $ref = (string) $data['referenceMonth'];
        $rule = (string) $data['syndicAllocationRule'];
        $extra = $data['extraFeePerUnitCents'];
        $reserve = $data['reserveFundPerUnitCents'];

        if (!is_int($extra) || !is_int($reserve)) {
            throw new InvalidDataException('extraFeePerUnitCents and reserveFundPerUnitCents must be integers (cents).');
        }

        $command = new RecordOpeningReferenceMonthCommand(
            $ref,
            $rule,
            $extra,
            $reserve,
            $this->optionalInt($data, 'expectedCommonExpensesCents'),
            $this->optionalInt($data, 'expectedSyndicShareTotalCents'),
            $this->optionalInt($data, 'expectedBoletoTotalCents'),
            $this->optionalInt($data, 'optionalGasTotalCents'),
            $this->optionalLedgerAccountId($data),
        );

        $this->dispatch($command);

        return new JsonResponse(['recorded' => true], Response::HTTP_CREATED);
    }

    public function exceptions(): array
    {
        return [
            InvalidDataException::class => Response::HTTP_BAD_REQUEST,
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    private function optionalInt(array $data, string $key): ?int
    {
        if (!array_key_exists($key, $data) || $data[$key] === null) {
            return null;
        }

        if (!is_int($data[$key])) {
            throw new InvalidDataException(sprintf('Field "%s" must be an integer or null.', $key));
        }

        return $data[$key];
    }

    /**
     * @param array<string, mixed> $data
     */
    private function optionalLedgerAccountId(array $data): ?string
    {
        if (!array_key_exists('ledgerAccountId', $data) || $data['ledgerAccountId'] === null || $data['ledgerAccountId'] === '') {
            return null;
        }

        $id = (string) $data['ledgerAccountId'];

        if (!Uuid::isValid($id)) {
            throw new InvalidDataException('ledgerAccountId must be a valid UUID when provided.');
        }

        return $id;
    }
}
