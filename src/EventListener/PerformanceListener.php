<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

#[AsEventListener(event: KernelEvents::RESPONSE)]
class PerformanceListener
{
    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $response = $event->getResponse();
        $request = $event->getRequest();

        // Add performance headers
        if (str_starts_with($request->getPathInfo(), '/api/')) {
            // Enable HTTP caching for API responses
            $response->headers->set('Cache-Control', 'public, max-age=300, s-maxage=300');
            
            // Add performance headers
            $response->headers->set('X-Content-Type-Options', 'nosniff');
            $response->headers->set('X-Frame-Options', 'DENY');
            $response->headers->set('X-XSS-Protection', '1; mode=block');
            
            // Compression
            if (!$response->headers->has('Content-Encoding')) {
                $response->headers->set('Vary', 'Accept-Encoding');
            }
        }

        // Set ETags for cacheable responses
        if ($response->getStatusCode() === 200 && $request->isMethod('GET')) {
            $etag = md5($response->getContent());
            $response->setEtag($etag);
            $response->setPublic();
            
            if ($response->isNotModified($request)) {
                return;
            }
        }
    }
}