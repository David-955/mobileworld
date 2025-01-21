<?php

namespace App\Controller;

use App\Repository\ProduitRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\CategoriesBoutiqueRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class ShopController extends AbstractController
{
    public function __construct(private CategoriesBoutiqueRepository $categoriesBoutiqueRepository, private ProduitRepository $produitRepository) {}

    #[Route('/boutique', name: 'app_boutique')]
    public function shop(): Response
    {
        $categories = $this->categoriesBoutiqueRepository->findAll();
        return $this->render('boutique/index.html.twig', [
            'categories_boutique' => $categories,
        ]);
    }

    #[Route('/boutique/{alias}', name: 'app_produit')]
    public function product(): Response
    {
        $products = $this->produitRepository->findAll();
        return $this->render('boutique/produit.html.twig', [
            'products' => $products,
        ]);
    }
}

