<?php

declare(strict_types=1);

namespace App\Tests\Context\ResidentUnit\Domain;

use App\Tests\Shared\Infrastructure\PhpUnit\UnitTestCase;

final class ResidentUnitTest extends UnitTestCase
{
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
}