<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure;

use App\Context\Setup\Application\Service\SetupStatusChecker;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;

use function str_starts_with;

final class SetupRequiredListener
{
    /**
     * Allowed while setup is incomplete: auth/registration, setup API, and data needed to
     * finish the wizard (accounts, units, gas, expenses).
     */
    private const EXEMPT_PREFIXES = [
        '/api/v1/setup/',
        '/api/v1/users/register',
        '/api/v1/users/activate',
        '/api/v1/users/password-reset',
        '/api/v1/login_check',
        '/api/v1/accounts',
        '/api/v1/resident-unit/',
        '/api/v1/gas/',
        '/api/v1/expense-types',
        '/api/v1/expenses',
        '/api/v1/recurring-expenses',
    ];

    public function __construct(
        private readonly SetupStatusChecker $checker,
        #[Autowire('%kernel.environment%')]
        private readonly string $kernelEnvironment,
    ) {}

    public function onKernelRequest(RequestEvent $event): void
    {
        if ($this->kernelEnvironment === 'test') {
            return;
        }

        if (!$event->isMainRequest()) {
            return;
        }

        $path = $event->getRequest()->getPathInfo();

        if (!str_starts_with($path, '/api/v1/')) {
            return;
        }

        foreach (self::EXEMPT_PREFIXES as $prefix) {
            if (str_starts_with($path, $prefix)) {
                return;
            }
        }

        if ($this->checker->isComplete()) {
            return;
        }

        $status = $this->checker->status();

        $event->setResponse(new JsonResponse(
            [
                'error'   => 'SETUP_REQUIRED',
                'message' => $status['message'],
                'setup'   => $status,
            ],
            Response::HTTP_FORBIDDEN,
        ));
    }
}
