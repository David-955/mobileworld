<?php

namespace App\Controller;

use App\Service\ApiService;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Entity\Commentaire;
use App\Form\CommentaireType;
use Doctrine\Persistence\ManagerRegistry;

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
    public function article($title, Request $request, ManagerRegistry $doctrine): Response
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

        if (!$article) {
            throw $this->createNotFoundException('Article non trouvé');
        }

        $commentaire = new Commentaire();
        $form = $this->createForm(CommentaireType::class, $commentaire);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Générer l'URL de la page actuelle
            $articleUrl = $this->generateUrl('app_article', ['title' => urlencode($title)], true);
            // Enregistrer l'URL de la page actuelle pour retrouver les commentaires associés
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

        return $this->render('accueil/article.html.twig', [
            'controller_name' => 'AccueilController',
            'article' => $article,
            'title' => $title,
            'form' => $form->createView(),
            'commentaires' => $commentaires,
        ]);
    }

}

