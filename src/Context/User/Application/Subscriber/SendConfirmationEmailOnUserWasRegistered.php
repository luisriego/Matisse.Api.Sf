<?php

declare(strict_types=1);

namespace App\Context\User\Application\Subscriber;

use App\Context\User\Application\Service\UserMailerInterface;
use App\Context\User\Domain\Event\UserWasRegistered;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\EventSubscriber;

final readonly class SendConfirmationEmailOnUserWasRegistered implements EventSubscriber
{
    public function __construct(private UserMailerInterface $userMailer) {}

    /** @param UserWasRegistered $event */
    public function __invoke(DomainEvent $event): void
    {
        $this->userMailer->sendConfirmationEmail(
            $event->email(),
            $event->name(),
            $event->aggregateId(),
            $event->confirmationToken(),
        );
    }

    public static function subscribedTo(): array
    {
        return [UserWasRegistered::class => '__invoke'];
    }
}
