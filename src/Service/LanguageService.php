<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class LanguageService
{
    private RequestStack $requestStack;
    private SessionInterface $session;
    private string $defaultLocale;
    private array $supportedLocales;

    public function __construct(RequestStack $requestStack, ParameterBagInterface $params)
    {
        $this->requestStack = $requestStack;
        $this->session = $requestStack->getSession();
        $this->defaultLocale = $params->get('app.locale');
        $this->supportedLocales = explode(',', $params->get('app.supported_locales'));
    }

    /**
     * Get the current locale from session, cookie or default
     */
    public function getCurrentLocale(): string
    {
        // First check session
        $locale = $this->session->get('_locale');
        if ($locale && $this->isValidLocale($locale)) {
            return $locale;
        }
        
        // Then check cookie
        $request = $this->requestStack->getCurrentRequest();
        if ($request && $request->cookies->has('app_locale')) {
            $locale = $request->cookies->get('app_locale');
            if ($this->isValidLocale($locale)) {
                return $locale;
            }
        }
        
        // Check URL parameter
        if ($request && $request->query->has('_locale')) {
            $locale = $request->query->get('_locale');
            if ($this->isValidLocale($locale)) {
                return $locale;
            }
        }
        
        // Finally, check route parameter
        if ($request && $request->attributes->has('_locale')) {
            $locale = $request->attributes->get('_locale');
            if ($this->isValidLocale($locale)) {
                return $locale;
            }
        }
        
        // Default locale
        return $this->defaultLocale;
    }

    /**
     * Set the locale in session and cookie
     */
    public function setLocale(string $locale): void
    {
        if (!$this->isValidLocale($locale)) {
            $locale = $this->defaultLocale;
        }

        // Set in session
        $this->session->set('_locale', $locale);
        
        // Set in cookie (365 days)
        $request = $this->requestStack->getCurrentRequest();
        if ($request) {
            $response = $request->attributes->get('_controller_result');
            if ($response) {
                $response->headers->setCookie(
                    new Cookie(
                        'app_locale',
                        $locale,
                        time() + 365 * 24 * 60 * 60, // 1 year
                        '/',
                        null,
                        false,
                        true,
                        false,
                        'lax'
                    )
                );
            }
        }
    }

    /**
     * Get all supported locales with their names
     */
    public function getSupportedLocales(): array
    {
        return [
            'fr' => 'Français',
            'en' => 'English',
            'nl' => 'Nederlands',
            'de' => 'Deutsch',
        ];
    }

    /**
     * Check if locale is valid
     */
    private function isValidLocale(string $locale): bool
    {
        return in_array($locale, $this->supportedLocales);
    }
}