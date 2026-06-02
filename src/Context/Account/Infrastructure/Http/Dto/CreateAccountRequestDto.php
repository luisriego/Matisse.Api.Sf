<?php

declare(strict_types=1);

namespace App\Context\Account\Infrastructure\Http\Dto;

use App\Shared\Domain\Exception\InvalidArgumentException;
use App\Shared\Infrastructure\RequestDto;
use DateTimeImmutable;
use Symfony\Component\HttpFoundation\Request;

use function filter_var;
use function is_string;

use const FILTER_VALIDATE_INT;

/**
 * Flat keys (no nested object): Symfony request bag populated from JSON cannot hold nested arrays.
 */
final readonly class CreateAccountRequestDto implements RequestDto
{
    public string $id;
    public string $name;
    public int $initialBalanceAmount;
    public string $initialBalanceDate;

    public function __construct(Request $request)
    {
        $id = $request->request->get('id');

        if (!is_string($id) || $id === '') {
            throw InvalidArgumentException::createFromMessage('Missing or invalid "id".');
        }
        $this->id = $id;

        $name = $request->request->get('name');

        if (!is_string($name) || $name === '') {
            throw InvalidArgumentException::createFromMessage('Missing or invalid "name".');
        }
        $this->name = $name;

        $amount = $request->request->get('initialBalanceAmount');

        if (filter_var($amount, FILTER_VALIDATE_INT) === false) {
            throw InvalidArgumentException::createFromMessage('Field "initialBalanceAmount" is required (integer, cents).');
        }
        $this->initialBalanceAmount = (int) $amount;

        $date = $request->request->get('initialBalanceDate');

        if (!is_string($date) || $date === '') {
            throw InvalidArgumentException::createFromMessage('Field "initialBalanceDate" is required (Y-m-d).');
        }
        $parsed = DateTimeImmutable::createFromFormat('Y-m-d', $date);

        if (false === $parsed || $parsed->format('Y-m-d') !== $date) {
            throw InvalidArgumentException::createFromMessage('initialBalanceDate must be a valid Y-m-d date.');
        }
        $this->initialBalanceDate = $date;
    }
}
