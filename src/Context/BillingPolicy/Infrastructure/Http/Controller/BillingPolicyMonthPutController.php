<?php

declare(strict_types=1);

namespace App\Context\BillingPolicy\Infrastructure\Http\Controller;

use App\Context\BillingPolicy\Application\UseCase\RecordBillingPolicyMonth\RecordBillingPolicyMonthCommand;
use App\Shared\Domain\Exception\InvalidDataException;
use App\Shared\Infrastructure\Symfony\ApiController;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use function array_key_exists;
use function is_array;
use function is_int;
use function json_decode;
use function preg_match;
use function sprintf;

#[OA\Put(
    path: '/api/v1/billing-policy/months/{targetMonth}',
    summary: 'Record monthly billing parameters',
    tags: ['Billing Policy'],
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(
            name: 'targetMonth',
            in: 'path',
            required: true,
            schema: new OA\Schema(type: 'string', example: '2026-01'),
        ),
    ],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: [
                'extraFeePerUnitCents',
                'reserveFundPerUnitCents',
                'syndicShareTotalCents',
            ],
            properties: [
                new OA\Property(property: 'extraFeePerUnitCents', type: 'integer'),
                new OA\Property(property: 'reserveFundPerUnitCents', type: 'integer'),
                new OA\Property(property: 'syndicShareTotalCents', type: 'integer'),
                new OA\Property(property: 'gasPricePerM3Cents', type: 'integer', nullable: true),
            ],
        ),
    ),
    responses: [
        new OA\Response(response: 201, description: 'Parameters recorded.'),
        new OA\Response(response: 400, description: 'Validation error.'),
        new OA\Response(response: 401, description: 'Unauthorized'),
    ],
)]
final class BillingPolicyMonthPutController extends ApiController
{
    public function __invoke(Request $request, string $targetMonth): JsonResponse
    {
        if (1 !== preg_match('/^\d{4}-\d{2}$/', $targetMonth)) {
            throw new InvalidDataException('targetMonth must be YYYY-MM.');
        }

        /** @var array<string, mixed>|null $data */
        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            throw new InvalidDataException('JSON body required.');
        }

        $extra = $this->readRequiredInt($data, 'extraFeePerUnitCents', 'extra_fee_per_unit_cents');
        $reserve = $this->readRequiredInt($data, 'reserveFundPerUnitCents', 'reserve_fund_per_unit_cents');
        $syndic = $this->readRequiredInt($data, 'syndicShareTotalCents', 'syndic_share_total_cents');
        $gas = $this->readNullableInt($data, 'gasPricePerM3Cents', 'gas_price_per_m3_cents');

        $this->dispatch(new RecordBillingPolicyMonthCommand(
            $targetMonth,
            $extra,
            $reserve,
            $syndic,
            $gas,
        ));

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
    private function readRequiredInt(array $data, string $camel, string $snake): int
    {
        $value = $this->readInt($data, $camel, $snake);

        if ($value === null) {
            throw new InvalidDataException(sprintf('Missing field "%s".', $camel));
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function readInt(array $data, string $camel, string $snake): ?int
    {
        foreach ([$camel, $snake] as $key) {
            if (!array_key_exists($key, $data)) {
                continue;
            }

            if (!is_int($data[$key])) {
                throw new InvalidDataException(sprintf('Field "%s" must be an integer.', $key));
            }

            return $data[$key];
        }

        return null;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function readNullableInt(array $data, string $camel, string $snake): ?int
    {
        foreach ([$camel, $snake] as $key) {
            if (!array_key_exists($key, $data)) {
                continue;
            }

            if ($data[$key] === null) {
                return null;
            }

            if (!is_int($data[$key])) {
                throw new InvalidDataException(sprintf('Field "%s" must be an integer or null.', $key));
            }

            return $data[$key];
        }

        return null;
    }
}
