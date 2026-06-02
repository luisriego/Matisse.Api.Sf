<?php

declare(strict_types=1);

namespace App\Tests\Context\User\Infrastructure\Symfony\Normalizer;

use App\Context\ResidentUnit\Domain\ResidentUnit;
use App\Context\User\Domain\User;
use App\Context\User\Infrastructure\Symfony\Normalizer\UserNormalizer;
use DateTime;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use stdClass;
use Symfony\Component\Serializer\Serializer;

 // Import the concrete Serializer class

final class UserNormalizerTest extends TestCase
{
    private UserNormalizer $normalizer;
    private MockObject|Serializer $mockSerializer; // Use concrete Serializer class for mock

    protected function setUp(): void
    {
        parent::setUp();

        // Mock the concrete Serializer class
        $this->mockSerializer = $this->createMock(Serializer::class);
        $this->normalizer = new UserNormalizer();
        $this->normalizer->setSerializer($this->mockSerializer);
    }

    public function testSupportsNormalization(): void
    {
        $user = $this->createMock(User::class);
        $this->assertTrue($this->normalizer->supportsNormalization($user));
        $this->assertFalse($this->normalizer->supportsNormalization(new stdClass()));
    }

    public function testNormalizeUserWithResidentUnitAndAllData(): void
    {
        $userId = 'b0eebc99-9c0b-4ef8-bb6d-6bb9bd380a12';
        $userName = 'John Doe';
        $userLastName = 'Smith';
        $userGender = 'male';
        $userPhoneNumber = '1234567890';
        $userEmail = 'john.doe@example.com';
        $userRoles = ['ROLE_USER', 'ROLE_ADMIN'];
        $userIsActive = true;
        $userCreatedAt = new DateTimeImmutable('2023-01-01 10:00:00');
        $userUpdatedAt = new DateTime('2023-01-02 11:00:00');

        $residentUnitId = 'a1b2c3d4-e5f6-7a8b-9c0d-1e2f3a4b5c6d';
        $residentUnitUnit = 'AP-101';
        $residentUnitIdealFraction = 0.15;
        $residentUnitIsActive = true;
        $residentUnitCreatedAt = new DateTimeImmutable('2023-01-01 09:00:00');
        $residentUnitUpdatedAt = new DateTime('2023-01-02 10:00:00');
        $residentUnitRecipients = [['name' => 'Recipient 1', 'email' => 'rec1@example.com']];

        // Mock User entity
        $user = $this->createMock(User::class);
        $user->method('id')->willReturn($userId);
        $user->method('name')->willReturn($userName);
        $user->method('lastName')->willReturn($userLastName);
        $user->method('gender')->willReturn($userGender);
        $user->method('phoneNumber')->willReturn($userPhoneNumber);
        $user->method('getEmail')->willReturn($userEmail);
        $user->method('getRoles')->willReturn($userRoles);
        $user->method('isActive')->willReturn($userIsActive);
        $user->method('createdAt')->willReturn($userCreatedAt);
        $user->method('updatedAt')->willReturn($userUpdatedAt);

        // Mock ResidentUnit entity
        $residentUnit = $this->createMock(ResidentUnit::class);
        $residentUnit->method('id')->willReturn($residentUnitId);
        $residentUnit->method('unit')->willReturn($residentUnitUnit);
        $residentUnit->method('idealFraction')->willReturn($residentUnitIdealFraction);
        $residentUnit->method('isActive')->willReturn($residentUnitIsActive);
        $residentUnit->method('createdAt')->willReturn($residentUnitCreatedAt);
        $residentUnit->method('updatedAt')->willReturn($residentUnitUpdatedAt);
        $residentUnit->method('notificationRecipients')->willReturn($residentUnitRecipients);

        $user->method('getResidentUnit')->willReturn($residentUnit);

        // Configure mock serializer to return a specific array for ResidentUnit
        $this->mockSerializer->expects(self::once())
            ->method('normalize')
            ->with(self::equalTo($residentUnit), self::anything(), self::anything())
            ->willReturn([
                'id' => $residentUnitId,
                'unit' => $residentUnitUnit,
                'idealFraction' => $residentUnitIdealFraction,
                'isActive' => $residentUnitIsActive,
                'createdAt' => $residentUnitCreatedAt->format('Y-m-d H:i:s'),
                'updatedAt' => $residentUnitUpdatedAt->format('Y-m-d H:i:s'),
                'notificationRecipients' => $residentUnitRecipients,
            ]);

        $expected = [
            'id' => $userId,
            'name' => $userName,
            'lastName' => $userLastName,
            'gender' => $userGender,
            'phoneNumber' => $userPhoneNumber,
            'email' => $userEmail,
            'roles' => $userRoles,
            'isActive' => $userIsActive,
            'createdAt' => $userCreatedAt->format('Y-m-d H:i:s'),
            'updatedAt' => $userUpdatedAt->format('Y-m-d H:i:s'),
            'residentUnit' => [
                'id' => $residentUnitId,
                'unit' => $residentUnitUnit,
                'idealFraction' => $residentUnitIdealFraction,
                'isActive' => $residentUnitIsActive,
                'createdAt' => $residentUnitCreatedAt->format('Y-m-d H:i:s'),
                'updatedAt' => $residentUnitUpdatedAt->format('Y-m-d H:i:s'),
                'notificationRecipients' => $residentUnitRecipients,
            ],
            'avatar' => null,
        ];

        $this->assertEquals($expected, $this->normalizer->normalize($user));
    }

