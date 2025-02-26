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
    
        // Si la catégorie n'existe pas
        if (!$category) {
            throw $this->createNotFoundException('Catégorie non trouvée');
        }
    
        $search = $request->query->get('search', '');
        $sort = $request->query->get('sort', 'price_asc'); // Valeur par défaut : tri croissant
    
        $queryBuilder = $this->produitRepository->createQueryBuilder('p')
            ->where('p.categorie = :category')
            ->setParameter('category', $category);
    
        if ($search) {
            $queryBuilder->andWhere('p.nom LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }
    
        // Définir le tri en fonction du paramètre 'sort'
        if ($sort === 'price_asc') {
            $queryBuilder->orderBy('p.prix', 'ASC'); // Tri croissant sur le champ 'prix'
        } elseif ($sort === 'price_desc') {
            $queryBuilder->orderBy('p.prix', 'DESC'); // Tri décroissant sur le champ 'prix'
        } else {
            // Par défaut, trier par prix croissant si la valeur est invalide
            $queryBuilder->orderBy('p.prix', 'ASC');
        }
    
        $productsQuery = $queryBuilder->getQuery();
    
        // Paginer les articles filtrés
        $pagination = $paginator->paginate(
            $productsQuery, // Les données filtrées
            $request->query->getInt('page', 1), // Numéro de la page actuelle
            10 // Nombre d'articles par page
        );
    
        return $this->render('boutique/produit.html.twig', [
            'category' => $category,
            'pagination' => $pagination,
            'search' => $search,
            'sort' => $sort, // Passer le paramètre de tri à la vue
        ]);
    }
}
