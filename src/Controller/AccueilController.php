<?php

namespace App\Controller;

use App\Service\ApiService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;

final class AccueilController extends AbstractController
{
    private $apiService;

    public function __construct(ApiService $apiService) {
        $this->apiService = $apiService;
    }

    #[Route('/', name: 'app_accueil')]
    public function index(Request $request, PaginatorInterface $paginator): Response
    {
        $url = "https://newsapi.org/v2/everything?q=smartphone&language=fr&sortBy=publishedAt&apiKey=2e45d3d4f2b9445f84b7919840c8d42c";
        $data = $this->apiService->fetchData($url);

        $pagination = $paginator->paginate(
            $data['articles'], // les données à paginer
            $request->query->getInt('page', 1), // numéro de la page actuelle
            10 // nombre d'articles par page
        );

        return $this->render('accueil/index.html.twig', [
            'controller_name' => 'AccueilController',
            'pagination' => $pagination,
        ]);
    }

    // préfixe /article pour les routes dynamiques sinon erreur avec la route /boutique 
    #[Route('/article/{title}', name: 'app_article')]
    public function article($title): Response
    {
        $title = urldecode($title);
        $data = $this->apiService->fetchData("https://newsapi.org/v2/everything?q=smartphone&language=fr&sortBy=publishedAt&apiKey=2e45d3d4f2b9445f84b7919840c8d42c");
        $article = null;
        // trouver l'article avec le titre correspondant
        foreach ($data['articles'] as $item) {
            if ($item['title'] === $title) {
                $article = $item;
                break;
            }
        }
        return $this->render('accueil/article.html.twig', [
            'controller_name' => 'AccueilController',
            'article' => $article,
            'title' => $title,
        ]);
    }


}

