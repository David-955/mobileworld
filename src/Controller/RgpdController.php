<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class RgpdController extends AbstractController
{
    #[Route('/rgpd', name: 'app_mentions')]
    public function index(): Response
    {
        return $this->render('rgpd/mentionslegales.html.twig', [
            'controller_name' => 'RgpdController',
        ]);
    }
}