    public function testNormalizeUserWithoutResidentUnit(): void
    {
        $userId = 'b0eebc99-9c0b-4ef8-bb6d-6bb9bd380a12';
        $userName = 'Jane Doe';
        $userEmail = 'jane.doe@example.com';
        $userCreatedAt = new DateTimeImmutable('2023-01-01 10:00:00');

        // Mock User entity without ResidentUnit
        $user = $this->createMock(User::class);
        $user->method('id')->willReturn($userId);
        $user->method('name')->willReturn($userName);
        $user->method('lastName')->willReturn(null);
        $user->method('gender')->willReturn(null);
        $user->method('phoneNumber')->willReturn(null);
        $user->method('getEmail')->willReturn($userEmail);
        $user->method('getRoles')->willReturn(['ROLE_USER']);
        $user->method('isActive')->willReturn(true);
        $user->method('createdAt')->willReturn($userCreatedAt);
        $user->method('updatedAt')->willReturn(null);
        $user->method('getResidentUnit')->willReturn(null);

        // Ensure mock serializer is NOT called for residentUnit
        $this->mockSerializer->expects(self::never())
            ->method('normalize');

        $expected = [
            'id' => $userId,
            'name' => $userName,
            'lastName' => null,
            'gender' => null,
            'phoneNumber' => null,
            'email' => $userEmail,
            'roles' => ['ROLE_USER'],
            'isActive' => true,
            'createdAt' => $userCreatedAt->format('Y-m-d H:i:s'),
            'updatedAt' => null,
            'residentUnit' => null,
            'avatar' => null,
        ];

        $this->assertEquals($expected, $this->normalizer->normalize($user));
    }

