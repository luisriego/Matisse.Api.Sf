<?php

declare(strict_types=1);

namespace App\Tests\Context\User\Infrastructure\Http\Controller;

use App\Context\User\Domain\User;
use App\Tests\Shared\Infrastructure\PhpUnit\ApiTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class UploadAvatarControllerTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->createAuthenticatedClient();
    }

    public function test_it_should_upload_avatar_and_update_user(): void
    {
        // 1. Get the authenticated User
        $user = $this->getAuthenticatedUser();

        // 2. Create a dummy but valid 1x1 GIF file for upload
        $avatarPath = tempnam(sys_get_temp_dir(), 'avatar');
        $gifContent = base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
        file_put_contents($avatarPath, $gifContent);

        $uploadedFile = new UploadedFile(
            $avatarPath,
            'avatar.gif',
            'image/gif',
            null,
            true
        );

        // 3. Make the request
        $this->client->request(
            'POST',
            sprintf('/api/v1/users/%s/avatar', $user->id()),
            [],
            ['avatar' => $uploadedFile]
        );

        // 4. Assert response
        $this->assertResponseIsSuccessful();

        // 5. Assert user avatar was updated
        $this->entityManager->clear();
        /** @var User $updatedUser */
        $updatedUser = $this->entityManager->find(User::class, $user->id());
        $this->assertNotNull($updatedUser->avatar());

        // 6. Assert file exists
        $uploadsPath = self::getContainer()->getParameter('avatar_uploads_path'); // <-- CORREGIDO
        $this->assertFileExists($uploadsPath . '/' . $updatedUser->avatar());

        // Clean up the created file
        @unlink($uploadsPath . '/' . $updatedUser->avatar());
    }
}
