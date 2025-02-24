<?php

namespace App\Controller;

use App\Repository\ProduitRepository;
use App\Repository\CategorieRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class ShopController extends AbstractController
{
    public function __construct(private CategorieRepository $categorieRepository, private ProduitRepository $produitRepository) {}

    #[Route('/boutique', name: 'app_boutique')]
    public function shop(): Response
    {
        $categories = $this->categorieRepository->findAll();
        return $this->render('boutique/index.html.twig', [
            'categories' => $categories,
        ]);
    }

    #[Route('/boutique/{id}', name: 'app_categorie')]
    public function product(int $id, PaginatorInterface $paginator, Request $request): Response
    {
        $category = $this->categorieRepository->find($id);

        // si quelqu'un tape une URL avec un id qui n'existe pas
        if (!$category) {
            throw $this->createNotFoundException('Catégorie non trouvée');
        }

        $products = $this->produitRepository->findBy(['categorie' => $category]);

        // Paginer les articles filtrés
        $pagination = $paginator->paginate(
            $products, // les données filtrées
            $request->query->getInt('page', 1), // numéro de la page actuelle
            10 // nombre d'articles par page
        );

        return $this->render('boutique/produit.html.twig', [
            // Je me sert de category pour retrouver la catégorie dans produit.html.twig
            'category' => $category,
            // 'products' => $products,
            'pagination' => $pagination,
        ]);
    }  
}
