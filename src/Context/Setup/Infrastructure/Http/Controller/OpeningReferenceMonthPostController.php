<?php

declare(strict_types=1);

namespace App\Context\Setup\Infrastructure\Http\Controller;

use App\Context\Setup\Application\UseCase\RecordOpeningReferenceMonth\RecordOpeningReferenceMonthCommand;
use App\Shared\Domain\Exception\InvalidDataException;
use App\Shared\Infrastructure\Symfony\ApiController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use function array_key_exists;
use function is_int;
use function json_decode;

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
        );

        $this->dispatch($command);

        return new JsonResponse(['recorded' => true], Response::HTTP_CREATED);
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

    public function exceptions(): array
    {
        return [
            InvalidDataException::class => Response::HTTP_BAD_REQUEST,
        ];
    }
}
