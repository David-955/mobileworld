<?php

namespace App\Controller;

use Stripe\Stripe;
use App\Entity\Commande;
use App\Service\PdfGenerator;
use App\Repository\ProduitRepository;
use App\Repository\CommandeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Stripe\Checkout\Session as StripeSession;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\AccessDeniedException;

class CommandeController extends AbstractController
{
    private $produitRepository;
    private $entityManager;

    public function __construct(ProduitRepository $produitRepository, EntityManagerInterface $entityManager)
    {
        $this->produitRepository = $produitRepository;
        $this->entityManager = $entityManager;
    }

    #[Route('/commande', name: 'app_commande')]
    public function index(SessionInterface $session, Request $request): Response
    {
        // Récupérer le contenu du panier depuis la session
        $panier = $session->get('panier', []);
        if (empty($panier)) {
            return $this->redirectToRoute('app_boutique');
        }

        // Récupérer les produits correspondants
        $paniervalide = [];
        $total = 0;
        foreach ($panier as $id => $quantity) {
            $product = $this->produitRepository->find($id);
            if (!$product) continue;

            $paniervalide[] = [
                'product' => $product,
                'quantity' => $quantity,
                'total' => $product->getPrix() * $quantity
            ];
            $total += $product->getPrix() * $quantity;
        }

        // Vérifier si l'utilisateur est connecté
        $user = $this->getUser();
        if (!$user) {
            $this->addFlash('error', 'Veuillez vous connecter pour passer commande.');
            return $this->redirectToRoute('app_login'); // Rediriger vers la page de connexion
        }

        // Traiter la soumission du formulaire (sans AdresseType)
        if ($request->isMethod('POST')) {
            // Récupérer les données d'adresse depuis la requête
            $nom = $request->request->get('nom');
            $prenom = $request->request->get('prenom');
            $adresse = $request->request->get('adresse');
            $ville = $request->request->get('ville');
            $codePostal = $request->request->get('codePostal');
            $tel = $request->request->get('tel');

            // Valider les données d'adresse
            if (empty($nom) || empty($prenom) || empty($adresse) || empty($ville) || empty($codePostal) || empty($tel)) {
                $this->addFlash('error', 'Veuillez remplir tous les champs de l\'adresse.');
                return $this->redirectToRoute('app_commande');
            }

            // Vérifier le stock avant de créer les commandes
            foreach ($paniervalide as $item) {
                $product = $item['product'];
                $quantitepanier = $item['quantity'];
                if ($product->getStock() < $quantitepanier) {
                    $this->addFlash('error', sprintf(
                        'Le stock du produit "%s" est insuffisant. Stock disponible : %d',
                        $product->getNom(),
                        $product->getStock()
                    ));
                    return $this->redirectToRoute('app_panier'); // Rediriger vers le panier
                }
            }

            // Génération du numéro de commande aléatoire
            $aleatoire = random_int(10000, 99999);

            // Stocker temporairement les informations dans la session
            $session->set('temp_commande_numero', $aleatoire);
            $session->set('temp_commande_adresse', [
                'nom' => $nom,
                'prenom' => $prenom,
                'adresse' => $adresse,
                'ville' => $ville,
                'codePostal' => $codePostal,
                'tel' => $tel,
            ]);

            // Rediriger vers la page de paiement Stripe
            return $this->redirectToRoute('stripe_checkout');
        }

        // Afficher la page de commande sans formulaire AdresseType
        return $this->render('commande/index.html.twig', [
            'panier' => $paniervalide,
            'total' => $total,
        ]);
    }

    #[Route('/checkout', name: 'stripe_checkout')]
    public function checkout(): Response
    {
        $stripeKey = $this->getParameter('stripe_publishable_key');
        dump($stripeKey); // Vérifiez la valeur dans la barre de débogage Symfony
        return $this->render('commande/checkout.html.twig', [
            'stripe_publishable_key' => $stripeKey,
        ]);
    }

    // #[Route('/create-checkout-session', name: 'create_checkout_session', methods: ['POST'])]
    // public function createCheckoutSession(Request $request): Response
    // {
    //     // Récupère la clé secrète depuis .env.local
    //     $stripeSecretKey = 'sk_test_51RPQKbQ7QAlYQxfgucC7OxvvuT9aNmEn14krkuWtDAOwBuSgQRnKPHmfdSB7BTETtzg3gRdwEse1H6qpNLpPEqLb00pbl1u7YO';

    //     if (!$stripeSecretKey) {
    //         return new JsonResponse(['error' => 'Clé secrète Stripe manquante'], 500);
    //     }

    //     \Stripe\Stripe::setApiKey('sk_test_51RPQKbQ7QAlYQxfgucC7OxvvuT9aNmEn14krkuWtDAOwBuSgQRnKPHmfdSB7BTETtzg3gRdwEse1H6qpNLpPEqLb00pbl1u7YO');

    //     $session = $request->getSession();
    //     $panier = $session->get('panier', []);

    //     if (empty($panier)) {
    //         return new JsonResponse(['error' => 'Votre panier est vide'], 400);
    //     }

