<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ApiDocsController extends AbstractController
{
    #[Route('/api/docs', name: 'api_docs', methods: ['GET'])]
    public function docs(): Response
    {
        return $this->render('api_docs.html.twig');
    }

    #[Route('/swagger', name: 'swagger_ui', methods: ['GET'])]
    public function swagger(): Response
    {
        $swaggerHtml = file_get_contents($this->getParameter('kernel.project_dir') . '/public/swagger.html');
        return new Response($swaggerHtml);
    }
}