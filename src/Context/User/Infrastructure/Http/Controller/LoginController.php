<?php

declare(strict_types=1);

namespace App\Context\User\Infrastructure\Http\Controller;

use App\Context\User\Domain\User;
use App\Shared\Infrastructure\Symfony\ApiController;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface; // Importar la interfaz
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Attribute\CurrentUser; // Importar MessageBusInterface
use Throwable;

final class LoginController extends ApiController
{
    private JWTTokenManagerInterface $jwtManager; // Declarar la propiedad

    public function __construct(
        MessageBusInterface $commandBus, // Inyectar dependencias de ApiController
        MessageBusInterface $queryBus,   // Inyectar dependencias de ApiController
        JWTTokenManagerInterface $jwtManager, // Inyectar el JWTTokenManagerInterface
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

        // Asegurarse de que el usuario es una instancia de nuestra entidad User
        if (!$user instanceof User) {
            return new JsonResponse(['message' => 'Invalid user type.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        // Generar el token JWT
        $token = $this->jwtManager->create($user);

        return new JsonResponse([
            'message' => 'Login successful.',
            'token'   => $token,
        ]);
    }

    protected function exceptions(): array
    {
        // Define exception mappings if needed, though most auth errors are handled before this controller.
        return [];
    }
}
