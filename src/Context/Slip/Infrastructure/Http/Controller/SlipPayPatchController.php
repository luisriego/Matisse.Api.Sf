<?php

declare(strict_types=1);

namespace App\Context\Slip\Infrastructure\Http\Controller;

use App\Context\Slip\Application\UseCase\PaySlip\PaySlipCommand;
use App\Context\Slip\Domain\Exception\SlipNotFoundException;
use App\Shared\Infrastructure\Symfony\ApiController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Workflow\Exception\LogicException;
use Throwable;

use function class_exists;
use function str_ends_with;

final class SlipPayPatchController extends ApiController
{
    public function __invoke(string $id): JsonResponse
    {
        try {
            $this->dispatch(new PaySlipCommand($id));

            return new JsonResponse('', Response::HTTP_ACCEPTED);
        } catch (HandlerFailedException $e) {
            $root = $this->unwrap($e);

            if ($root instanceof SlipNotFoundException || $this->isSharedNotFound($root)) {
                return new JsonResponse(['error' => $root->getMessage()], Response::HTTP_NOT_FOUND);
            }

            if ($root instanceof LogicException) {
                return new JsonResponse(['error' => $root->getMessage()], Response::HTTP_CONFLICT);
            }

            throw $root; // Let Symfony render the real root cause in test/dev
        } catch (SlipNotFoundException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        } catch (LogicException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_CONFLICT);
        }
    }

    protected function exceptions(): array
    {
        return [
            SlipNotFoundException::class => Response::HTTP_NOT_FOUND,
            LogicException::class => Response::HTTP_CONFLICT, // Workflow transition not allowed
        ];
    }

    /**
     * Unwrap to the first non-HandlerFailedException cause.
     */
    private function unwrap(Throwable $e): Throwable
    {
        $t = $e;

        while ($t instanceof HandlerFailedException && $t->getPrevious() !== null) {
            $t = $t->getPrevious();
        }

        return $t;
    }

    /**
     * Checks if the throwable represents a shared NotFound exception without hard coupling.
     */
    private function isSharedNotFound(Throwable $e): bool
    {
        // Prefer instanceof if class exists to avoid fatal in instanceof on unknown class
        $sharedClass = 'App\Shared\Domain\Exception\NotFoundException';

        if (class_exists($sharedClass)) {
            return $e instanceof $sharedClass;
        }

        // Fallback by class name suffix match
        return str_ends_with($e::class, 'NotFoundException');
    }
}