    public function testNormalizeUserWithNullProperties(): void
    {
        $userId = 'c1d2e3f4-a5b6-7c8d-9e0f-1a2b3c4d5e6f';
        $userName = 'Test User';
        $userEmail = 'test@example.com';
        $userCreatedAt = new DateTimeImmutable('2023-03-03 12:30:00');

        // Mock User entity with several null properties
        $user = $this->createMock(User::class);
        $user->method('id')->willReturn($userId);
        $user->method('name')->willReturn($userName);
        $user->method('lastName')->willReturn(null);
        $user->method('gender')->willReturn(null);
        $user->method('phoneNumber')->willReturn(null);
        $user->method('getEmail')->willReturn($userEmail);
        $user->method('getRoles')->willReturn(['ROLE_USER']);
        $user->method('isActive')->willReturn(false);
        $user->method('createdAt')->willReturn($userCreatedAt);
        $user->method('updatedAt')->willReturn(null);
        $user->method('getResidentUnit')->willReturn(null);

        $this->mockSerializer->expects(self::never())
            ->method('normalize');

        $expected = [
            'id' => $userId,
            'name' => $userName,
            'lastName' => null,
            'gender' => null,
            'phoneNumber' => null,
            'email' => $userEmail,
            'roles' => ['ROLE_USER'],
            'isActive' => false,
            'createdAt' => $userCreatedAt->format('Y-m-d H:i:s'),
            'updatedAt' => null,
            'residentUnit' => null,
            'avatar' => null,
        ];

        $this->assertEquals($expected, $this->normalizer->normalize($user));
    }

    /**
     * @throws Exception
     */
    public function testNormalizeUserWithMultipleRoles(): void
    {
        $userId = 'd1e2f3a4-b5c6-7d8e-9f0a-1b2c3d4e5f6a';
        $userName = 'Admin User';
        $userEmail = 'admin@example.com';
        $userRoles = ['ROLE_USER', 'ROLE_ADMIN', 'ROLE_SUPER_ADMIN'];
        $userCreatedAt = new DateTimeImmutable('2023-04-04 14:00:00');

        // Mock User entity with multiple roles
        $user = $this->createMock(User::class);
        $user->method('id')->willReturn($userId);
        $user->method('name')->willReturn($userName);
        $user->method('lastName')->willReturn(null);
        $user->method('gender')->willReturn(null);
        $user->method('phoneNumber')->willReturn(null);
        $user->method('getEmail')->willReturn($userEmail);
        $user->method('getRoles')->willReturn($userRoles);
        $user->method('isActive')->willReturn(true);
        $user->method('createdAt')->willReturn($userCreatedAt);
        $user->method('updatedAt')->willReturn(null);
        $user->method('getResidentUnit')->willReturn(null);

        $this->mockSerializer->expects(self::never())
            ->method('normalize');

        $expected = [
            'id' => $userId,
            'name' => $userName,
            'lastName' => null,
            'gender' => null,
            'phoneNumber' => null,
            'email' => $userEmail,
            'roles' => $userRoles,
            'isActive' => true,
            'createdAt' => $userCreatedAt->format('Y-m-d H:i:s'),
            'updatedAt' => null,
            'residentUnit' => null,
            'avatar' => null,
        ];

        $this->assertEquals($expected, $this->normalizer->normalize($user));
    }

    public function testNormalizeUserWithEmptyStringProperties(): void
    {
        $userId = 'e1f2g3h4-i5j6-k7l8-m9n0-o1p2q3r4s5t6';
        $userName = 'Empty Props User';
        $userEmail = 'empty@example.com';
        $userCreatedAt = new DateTimeImmutable('2023-05-05 15:00:00');

        // Mock User entity with optional properties as empty strings
        $user = $this->createMock(User::class);
        $user->method('id')->willReturn($userId);
        $user->method('name')->willReturn($userName);
        $user->method('lastName')->willReturn('');
        $user->method('gender')->willReturn('');
        $user->method('phoneNumber')->willReturn('');
        $user->method('getEmail')->willReturn($userEmail);
        $user->method('getRoles')->willReturn(['ROLE_USER']);
        $user->method('isActive')->willReturn(true);
        $user->method('createdAt')->willReturn($userCreatedAt);
        $user->method('updatedAt')->willReturn(null);
        $user->method('getResidentUnit')->willReturn(null);

        $this->mockSerializer->expects(self::never())
            ->method('normalize');

        $expected = [
            'id' => $userId,
            'name' => $userName,
            'lastName' => '',
            'gender' => '',
            'phoneNumber' => '',
            'email' => $userEmail,
            'roles' => ['ROLE_USER'],
            'isActive' => true,
            'createdAt' => $userCreatedAt->format('Y-m-d H:i:s'),
            'updatedAt' => null,
            'residentUnit' => null,
            'avatar' => null,
        ];

        $this->assertEquals($expected, $this->normalizer->normalize($user));
    }

