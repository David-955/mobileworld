<?php

namespace App\Controller;

use App\Repository\CategoriesBoutiqueRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class ShopController extends AbstractController
{
    public function __construct(private CategoriesBoutiqueRepository $categoriesBoutiqueRepository) {}

    #[Route('/boutique', name: 'app_boutique')]
    public function shop(): Response
    {
        $categories = $this->categoriesBoutiqueRepository->findAll();
        return $this->render('boutique/index.html.twig', [
            'categories_boutique' => $categories,
        ]);
    }
}

