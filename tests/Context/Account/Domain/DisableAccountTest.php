<?php

namespace App\Tests\Context\Account\Domain;

use PHPUnit\Framework\TestCase;
use App\Tests\Context\Account\Domain\AccountMother;

class DisableAccountTest extends TestCase
{
    public function testDisableAccount(): void
    {
        // Arrange - using AccountMother to create the test instance
        $account = AccountMother::create();
        $account->enable(); // Make sure it's active before disabling

        // Clear any events from enabling
        $account->pullDomainEvents();

        // Act
        $account->disable();

        // Assert
        $this->assertFalse($account->isActive());

        // Now we expect a domain event
        $events = $account->pullDomainEvents();
        $this->assertCount(1, $events);
        $this->assertEquals('account.disabled', $events[0]->eventName());
        $this->assertEquals($account->id(), $events[0]->aggregateId());
    }
}
