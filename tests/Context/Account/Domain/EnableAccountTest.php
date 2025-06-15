<?php

namespace App\Tests\Context\Account\Domain;

use PHPUnit\Framework\TestCase;

class EnableAccountTest extends TestCase
{
    public function testEnableAccount(): void
    {
        // Arrange - using AccountMother to create the test instance
        $account = AccountMother::create();

        // Act
        $account->enable();

        // Assert
        $this->assertTrue($account->isActive());

        // Check that the domain event was recorded
        $events = $account->pullDomainEvents();
        $this->assertCount(1, $events);
        $this->assertEquals('account.enabled', $events[0]->eventName());
        $this->assertEquals($account->id(), $events[0]->aggregateId());
    }
}