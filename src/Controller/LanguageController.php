<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class LanguageController extends AbstractController
{
    /**
     * @Route("/change-language/{locale}", name="app_change_language")
     */
    #[Route("/change-language/{locale}", name: "app_change_language")]
    public function changeLanguage(string $locale, Request $request): Response
    {
        // Get supported locales from parameter
        $supportedLocales = explode(',', $this->getParameter('app.supported_locales'));
        
        // Check if the locale is supported
        if (!in_array($locale, $supportedLocales)) {
            $locale = $this->getParameter('app.locale'); // default locale
        }
        
        // Store the locale in session
        $request->getSession()->set('_locale', $locale);
        
        // Create a cookie that expires in one year
        $cookie = new Cookie(
            'app_locale',      // name
            $locale,           // value
            time() + 365*24*60*60, // expires (1 year)
            '/',               // path
            null,              // domain
            false,             // secure
            true,              // httpOnly
            false,             // raw
            'lax'              // sameSite
        );
        
        // Get the referer URL or default to homepage
        $referer = $request->headers->get('referer');
        if (!$referer) {
            $referer = $this->generateUrl('app_home', [], UrlGeneratorInterface::ABSOLUTE_URL);
        }
        
        // Create response with redirect
        $response = $this->redirect($referer);
        
        // Add cookie to response
        $response->headers->setCookie($cookie);
        
        return $response;
    }
}