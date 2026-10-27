<?php

declare(strict_types=1);

namespace App\Shared\Application;

use App\Shared\Domain\Exception\TransientException;

use function count;

/**
 * Executes a callback with exponential backoff retry logic.
 *
 * TransientException instances are retried up to $maxAttempts times
 * with exponential delay between attempts. Non-transient exceptions
 * are propagated immediately without retry.
 */
final class RetryMechanism
{
    public function __construct(
        private readonly int $maxAttempts = 3,
        private readonly float $baseDelayMs = 100,
    ) {}

    /**
     * @template T
     *
     * @param callable(): T $callback
     *
     * @throws TransientException When all attempts are exhausted.
     * @throws \Throwable         When a non-transient exception occurs.
     *
     * @return T
     */
    public function execute(callable $callback): mixed
    {
        $lastException = null;

        for ($attempt = 1; $attempt <= $this->maxAttempts; ++$attempt) {
            try {
                return $callback();
            } catch (TransientException $e) {
                $lastException = $e;

                if ($attempt < $this->maxAttempts) {
                    $delayMs = $this->baseDelayMs * (2 ** ($attempt - 1));
                    usleep((int) ($delayMs * 1000));
                }
            }
        }

        throw $lastException;
    }
}
