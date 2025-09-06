<?php

namespace App\Tests\Context\Account\Infrastructure\Http\Controller;

use App\Context\Account\Application\UseCase\FindAccount\FindAccountQuery;
use App\Context\Account\Domain\Exception\AccountNotFoundException;
use App\Context\Account\Infrastructure\Http\Controller\GetAccountController;
use App\Tests\Context\Account\Domain\AccountMother;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;

class GetAccountControllerTest extends TestCase
{
    private MessageBusInterface|MockObject $queryBus;
    private GetAccountController $controller;

    protected function setUp(): void
    {
        $this->queryBus = $this->createMock(MessageBusInterface::class);
        $this->controller = new GetAccountController($this->queryBus);
    }

    public function testGetAccountSuccess(): void
    {
        // Arrange
        $account = AccountMother::create();
        $accountData = $account->toArray();
        $accountId = $account->id();

        // Create a real HandledStamp instead of mocking it
        $handledStamp = new HandledStamp($accountData, 'handler_name');
        $envelope = new Envelope(new \stdClass(), [$handledStamp]);

        $this->queryBus
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function (FindAccountQuery $query) use ($accountId) {
                return $query->id() === $accountId;
            }))
            ->willReturn($envelope);

        // Act
        $response = $this->controller->__invoke($accountId);

        // Assert
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertEquals($accountData, json_decode($response->getContent(), true));
    }

//    public function testGetAccountNotFound(): void
//    {
//        // Arrange
//        $accountId = 'non-existent-id';
//
//        $this->queryBus
//            ->expects($this->once())
//            ->method('dispatch')
//            ->willThrowException(new AccountNotFoundException($accountId));
//
//        // Act
//        $response = $this->controller->__invoke($accountId);
//
//        // Assert
//        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
//        $this->assertEquals(['error' => 'Account not found'], json_decode($response->getContent(), true));
//    }
//
//    public function testGetAccountThrowsException(): void
//    {
//        // Arrange
//        $accountId = 'account-123';
//        $errorMessage = 'Database connection error';
//
//        $this->queryBus
//            ->expects($this->once())
//            ->method('dispatch')
//            ->willThrowException(new \RuntimeException($errorMessage));
//
//        // Act
//        $response = $this->controller->__invoke($accountId);
//
//        // Assert
//        $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
//        $this->assertEquals(['error' => $errorMessage], json_decode($response->getContent(), true));
//    }
}