<?php

declare(strict_types=1);

namespace App\Context\User\Infrastructure\Http\Controller;

use App\Shared\Infrastructure\Symfony\ApiController;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface; // Importar la interfaz
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Messenger\MessageBusInterface; // Importar MessageBusInterface
use Throwable;

final class LoginController extends ApiController
{
    private JWTTokenManagerInterface $jwtManager; // Declarar la propiedad

    public function __construct(
        MessageBusInterface $commandBus, // Inyectar dependencias de ApiController
        MessageBusInterface $queryBus,   // Inyectar dependencias de ApiController
        JWTTokenManagerInterface $jwtManager // Inyectar el JWTTokenManagerInterface
    ) {
        parent::__construct($commandBus, $queryBus); // Llamar al constructor del padre
        $this->jwtManager = $jwtManager;
    }

    /**
     * @throws Throwable
     */
    public function __invoke(#[CurrentUser] ?UserInterface $user): JsonResponse
    {
        if (null === $user) {
            return new JsonResponse(['message' => 'Invalid credentials.'], Response::HTTP_UNAUTHORIZED);
        }

        // Generar el token JWT
        $token = $this->jwtManager->create($user);

        return new JsonResponse([
            'message' => 'Login successful.',
            'token'   => $token, // Incluir el token en la respuesta
            'user'    => $user->getUserIdentifier(),
            'roles'   => $user->getRoles(),
        ]);
    }

    protected function exceptions(): array
    {
        // Define exception mappings if needed, though most auth errors are handled before this controller.
        return [];
    }
}
