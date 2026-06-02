<?php

declare(strict_types=1);

namespace App\Tests\Context\ResidentUnit\Domain;

use App\Context\ResidentUnit\Domain\Event\ResidentUnitIdealFractionWasChanged;
use App\Context\ResidentUnit\Domain\Event\ResidentUnitRecipientsWereReplaced;
use App\Context\ResidentUnit\Domain\Event\ResidentUnitRecipientWasAppended;
use App\Context\ResidentUnit\Domain\ResidentUnit;
use App\Context\ResidentUnit\Domain\ResidentUnitIdealFraction;
use App\Tests\Shared\Infrastructure\PhpUnit\UnitTestCase;

use function sleep;

final class ResidentUnitTest extends UnitTestCase
{
    public function testItShouldCreateAResidentUnit(): void
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

    public function testItShouldCreateAResidentUnitWithRecipients(): void
    {
        $recipients = [
            ['name' => 'John Doe', 'email' => 'john.doe@example.com'],
            ['name' => 'Jane Doe', 'email' => 'jane.doe@example.com'],
        ];
        $residentUnit = ResidentUnitMother::createWithRecipients(recipients: $recipients);

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

    public function testItShouldUpdateTheUpdatedAtTimestampWhenAppendingARecipient(): void
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
            'The updatedAt timestamp should be more recent after appending a recipient.',
        );

        $events = $residentUnit->pullDomainEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(ResidentUnitRecipientWasAppended::class, $events[0]);
        self::assertSame('Nuevo Vecino', $events[0]->name);
        self::assertSame('vecino@example.com', $events[0]->email);
    }

    // NUEVO: Test para 'replaceRecipients'
    public function testItShouldReplaceAllRecipientsAndUpdateTimestamp(): void
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

        $events = $residentUnit->pullDomainEvents();
        self::assertCount(2, $events); // One for append, one for replace
        self::assertInstanceOf(ResidentUnitRecipientsWereReplaced::class, $events[1]);
        self::assertSame($newRecipients, $events[1]->recipients);
    }

    public function testItShouldAddMultipleRecipientsSequentially(): void
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

    public function testItShouldAllowAddingRecipientsWithDuplicateEmails(): void
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

    public function testIdealFractionMustNotBeMoreThan1(): void
    {
        $residentUnit = ResidentUnitMother::create();

        self::assertTrue($residentUnit->idealFractionMustNotBeMoreThan1(0.5, 0.3));
        self::assertTrue($residentUnit->idealFractionMustNotBeMoreThan1(0.5, 0.5));
        self::assertFalse($residentUnit->idealFractionMustNotBeMoreThan1(0.5, 0.6));
    }

    public function testItShouldChangeIdealFractionAndRecordEvent(): void
    {
        $residentUnit = ResidentUnitMother::create();
        $residentUnit->pullDomainEvents(); // clear previous events

        $newFraction = new ResidentUnitIdealFraction(0.75);
        $residentUnit->changeIdealFraction($newFraction);

        self::assertSame(0.75, $residentUnit->idealFraction());

        $events = $residentUnit->pullDomainEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(ResidentUnitIdealFractionWasChanged::class, $events[0]);
        self::assertSame(0.75, $events[0]->idealFraction);
    }
}
