<?php

declare(strict_types=1);

namespace App\Tests\Context\ResidentUnit\Domain;

use App\Context\ResidentUnit\Domain\ResidentUnit;
use App\Tests\Shared\Infrastructure\PhpUnit\UnitTestCase;

final class ResidentUnitTest extends UnitTestCase
{
    public function test_it_should_create_a_resident_unit(): void
    {
        $residentUnit = ResidentUnitMother::create();

        self::assertInstanceOf(ResidentUnit::class, $residentUnit);
        self::assertNotEmpty($residentUnit->id());
        self::assertNotEmpty($residentUnit->unit());
        self::assertIsFloat($residentUnit->idealFraction());
        self::assertTrue($residentUnit->isActive());
        self::assertNotNull($residentUnit->createdAt());
        self::assertNotNull($residentUnit->updatedAt());
        self::assertEmpty($residentUnit->notificationRecipients());
    }

    public function test_it_should_create_a_resident_unit_with_recipients(): void
    {
        $recipients = [
            ['name' => 'John Doe', 'email' => 'john.doe@example.com'],
            ['name' => 'Jane Doe', 'email' => 'jane.doe@example.com'],
        ];
        $residentUnit = ResidentUnitMother::createWithRecipients(notificationRecipients: $recipients);

        self::assertInstanceOf(ResidentUnit::class, $residentUnit);
        self::assertNotEmpty($residentUnit->id());
        self::assertNotEmpty($residentUnit->unit());
        self::assertIsFloat($residentUnit->idealFraction());
        self::assertTrue($residentUnit->isActive());
        self::assertNotNull($residentUnit->createdAt());
        self::assertNotNull($residentUnit->updatedAt());
        self::assertCount(2, $residentUnit->notificationRecipients());
        self::assertSame($recipients, $residentUnit->notificationRecipients());
    }

    public function test_it_should_update_the_updated_at_timestamp_when_appending_a_recipient(): void
    {
        // Arrange
        $residentUnit = ResidentUnitMother::create();
        $initialTimestamp = $residentUnit->updatedAt();
        sleep(1);

        // Act
        $residentUnit->appendRecipient('Nuevo Vecino', 'vecino@example.com');

        // Assert
        $newTimestamp = $residentUnit->updatedAt();
        self::assertGreaterThan(
            $initialTimestamp,
            $newTimestamp,
            'The updatedAt timestamp should be more recent after appending a recipient.'
        );
    }

    // NUEVO: Test para 'replaceRecipients'
    public function test_it_should_replace_all_recipients_and_update_timestamp(): void
    {
        // Arrange
        $residentUnit = ResidentUnitMother::create();
        $residentUnit->appendRecipient('Antiguo Vecino', 'antiguo@example.com'); // Estado inicial
        $initialTimestamp = $residentUnit->updatedAt();

        $newRecipients = [
            ['name' => 'Propietario', 'email' => 'prop@example.com'],
            ['name' => 'Inquilino', 'email' => 'inqui@example.com'],
        ];

        sleep(1);

        // Act
        $residentUnit->replaceRecipients($newRecipients);

        // Assert
        $finalRecipients = $residentUnit->notificationRecipients();
        $newTimestamp = $residentUnit->updatedAt();

        self::assertCount(2, $finalRecipients);
        self::assertSame('prop@example.com', $finalRecipients[0]['email']);
        self::assertGreaterThan($initialTimestamp, $newTimestamp, 'Timestamp should be updated after replacing recipients.');
    }

    public function test_it_should_add_multiple_recipients_sequentially(): void
    {
        // Arrange
        $residentUnit = ResidentUnitMother::create();

        // Act
        $residentUnit->appendRecipient('Primer Vecino', 'primero@example.com');
        $residentUnit->appendRecipient('Segundo Vecino', 'segundo@example.com');

        // Assert
        $recipients = $residentUnit->notificationRecipients();
        self::assertCount(2, $recipients);
        self::assertSame('segundo@example.com', $recipients[1]['email']);
    }

    public function test_it_should_allow_adding_recipients_with_duplicate_emails(): void
    {
        // Arrange
        $residentUnit = ResidentUnitMother::create();
        $duplicateEmail = 'repetido@example.com';

        // Act
        $residentUnit->appendRecipient('Juan', $duplicateEmail);
        $residentUnit->appendRecipient('Juan (hijo)', $duplicateEmail);

        // Assert
        $recipients = $residentUnit->notificationRecipients();
        self::assertCount(2, $recipients, 'Should allow adding two recipients with the same email.');
        self::assertSame($duplicateEmail, $recipients[0]['email']);
        self::assertSame($duplicateEmail, $recipients[1]['email']);
    }

    public function test_ideal_fraction_must_not_be_more_than_1(): void
    {
        $residentUnit = ResidentUnitMother::create();

        self::assertTrue($residentUnit->idealFractionMustNotBeMoreThan1(0.5, 0.3));
        self::assertTrue($residentUnit->idealFractionMustNotBeMoreThan1(0.5, 0.5));
        self::assertFalse($residentUnit->idealFractionMustNotBeMoreThan1(0.5, 0.6));
    }

    public function test_it_should_return_array_representation(): void
    {
        $residentUnit = ResidentUnitMother::create();
        $residentUnitArray = $residentUnit->toArray();

        self::assertIsArray($residentUnitArray);
        self::assertArrayHasKey('id', $residentUnitArray);
        self::assertArrayHasKey('unit', $residentUnitArray);
        self::assertArrayHasKey('idealFraction', $residentUnitArray);
        self::assertArrayHasKey('createdAt', $residentUnitArray);
        self::assertArrayHasKey('updatedAt', $residentUnitArray);
        self::assertArrayHasKey('notificationRecipients', $residentUnitArray);

        self::assertSame($residentUnit->id(), $residentUnitArray['id']);
        self::assertSame($residentUnit->unit(), $residentUnitArray['unit']);
        self::assertSame($residentUnit->idealFraction(), $residentUnitArray['idealFraction']);
        self::assertSame($residentUnit->createdAt()->format('Y-m-d H:i:s'), $residentUnitArray['createdAt']);
        self::assertSame($residentUnit->updatedAt() ? $residentUnit->updatedAt()->format('Y-m-d H:i:s') : null, $residentUnitArray['updatedAt']);
        self::assertSame($residentUnit->notificationRecipients(), $residentUnitArray['notificationRecipients']);
    }
}