    public function testNormalizeResidentUnitWithNullProperties(): void
    {
        $userId = 'f1g2h3i4-j5k6-l7m8-n9o0-p1q2r3s4t5u6';
        $userName = 'User With Null RU Props';
        $userEmail = 'nullru@example.com';
        $userCreatedAt = new DateTimeImmutable('2023-06-06 16:00:00');

        $residentUnitId = 'g1h2i3j4-k5l6-m7n8-o9p0-q1r2s3t4u5v6';
        $residentUnitUnit = 'AP-202';
        $residentUnitIdealFraction = 0.20;
        $residentUnitIsActive = false;
        $residentUnitCreatedAt = new DateTimeImmutable('2023-06-06 15:00:00');
        // updatedAt is null
        $residentUnitRecipients = []; // Empty array

        // Mock User entity
        $user = $this->createMock(User::class);
        $user->method('id')->willReturn($userId);
        $user->method('name')->willReturn($userName);
        $user->method('lastName')->willReturn(null);
        $user->method('gender')->willReturn(null);
        $user->method('phoneNumber')->willReturn(null);
        $user->method('getEmail')->willReturn($userEmail);
        $user->method('getRoles')->willReturn(['ROLE_USER']);
        $user->method('isActive')->willReturn(true);
        $user->method('createdAt')->willReturn($userCreatedAt);
        $user->method('updatedAt')->willReturn(null);

        // Mock ResidentUnit entity with null/empty properties
        $residentUnit = $this->createMock(ResidentUnit::class);
        $residentUnit->method('id')->willReturn($residentUnitId);
        $residentUnit->method('unit')->willReturn($residentUnitUnit);
        $residentUnit->method('idealFraction')->willReturn($residentUnitIdealFraction);
        $residentUnit->method('isActive')->willReturn($residentUnitIsActive);
        $residentUnit->method('createdAt')->willReturn($residentUnitCreatedAt);
        $residentUnit->method('updatedAt')->willReturn(null); // Null updatedAt
        $residentUnit->method('notificationRecipients')->willReturn($residentUnitRecipients); // Empty array

        $user->method('getResidentUnit')->willReturn($residentUnit);

        // Configure mock serializer to return a specific array for ResidentUnit
        $this->mockSerializer->expects(self::once())
            ->method('normalize')
            ->with(self::equalTo($residentUnit), self::anything(), self::anything())
            ->willReturn([
                'id' => $residentUnitId,
                'unit' => $residentUnitUnit,
                'idealFraction' => $residentUnitIdealFraction,
                'isActive' => $residentUnitIsActive,
                'createdAt' => $residentUnitCreatedAt->format('Y-m-d H:i:s'),
                'updatedAt' => null, // Null updatedAt
                'notificationRecipients' => $residentUnitRecipients, // Empty array
            ]);

        $expected = [
            'id' => $userId,
            'name' => $userName,
            'lastName' => null,
            'gender' => null,
            'phoneNumber' => null,
            'email' => $userEmail,
            'roles' => ['ROLE_USER'],
            'isActive' => true,
            'createdAt' => $userCreatedAt->format('Y-m-d H:i:s'),
            'updatedAt' => null,
            'residentUnit' => [
                'id' => $residentUnitId,
                'unit' => $residentUnitUnit,
                'idealFraction' => $residentUnitIdealFraction,
                'isActive' => $residentUnitIsActive,
                'createdAt' => $residentUnitCreatedAt->format('Y-m-d H:i:s'),
                'updatedAt' => null,
                'notificationRecipients' => $residentUnitRecipients,
            ],
            'avatar' => null,
        ];

        $this->assertEquals($expected, $this->normalizer->normalize($user));
    }
}
