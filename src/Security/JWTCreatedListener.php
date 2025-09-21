<?php

namespace App\Security;

use App\Context\User\Domain\User;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTCreatedEvent;
use Symfony\Component\Security\Core\User\UserInterface;

class JWTCreatedListener
{
    public function onJWTCreated(JWTCreatedEvent $event): void
    {
        /** @var User $user */
        $user = $event->getUser();
        $payload = $event->getData();

        if (!$user instanceof UserInterface) {
            return;
        }

        if ($user instanceof User) {
            $payload['id'] = $user->id();
            $payload['user'] = $user->getUserIdentifier();
            $payload['name'] = $user->getName();
            $payload['unit'] = $user->getResidentUnit()?->id();
            $payload['roles'] = $user->getRoles()[0];
        }

        $event->setData($payload);
    }
}