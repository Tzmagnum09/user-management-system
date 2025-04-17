<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TermsController extends AbstractController
{
    #[Route('/terms', name: 'app_terms')]
    public function index(): Response
    {
        return $this->render('terms/index.html.twig', [
            'last_update' => '17/04/2025',
        ]);
    }
    
    #[Route('/terms/modal', name: 'app_terms_modal')]
    public function modal(): Response
    {
        return $this->render('terms/modal.html.twig', [
            'last_update' => '17/04/2025',
        ]);
    }
}