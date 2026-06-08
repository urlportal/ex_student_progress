<?php

namespace App\Infrastructure\EventListener;

use App\Domain\Entity\User;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTCreatedEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: 'lexik_jwt_authentication.on_jwt_created')]
final class JwtCreatedListener
{
    public function __invoke(JWTCreatedEvent $event): void
    {
        $user = $event->getUser();

        if (!$user instanceof User) {
            return;
        }

        $data = $event->getData();
        $data['id'] = (string) $user->getId();
        $event->setData($data);
    }
}
