<?php

namespace App\Infrastructure\EventListener;

use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Token\Parser;
use Lcobucci\JWT\UnencryptedToken;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: 'lexik_jwt_authentication.on_authentication_success')]
final class JwtAuthenticationSuccessListener
{
    public function __invoke(AuthenticationSuccessEvent $event): void
    {
        $data = $event->getData();

        if (!isset($data['token'])) {
            return;
        }

        $parsed = (new Parser(new JoseEncoder()))->parse($data['token']);

        if (!$parsed instanceof UnencryptedToken) {
            return;
        }

        $exp = $parsed->claims()->get('exp');

        if ($exp instanceof \DateTimeInterface) {
            $data['expires_at'] = $exp->getTimestamp();
            $event->setData($data);
        }
    }
}
