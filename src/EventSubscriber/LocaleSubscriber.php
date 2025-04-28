<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class LocaleSubscriber implements EventSubscriberInterface
{
    private $defaultLocale;
    private $supportedLocales;

    public function __construct(ParameterBagInterface $params)
    {
        $this->defaultLocale = $params->get('app.locale');
        $this->supportedLocales = explode(',', $params->get('app.supported_locales'));
    }

    public function onKernelRequest(RequestEvent $event)
    {
        $request = $event->getRequest();
        
        if (!$event->isMainRequest()) {
            return;
        }

        // Try to get locale from query parameter or route attribute
        $locale = $request->query->get('_locale') ?? $request->attributes->get('_locale');
        
        // Try to get locale from session
        if (!$locale) {
            $session = $request->getSession();
            $locale = $session->get('_locale');
        }
        
        // Try to get locale from cookies
        if (!$locale) {
            $locale = $request->cookies->get('app_locale');
        }
        
        // Set locale if it's supported
        if ($locale && in_array($locale, $this->supportedLocales)) {
            $request->setLocale($locale);
            
            // Save locale to session for future requests
            if ($request->hasSession()) {
                $request->getSession()->set('_locale', $locale);
            }
        } else {
            // Set default locale if none was found or if not supported
            $request->setLocale($this->defaultLocale);
        }
    }

    public static function getSubscribedEvents()
    {
        return [
            // Must be registered before the default Locale listener
            KernelEvents::REQUEST => [['onKernelRequest', 20]],
        ];
    }
}