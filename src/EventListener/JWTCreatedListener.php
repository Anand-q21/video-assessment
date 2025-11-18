<?php

namespace App\EventListener;

use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTCreatedEvent;

class JWTCreatedListener
{
    public function onJWTCreated(JWTCreatedEvent $event): void
    {
        $payload = $event->getData();
        
        // Get current timestamp
        $now = new \DateTime();
        $exp = new \DateTime('+1 hour');
        
        // Set correct timestamps
        $payload['iat'] = $now->getTimestamp();
        $payload['exp'] = $exp->getTimestamp();
        
        $event->setData($payload);
    }
}