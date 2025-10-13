<?php

declare(strict_types=1);

namespace App\Context\Slip\Infrastructure\Http\Controller;

use App\Context\Slip\Application\UseCase\SlipGeneration\SlipGenerationCommand;
use App\Shared\Infrastructure\Symfony\ApiController;
use DateTimeImmutable;
use InvalidArgumentException;
use JsonException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Throwable;
use TypeError;

use function array_map;
use function explode;
use function is_string;
use function json_decode;
use function preg_match;

use const JSON_THROW_ON_ERROR;

final class SlipGenerationPostController extends ApiController
{
    public function __invoke(Request $request): Response
    {
        try {
            $data = json_decode($request->getContent() ?: '[]', true, 512, JSON_THROW_ON_ERROR);

            // Validate payload
            $targetMonth = $data['targetMonth'] ?? null;

            if (!is_string($targetMonth) || !preg_match('/^\d{4}-\d{2}$/', $targetMonth)) {
                throw new BadRequestHttpException('Invalid "targetMonth" format. Expected YYYY-MM.');
            }

            $force = (bool) ($data['force'] ?? false);

            // Parse year/month
            [$year, $month] = array_map('intval', explode('-', $targetMonth));

            if ($month < 1 || $month > 12) {
                throw new BadRequestHttpException('Invalid month. Must be between 01 and 12.');
            }

            // Auto-force backfills for past months to avoid policy rejections on historical generation
            $now = new DateTimeImmutable('now');
            $nowYear = (int) $now->format('Y');
            $nowMonth = (int) $now->format('m');
            $isPastMonth = ($year < $nowYear) || ($year === $nowYear && $month < $nowMonth);

            if ($isPastMonth) {
                $force = true;
            }

            // Build and dispatch a proper Command
            $command = new SlipGenerationCommand($year, $month, $force);
            $this->dispatch($command);

            return new Response('', Response::HTTP_CREATED);
        } catch (JsonException $e) {
            return new JsonResponse(['error' => 'Malformed JSON body'], Response::HTTP_BAD_REQUEST);
        } catch (BadRequestHttpException|InvalidArgumentException|TypeError $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (HandlerFailedException $e) {
            $root = $this->unwrap($e);

            if ($root instanceof InvalidArgumentException || $root instanceof BadRequestHttpException || $root instanceof TypeError) {
                return new JsonResponse(['error' => $root->getMessage()], Response::HTTP_BAD_REQUEST);
            }

            throw $root; // let unexpected errors surface in test/dev
        }
    }

    protected function exceptions(): array
    {
        return [
            InvalidArgumentException::class => Response::HTTP_BAD_REQUEST,
            BadRequestHttpException::class => Response::HTTP_BAD_REQUEST,
            TypeError::class => Response::HTTP_BAD_REQUEST,
        ];
    }

    private function unwrap(Throwable $e): Throwable
    {
        $t = $e;

        while ($t instanceof HandlerFailedException && $t->getPrevious() !== null) {
            $t = $t->getPrevious();
        }

        return $t;
    }
}
