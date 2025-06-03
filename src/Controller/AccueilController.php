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
use andreskrey\Readability\Readability;
use andreskrey\Readability\Configuration;

class AccueilController extends AbstractController
{
    private $apiService;

    public function __construct(ApiService $apiService)
    {
        $this->apiService = $apiService;
    }

    #[Route('/', name: 'app_accueil')]
    public function index(Request $request, PaginatorInterface $paginator): Response
    {
        $url = "https://newsapi.org/v2/everything?q=smartphone&language=fr&sortBy=publishedAt&apiKey=2e45d3d4f2b9445f84b7919840c8d42c";
        $data = $this->apiService->fetchData($url);

        // Filtrer les articles 
        $filteredArticles = array_filter($data['articles'], function ($article) {
            $keywords = ['smartphone', 'mobile', 'Android', 'iOS', 'Samsung', 'iPhone', 'Xiaomi', 'Huawei', 'honor', 'OnePlus', 'Oppo', 'Realme', 'vivo', 'Sony', 'Asus', 'Google', 'Pixel', 'Nokia', 'Motorola', 'LG', 'BlackBerry', 'Fairphone', 'ZTE', 'Lenovo'];
            $excludedKeywords = ['jeu', 'game', 'jeu vidéo', 'sport', 'politique', 'électrique', 'voiture', 'automobile', 'soldes', 'promotion', 'réduction', 'sponso', 'sponsorisé', 'pub', 'publicité', 'offres', 'offre', 'bon plan', 'code promo', 'coupon', 'remise', 'cadeau', 'gratuit', 'gratuite', 'gratuitement', 'geek', 'fou', 'explose'];

            // Vérifier les mots-clés pertinents dans le titre et la description de l'article 
            $pertinant = false;
            foreach ($keywords as $keyword) {
                if (stripos($article['title'], $keyword) !== false || stripos($article['description'], $keyword) !== false) {
                    $pertinant = true;
                    break;
                }
            }

            // Vérifier les mots-clés non pertinents
            foreach ($excludedKeywords as $keyword) {
                if (stripos($article['title'], $keyword) !== false || stripos($article['description'], $keyword) !== false) {
                    return false;
                }
            }

            return $pertinant;
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
    public function article($title, Request $request, ManagerRegistry $doctrine, PaginatorInterface $paginator): Response
    {
        $title = urldecode($title);
        $data = $this->apiService->fetchData("https://newsapi.org/v2/everything?q=smartphone&language=fr&sortBy=publishedAt&apiKey=2e45d3d4f2b9445f84b7919840c8d42c");
        $article = null;

        foreach ($data['articles'] as $item) {
            if ($item['title'] === $title) {
                $article = $item;
                break;
            }
        }

        if (!$article) {
            throw $this->createNotFoundException('Article non trouvé');
        }

        $articleUrl = $article['url'];
        $htmlContent = '';
        $images = [];

        try {
            // Récupérer le contenu de la page
            $html = file_get_contents($articleUrl);

            // Configurer Readability
            $config = new Configuration();
            $config->setFixRelativeURLs(true);
            $config->setOriginalURL($articleUrl);

            $readability = new Readability($config);

            // Passer directement le HTML (string)
            $readability->parse($html);

            // Récupérer le contenu nettoyé
            $htmlContent = $readability->getContent();

            // Extraire les images principales
            preg_match_all('/<img[^>]+src="([^">]+)"/', $htmlContent, $matches);
            $images = $matches[1] ?? [];
        } catch (\Exception $e) {
            $htmlContent = '<p>Erreur lors de l’extraction du contenu : ' . $e->getMessage() . '</p>';
        }


        $article['htmlContent'] = $htmlContent;
        $article['images'] = $images;

        $commentaire = new Commentaire();
        $form = $this->createForm(CommentaireType::class, $commentaire);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $articleUrl = $this->generateUrl('app_article', ['title' => urlencode($title)], true);
            $commentaire->setArticle($articleUrl);
            $commentaire->setUtilisateur($this->getUser());
            $commentaire->setDate(new \DateTime('now', new \DateTimeZone('Europe/Paris')));

            $entityManager = $doctrine->getManager();
            $entityManager->persist($commentaire);
            $entityManager->flush();

            return $this->redirectToRoute('app_article', ['title' => urlencode($title)]);
        }

        $repository = $doctrine->getRepository(Commentaire::class);
        $query = $repository->createQueryBuilder('c')
            ->where('c.article = :articleUrl')
            ->setParameter('articleUrl', $this->generateUrl('app_article', ['title' => urlencode($title)], true))
            ->orderBy('c.date', 'DESC')
            ->getQuery();

        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            5
        );

        $personnalisation = $doctrine->getRepository(Personnalisation::class)->findOneBy(['Utilisateur' => $this->getUser()]);

        return $this->render('accueil/article.html.twig', [
            'controller_name' => 'AccueilController',
            'article' => $article,
            'title' => $title,
            'form' => $form,
            'pagination' => $pagination,
            'personnalisation' => $personnalisation,
        ]);
    }
}
