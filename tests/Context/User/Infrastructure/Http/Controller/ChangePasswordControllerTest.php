<?php

declare(strict_types=1);

namespace App\Tests\Context\User\Infrastructure\Http\Controller;

use App\Tests\Shared\Infrastructure\PhpUnit\ApiTestCase;
use Symfony\Component\HttpFoundation\Response;

use function json_encode;

final class ChangePasswordControllerTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->client = $this->createAuthenticatedClient('user-for-change-pass@example.com', 'old-password-123');
    }

    public function testItShouldChangePasswordForAuthenticatedUser(): void
    {
        $newPassword = 'new-awesome-password-456';

        $this->client->request(
            'PATCH',
            '/api/v1/users/change-password',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'oldPassword' => 'old-password-123',
                'newPassword' => $newPassword,
            ]),
        );

        $this->assertResponseIsSuccessful();
    }

    public function testItShouldReturnBadRequestForInvalidOldPassword(): void
    {
        $this->client->request(
            'PATCH',
            '/api/v1/users/change-password',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'oldPassword' => 'this-is-a-wrong-password',
                'newPassword' => 'any-new-password',
            ]),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }
}
