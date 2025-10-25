<?php

declare(strict_types=1);

namespace App\Context\Slip\Infrastructure\Http\Dto;

use App\Context\Slip\Domain\ValueObject\SlipAmount;
use Symfony\Component\HttpFoundation\Request;
use App\Shared\Domain\Exception\InvalidArgumentException;

class SlipCheckTotalRequestDto
{
    public SlipAmount $amount; // <-- La propiedad DEBE ser de tipo SlipAmount

    public static function fromRequest(Request $request): self
    {
        $data = json_decode($request->getContent(), true);
        $dto = new self();

        try {
            $rawAmount = $data['amount'] ?? null;

            if (!is_numeric($rawAmount)) {
                throw new InvalidArgumentException("O valor 'amount' deve ser numérico.");
            }

            $intValue = (int) round((float) $rawAmount);

            // Aquí está la magia: creamos el objeto y lo asignamos.
            $dto->amount = new SlipAmount($intValue);

        } catch (InvalidArgumentException $e) {
            throw new \RuntimeException("Erro de validação do DTO: " . $e->getMessage(), 0, $e);
        }

        return $dto;
    }
}
