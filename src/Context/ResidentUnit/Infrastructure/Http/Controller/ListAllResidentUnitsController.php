<?php

declare(strict_types=1);

namespace App\Context\ResidentUnit\Infrastructure\Http\Controller;

use App\Context\ResidentUnit\Application\UseCase\ListAll\ListAllResidentUnitsQuery;
use App\Context\ResidentUnit\Domain\ResidentUnit;
use App\Shared\Infrastructure\Symfony\ApiController;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

use function array_map;

#[OA\Get(
    path: '/api/v1/resident-unit/all',
    summary: 'List all resident units',
    description: 'Returns every unit in the database, including inactive ones and those with idealFraction = 0. Useful during setup to detect orphan records not returned by `/actives`.',
    tags: ['Resident Units'],
    security: [['bearerAuth' => []]],
    responses: [
        new OA\Response(
            response: 200,
            description: 'Array of all resident units (no wrapper object).',
            content: new OA\JsonContent(
                type: 'array',
                items: new OA\Items(ref: '#/components/schemas/ResidentUnit'),
            ),
        ),
        new OA\Response(response: 401, description: 'Unauthorized'),
    ],
)]
final class ListAllResidentUnitsController extends ApiController
{
    /**
     * @throws Throwable
     */
    public function __invoke(): JsonResponse
    {
        $residentUnits = (array) $this->ask(new ListAllResidentUnitsQuery());

        $data = array_map(static fn (ResidentUnit $residentUnit) => [
            'id' => $residentUnit->id(),
            'unit' => $residentUnit->unit(),
            'idealFraction' => $residentUnit->idealFraction(),
            'isActive' => $residentUnit->isActive(),
            'createdAt' => $residentUnit->createdAt()->format('Y-m-d H:i:s'),
            'updatedAt' => $residentUnit->updatedAt()?->format('Y-m-d H:i:s'),
            'notificationRecipients' => array_map(static fn (array $recipient) => [
                'name' => $recipient['name'],
                'email' => $recipient['email'],
            ], $residentUnit->notificationRecipients()),
        ], $residentUnits);

        return new JsonResponse($data, Response::HTTP_OK);
    }

    public function exceptions(): array
    {
        return [];
    }
}
