<?php

namespace App\Controller;

use App\Entity\Commentaire;
use App\Service\ApiService;
use App\Form\CommentaireType;
use App\Entity\Personnalisation;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

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
    
        // Filtrer les articles côté PHP
        $filteredArticles = array_filter($data['articles'], function ($article) {
            $keywords = ['smartphone', 'mobile', 'Android', 'iOS', 'Samsung', 'iPhone', 'Xiaomi', 'Huawei'];
            $excludedKeywords = ['jeu', 'game', 'jeu vidéo', 'sport', 'politique', 'électrique', 'voiture', 'automobile', 'soldes', 'promotion', 'réduction'];  
    
            // Vérifier les mots-clés pertinents
            $isRelevant = false;
            foreach ($keywords as $keyword) {
                if (stripos($article['title'], $keyword) !== false || stripos($article['description'], $keyword) !== false) {
                    $isRelevant = true;
                    break;
                }
            }
    
            // Vérifier les mots-clés non pertinents
            foreach ($excludedKeywords as $keyword) {
                if (stripos($article['title'], $keyword) !== false || stripos($article['description'], $keyword) !== false) {
                    return false;
                }
            }
    
            return $isRelevant;
        });
    
        // Paginer les articles filtrés
        $pagination = $paginator->paginate(
            $filteredArticles, // les données filtrées
            $request->query->getInt('page', 1), // numéro de la page actuelle
            10 // nombre d'articles par page
        );
    
        return $this->render('accueil/index.html.twig', [
            'controller_name' => 'AccueilController',
            'pagination' => $pagination,
        ]);
    }

    #[Route('/article/{title}', name: 'app_article')]
    public function article($title, Request $request, ManagerRegistry $doctrine): Response
    {
        $title = urldecode($title);
        $data = $this->apiService->fetchData("https://newsapi.org/v2/everything?q=smartphone&language=fr&sortBy=publishedAt&apiKey=2e45d3d4f2b9445f84b7919840c8d42c");
        $article = null;

        // Trouver l'article avec le titre correspondant
        foreach ($data['articles'] as $item) {
            if ($item['title'] === $title) {
                $article = $item;
                break;
            }
        }

        if (!$article) {
            throw $this->createNotFoundException('Article non trouvé');
        }

        // Appeler l'API Diffbot pour récupérer le contenu complet de l'article
        $diffbotToken = "88225fa3e6849e4d29aa997e777d670b"; 
        $diffbotUrl = "https://api.diffbot.com/v3/analyze?url=" . urlencode($article['url']) . "&token=" . $diffbotToken;
        $diffbotResponse = $this->apiService->fetchData($diffbotUrl);

        // Extraire le contenu complet de la réponse Diffbot
        $fullContent = $diffbotResponse['objects'][0]['text'] ?? "Contenu complet non disponible.";

        // Ajouter le contenu complet à l'article
        $article['fullContent'] = $fullContent;

        // Gestion des commentaires
        $commentaire = new Commentaire();
        $form = $this->createForm(CommentaireType::class, $commentaire);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $articleUrl = $this->generateUrl('app_article', ['title' => urlencode($title)], true);
            $commentaire->setArticle($articleUrl);
            $commentaire->setUtilisateur($this->getUser());
            $commentaire->setDate(new \DateTime());

            $entityManager = $doctrine->getManager();
            $entityManager->persist($commentaire);
            $entityManager->flush();

            return $this->redirectToRoute('app_article', ['title' => urlencode($title)]);
        }

        // Récupérer les commentaires associés à cet article
        $commentaires = $doctrine->getRepository(Commentaire::class)->findBy(['article' => $this->generateUrl('app_article', ['title' => urlencode($title)], true)]);

        // Récupérer la personnalisation de l'Utilisateur actuel
        $personnalisation = $doctrine->getRepository(Personnalisation::class)->findOneBy(['Utilisateur' => $this->getUser()]);

        return $this->render('accueil/article.html.twig', [
            'controller_name' => 'AccueilController',
            'article' => $article,
            'title' => $title,
            'form' => $form->createView(),
            'commentaires' => $commentaires,
            'personnalisation' => $personnalisation,
        ]);
    }
}