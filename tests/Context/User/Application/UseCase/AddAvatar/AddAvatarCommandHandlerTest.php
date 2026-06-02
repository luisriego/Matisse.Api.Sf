<?php

declare(strict_types=1);

namespace App\Tests\Context\User\Application\UseCase\AddAvatar;

use App\Context\User\Application\UseCase\AddAvatar\AddAvatarCommand;
use App\Context\User\Application\UseCase\AddAvatar\AddAvatarCommandHandler;
use App\Context\User\Domain\UserRepository;
use App\Tests\Context\User\Domain\UserMother;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\String\UnicodeString;

use function base64_decode;
use function file_exists;
use function file_put_contents;
use function mkdir;
use function sys_get_temp_dir;
use function tempnam;
use function unlink;

final class AddAvatarCommandHandlerTest extends TestCase
{
    private MockObject|UserRepository $userRepository;
    private MockObject|SluggerInterface $slugger;
    private AddAvatarCommandHandler $handler;
    private string $uploadsPath = '/tmp/avatars';

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->slugger = $this->createMock(SluggerInterface::class);

        $this->handler = new AddAvatarCommandHandler(
            $this->userRepository,
            $this->slugger,
            $this->uploadsPath,
        );

        if (!file_exists($this->uploadsPath)) {
            mkdir($this->uploadsPath, 0o777, true);
        }
    }

    public function testItShouldAddAvatarToUser(): void
    {
        // 1. Create a User
        $user = UserMother::createRandom();

        // 2. Create a dummy file for upload
        $avatarPath = tempnam(sys_get_temp_dir(), 'avatar');
        $gifContent = base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7', true);
        file_put_contents($avatarPath, $gifContent);

        $uploadedFile = new UploadedFile(
            $avatarPath,
            'avatar.gif',
            'image/gif',
            null,
            true,
        );

        // 3. Expect repository and slugger calls
        $this->userRepository->expects($this->once())
            ->method('findOneByIdOrFail')
            ->with($user->id())
            ->willReturn($user);

        $this->userRepository->expects($this->once())
            ->method('save');

        // CORREGIDO: Simular el slugger devolviendo un objeto de string real
        $this->slugger->method('slug')
            ->willReturn(new UnicodeString('avatar-slug'));

        // 4. Create and handle the command
        $command = new AddAvatarCommand($user->id(), $uploadedFile);
        ($this->handler)($command);

        // 5. Assert avatar was updated and file was moved
        $this->assertNotNull($user->avatar());
        $this->assertStringStartsWith('avatar-slug', $user->avatar()); // Assert filename starts with slug
        $this->assertFileExists($this->uploadsPath . '/' . $user->avatar());

        // Clean up
        @unlink($this->uploadsPath . '/' . $user->avatar());
    }

    public function testItShouldDeleteOldAvatarWhenAddingNewOne(): void
    {
        // 1. Create a User with an existing avatar
        $user = UserMother::createRandom();
        $oldAvatarFilename = 'old_avatar.jpg';
        $user->updateAvatar($oldAvatarFilename);

        // Create a dummy old avatar file
        $oldAvatarPath = $this->uploadsPath . '/' . $oldAvatarFilename;
        file_put_contents($oldAvatarPath, 'old_avatar_content');
        $this->assertFileExists($oldAvatarPath);

        // 2. Create a new dummy file for upload
        $newAvatarPath = tempnam(sys_get_temp_dir(), 'avatar');
        $gifContent = base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7', true);
        file_put_contents($newAvatarPath, $gifContent);
        $uploadedFile = new UploadedFile($newAvatarPath, 'new_avatar.gif', 'image/gif', null, true);

        // 3. Expect repository and slugger calls
        $this->userRepository->expects($this->once())
            ->method('findOneByIdOrFail')
            ->with($user->id())
            ->willReturn($user);

        $this->userRepository->expects($this->once())
            ->method('save');

        $this->slugger->method('slug')->willReturn(new UnicodeString('new-avatar-slug'));

        // 4. Create and handle the command
        $command = new AddAvatarCommand($user->id(), $uploadedFile);
        ($this->handler)($command);

        // 5. Assert old avatar was deleted and new one was saved
        $this->assertFileDoesNotExist($oldAvatarPath);
        $this->assertNotNull($user->avatar());
        $this->assertStringStartsWith('new-avatar-slug', $user->avatar());
        $this->assertFileExists($this->uploadsPath . '/' . $user->avatar());

        // Clean up
        @unlink($this->uploadsPath . '/' . $user->avatar());
    }

    public function testItShouldSlugifyFilenamesWithSpecialCharacters(): void
    {
        // 1. Create a User
        $user = UserMother::createRandom();

        // 2. Create a dummy file with a weird name
        $avatarPath = tempnam(sys_get_temp_dir(), 'avatar');
        $gifContent = base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7', true);
        file_put_contents($avatarPath, $gifContent);
        $uploadedFile = new UploadedFile(
            $avatarPath,
            'a-weird file name with spaces & chars!.gif', // <-- Weird name
            'image/gif',
            null,
            true,
        );

        // 3. Expect repository and slugger calls
        $this->userRepository->expects($this->once())
            ->method('findOneByIdOrFail')
            ->with($user->id())
            ->willReturn($user);

        $this->userRepository->expects($this->once())
            ->method('save');

        // We expect the slugger to be called with the original filename without extension
        $this->slugger->expects($this->once())
            ->method('slug')
            ->with('a-weird file name with spaces & chars!')
            ->willReturn(new UnicodeString('a-weird-file-name-with-spaces-chars'));

        // 4. Create and handle the command
        $command = new AddAvatarCommand($user->id(), $uploadedFile);
        ($this->handler)($command);

        // 5. Assert avatar filename is the slugified version
        $this->assertNotNull($user->avatar());
        $this->assertStringStartsWith('a-weird-file-name-with-spaces-chars', $user->avatar());
        $this->assertFileExists($this->uploadsPath . '/' . $user->avatar());

        // Clean up
        @unlink($this->uploadsPath . '/' . $user->avatar());
    }

    public function testItShouldNotUpdateUserIfFileMoveFails(): void
    {
        // 1. Create a User
        $user = UserMother::createRandom();

        // 2. Create a MOCK UploadedFile to force an exception
        $uploadedFile = $this->createMock(UploadedFile::class);
        $uploadedFile->method('getClientOriginalName')->willReturn('avatar.gif');
        $uploadedFile->method('guessExtension')->willReturn('gif');

        // 3. Expect repository and slugger calls
        $this->userRepository->expects($this->once())
            ->method('findOneByIdOrFail')
            ->with($user->id())
            ->willReturn($user);

        // CRUCIAL: We expect the save method to NEVER be called if the move fails
        $this->userRepository->expects($this->never())
            ->method('save');

        $this->slugger->method('slug')
            ->willReturn(new UnicodeString('avatar-slug'));

        // 4. Force the move method to throw an exception
        $uploadedFile->expects($this->once())
            ->method('move')
            ->willThrowException(new FileException('Move failed'));

        // 5. Expect the exception to be thrown
        $this->expectException(FileException::class);

        // 6. Create and handle the command
        $command = new AddAvatarCommand($user->id(), $uploadedFile);
        ($this->handler)($command);
    }
}