    //     $lineItems = [];
    //     foreach ($panier as $id => $quantity) {
    //         $produit = $this->produitRepository->find($id);
    //         if (!$produit) {
    //             continue;
    //         }

    //         $lineItems[] = [
    //             'price_data' => [
    //                 'currency' => 'eur',
    //                 'product_data' => [
    //                     'name' => $produit->getNom(),
    //                 ],
    //                 'unit_amount' => $produit->getPrix() * 100,
    //             ],
    //             'quantity' => $quantity,
    //         ];
    //     }

    //     if (empty($lineItems)) {
    //         return new JsonResponse(['error' => 'Aucun produit valide dans le panier'], 400);
    //     }

    //     try {
    //         $sessionStripe = \Stripe\Checkout\Session::create([
    //             'payment_method_types' => ['card'],
    //             'line_items' => $lineItems,
    //             'mode' => 'payment',
    //             'success_url' => $this->generateUrl('success_url', [], UrlGeneratorInterface::ABSOLUTE_URL),
    //             'cancel_url' => $this->generateUrl('cancel_url', [], UrlGeneratorInterface::ABSOLUTE_URL),
    //         ]);

    //         return new JsonResponse(['id' => $sessionStripe->id]);
    //     } catch (\Exception $e) {
    //         return new JsonResponse(['error' => 'Erreur lors de la création de la session Stripe : ' . $e->getMessage()], 500);
    //     }
    // }

    #[Route('/confirmation/{numero}', name: 'app_confirmation')]
    public function confirmation(string $numero): Response
    {
        // Créer la commande si elle n’existe pas encore
        $commandes = $this->createCommandeFromSession($numero);

        $total = 0;
        foreach ($commandes as $commande) {
            $total += $commande->getProduit()->getPrix() * $commande->getQuantite();
        }

        $dateCommande = $commandes[0]->getDate();

        return $this->render('commande/confirmation.html.twig', [
            'commandes' => $commandes,
            'total' => $total,
            'date' => $dateCommande,
        ]);
    }

    private function createCommandeFromSession(string $numero): array
    {
        $session = $this->container->get('request_stack')->getCurrentRequest()->getSession();
        $user = $this->getUser();
        $panier = $session->get('panier', []);
        $adresse = $session->get('temp_commande_adresse', []);

        if (empty($panier) || empty($adresse)) {
            throw new \Exception("Données manquantes.");
        }

        $dateCommande = new \DateTime();
        $commandes = [];

        foreach ($panier as $id => $quantity) {
            $produit = $this->produitRepository->find($id);
            if (!$produit) continue;

            $commande = new Commande();
            $commande->setUtilisateur($user);
            $commande->setProduit($produit);
            $commande->setDate($dateCommande);
            $commande->setStatut("En attente d'expédition");
            $commande->setQuantite($quantity);
            $commande->setNumero($numero);

            // Informations de livraison
            $commande->setNom($adresse['nom']);
            $commande->setPrenom($adresse['prenom']);
            $commande->setAdresse($adresse['adresse']);
            $commande->setVille($adresse['ville']);
            $commande->setCodePostal($adresse['codePostal']);
            $commande->setTel($adresse['tel']);

            $newStock = $produit->getStock() - $quantity;
            $produit->setStock($newStock);

            $this->entityManager->persist($produit);
            $this->entityManager->persist($commande);
            $commandes[] = $commande;
        }

        $this->entityManager->flush();
        $session->remove('panier');
        $session->remove('temp_commande_adresse');
        $session->remove('temp_commande_numero');

        return $commandes;
    }

    #[Route('/mes-commandes', name: 'app_mes_commandes')]
    public function mesCommandes(CommandeRepository $commandeRepository, Request $request, PaginatorInterface $paginator): Response
    {
        $user = $this->getUser();
        if (!$user) return $this->redirectToRoute('app_login');

        $allNumeros = $commandeRepository->findUniqueCommandeNumerosByUser($user);
        $allNumeros = array_column($allNumeros, 'numero');

        $pagination = $paginator->paginate($allNumeros, $request->query->getInt('page', 1), 5);

        $commandesGroupedByNumero = [];
        foreach ($pagination->getItems() as $numero) {
            $commandes = $commandeRepository->findCommandesByNumero($numero, $user);
            if (!empty($commandes)) {
                $commandesGroupedByNumero[$numero] = [
                    'date' => $commandes[0]->getDate(),
                    'statut' => $commandes[0]->getStatut(),
                    'produits' => $commandes,
                ];
            }
        }

        return $this->render('commande/mes_commandes.html.twig', [
            'commandesGroupedByNumero' => $commandesGroupedByNumero,
            'pagination' => $pagination,
        ]);
    }

    #[Route('/commande/pdf/{numero}', name: 'app_commande_pdf')]
    public function generatePdf(
        string $numero,
        Request $request,
        CsrfTokenManagerInterface $csrfTokenManager,
        PdfGenerator $pdfGenerator
    ): Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $token = new CsrfToken('generate_pdf', $request->query->get('_csrf_token'));
        if (!$csrfTokenManager->isTokenValid($token)) {
            throw new AccessDeniedException('Jeton CSRF invalide.');
        }

