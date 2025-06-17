<?php

namespace App\Controller;

use App\Entity\Commentaire;
use App\Service\ApiService;
use App\Form\CommentaireType;
use App\Entity\Personnalisation;
use andreskrey\Readability\Readability;
use andreskrey\Readability\Configuration;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class AccueilController extends AbstractController
{
    private $apiService;

    public function __construct(ApiService $apiService)
    {
        $this->apiService = $apiService;
    }

    #[Route('/', name: 'app_accueil')]
    public function index(Request $request, PaginatorInterface $paginator, CacheInterface $cache): Response
    {
        $url = "https://newsapi.org/v2/everything?q=smartphone&language=fr&sortBy=publishedAt&apiKey=2e45d3d4f2b9445f84b7919840c8d42c";

        // ⏱️ Cache des données d'API pendant 15 minutes
        $data = $cache->get('homepage_articles', function () use ($url) {
            return $this->apiService->fetchData($url);
        });

        // Filtrer les sources indésirables
        $articles = array_filter($data['articles'], function ($article) {
            $url = $article['url'];
            return (
                strpos($url, 'lesnumeriques.com') === false &&
                strpos($url, 'dhnet.be') === false &&
                strpos($url, 'linuxfr.org') === false
            );
        });

        $pagination = $paginator->paginate(
            $articles,
            $request->query->getInt('page', 1),
            10
        );

        // ⏱️ Cache HTTP client (navigateur, reverse proxy, CDN)
        $response = $this->render('accueil/index.html.twig', [
            'controller_name' => 'AccueilController',
            'pagination' => $pagination,
        ]);

        $response->setSharedMaxAge(900); // 15 minutes
        $response->setPublic();

        return $response;
    }

    #[Route('/article/{title}', name: 'app_article')]
    public function article(
        $title,
        Request $request,
        ManagerRegistry $doctrine,
        PaginatorInterface $paginator,
        CacheInterface $cache
    ): Response {
        $title = urldecode($title);

        // 1. Cache sur la version nettoyée
        $cacheKey = 'article_' . md5($title);
        $articleData = $cache->get($cacheKey, function () use ($title) {
            $data = $this->apiService->fetchData("https://newsapi.org/v2/everything?q=smartphone&language=fr&sortBy=publishedAt&apiKey=2e45d3d4f2b9445f84b7919840c8d42c");

            $article = null;
            foreach ($data['articles'] as $item) {
                if ($item['title'] === $title) {
                    $article = $item;
                    break;
                }
            }

            if (!$article) {
                throw new \Exception('Article non trouvé');
            }

            $articleUrl = $article['url'];
            $htmlContent = '';
            $images = [];

            try {
                $html = file_get_contents($articleUrl);
                $config = new Configuration();
                $config->setFixRelativeURLs(true);
                $config->setOriginalURL($articleUrl);
                $readability = new Readability($config);
                $readability->parse($html);
                $htmlContent = $readability->getContent();
                $htmlContent = preg_replace('/<img[^>]+>/i', '', $htmlContent, 1);
                preg_match_all('/<img[^>]+src="([^">]+)"/', $htmlContent, $matches);
                $images = $matches[1] ?? [];
            } catch (\Exception $e) {
                $htmlContent = '<p>Erreur lors de l’extraction du contenu : ' . $e->getMessage() . '</p>';
            }

            $article['htmlContent'] = $htmlContent;
            $article['images'] = $images;

            return $article;
        });

        // 2. Système de commentaires (pas mis en cache pour conserver l’interactivité)
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
            'article' => $articleData,
            'title' => $title,
            'form' => $form,
            'pagination' => $pagination,
            'personnalisation' => $personnalisation,
        ]);
    }
}