        $user = $this->getUser();
        $commandes = $this->entityManager->getRepository(Commande::class)->findBy(['numero' => $numero, 'utilisateur' => $user]);

        if (empty($commandes)) {
            throw $this->createNotFoundException('Commande non trouvée ou non autorisée.');
        }

        $pdfContent = $pdfGenerator->generateRecap($commandes);
        $filename = sprintf('recapitulatif-%s.pdf', $numero);

        return new Response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('inline; filename="%s"', $filename),
        ]);
    }

    #[Route('/commande/annuler/{numero}/{produitId}', name: 'app_commande_annuler')]
    public function annulerProduit(
        string $numero,
        int $produitId,
        Request $request,
        CsrfTokenManagerInterface $csrfTokenManager,
        EntityManagerInterface $entityManager
    ): Response {
        $token = new CsrfToken('annuler_commande', $request->query->get('_csrf_token'));
        if (!$csrfTokenManager->isTokenValid($token)) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $commande = $entityManager->getRepository(Commande::class)->findOneBy(['numero' => $numero, 'produit' => $produitId]);

        if (!$commande) {
            throw $this->createNotFoundException('Commande ou produit non trouvé.');
        }

        if ($commande->getStatut() === "Produit annulé par le client") {
            $this->addFlash('warning', 'Ce produit a déjà été annulé.');
            return $this->redirectToRoute('app_mes_commandes');
        }

        $commande->setStatut("Produit annulé par le client");

        $produit = $commande->getProduit();
        $nouveauStock = $produit->getStock() + $commande->getQuantite();
        $produit->setStock($nouveauStock);

        $entityManager->persist($commande);
        $entityManager->persist($produit);
        $entityManager->flush();

        $this->addFlash('success', 'Le produit a été annulé avec succès.');

        return $this->redirectToRoute('app_mes_commandes');
    }

    #[Route('/paiement/success', name: 'success_url')]
    public function success(): Response
    {
        return $this->render('paiement/success.html.twig', [
            'message' => 'Votre paiement a été effectué avec succès !',
        ]);
    }

    #[Route('/paiement/cancel', name: 'cancel_url')]
    public function cancel(): Response
    {
        return $this->render('paiement/cancel.html.twig', [
            'message' => 'Votre paiement a été annulé.',
        ]);
    }








    #[Route('/panier/test', name: 'test_panier')]
    public function testPanier(SessionInterface $session): Response
    {
        // Faux panier avec quelques produits (id => quantité)
        $panierTest = [
            17 => 2,  // Produit ID 17, quantité 2
            18 => 1   // Produit ID 18, quantité 1
        ];

        // Remplace le panier actuel par le panier de test
        $session->set('panier', $panierTest);

        // Redirige vers la page de commande
        return $this->redirectToRoute('app_commande');
    }

    #[Route('/stripe/test', name: 'stripe_test')]
    public function stripeTest(): Response
    {
        // Récupère la clé publique depuis les paramètres ou .env.local
        $stripePublicKey = $_ENV['STRIPE_PUBLISHABLE_KEY'] ?? '';

        if (!$stripePublicKey) {
            throw new \Exception("Clé publique Stripe manquante");
        }

        return $this->render('commande/stripe_test.html.twig', [
            'stripe_publishable_key' => $stripePublicKey,
        ]);
    }

    #[Route('/create-checkout-session', name: 'create_checkout_session', methods: ['POST'])]
    public function createCheckoutSession(Request $request): Response
    {
        \Stripe\Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);

        $session = $request->getSession();
        $panier = $session->get('panier', []);

        if (empty($panier)) {
            return new JsonResponse(['error' => 'Votre panier est vide'], 400);
        }

        $lineItems = [];
        foreach ($panier as $id => $quantity) {
            $produit = $this->produitRepository->find($id);
            if (!$produit) continue;

            $lineItems[] = [
                'price_data' => [
                    'currency' => 'eur',
                    'product_data' => ['name' => $produit->getNom()],
                    'unit_amount' => $produit->getPrix() * 100,
                ],
                'quantity' => $quantity,
            ];
        }

        if (empty($lineItems)) {
            return new JsonResponse(['error' => 'Aucun produit valide trouvé'], 400);
        }

        try {
            $aleatoire = random_int(10000, 99999);

            $successUrl = $this->generateUrl('success_url', [], UrlGeneratorInterface::ABSOLUTE_URL);
            $cancelUrl = $this->generateUrl('cancel_url', [], UrlGeneratorInterface::ABSOLUTE_URL);

            $sessionStripe = \Stripe\Checkout\Session::create([
                'payment_method_types' => ['card'],
                'line_items' => $lineItems,
                'mode' => 'payment',
                'success_url' => $successUrl,
                'cancel_url' => $cancelUrl,
            ]);

            return new JsonResponse(['id' => $sessionStripe->id]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Erreur lors de la création de la session Stripe : ' . $e->getMessage()], 500);
        }
    }
}
