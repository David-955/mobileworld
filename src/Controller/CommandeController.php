<?php

namespace App\Controller;

use Stripe\Stripe;
use App\Entity\Commande;
use App\Form\AdresseType;
use App\Service\PdfGenerator;
use Symfony\Component\Mime\Email;
use App\Repository\ProduitRepository;
use App\Repository\CommandeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\AccessDeniedException;

class CommandeController extends AbstractController
{
    private $produitRepository;
    private $entityManager;
    private $mailer;

    public function __construct(
        ProduitRepository $produitRepository,
        EntityManagerInterface $entityManager,
        MailerInterface $mailer
    ) {
        $this->produitRepository = $produitRepository;
        $this->entityManager = $entityManager;
        $this->mailer = $mailer;
    }


    // Vérifie si l'utilisateur est connecté ET vérifié
    protected function checkVerifiedUser(): ?Response
    {
        $user = $this->getUser();
        if (!$user || !$user->isVerification()) {
            return $this->redirectToRoute('app_verification_pending');
        }
        return null;
    }

    #[Route('/commande', name: 'app_commande')]
    public function index(SessionInterface $session, Request $request): Response
    {
        // Récupérer le panier depuis la session
        $panier = $session->get('panier', []);
        if (empty($panier)) {
            return $this->redirectToRoute('app_boutique');
        }

        // Vérifier que l'utilisateur est connecté
        $user = $this->getUser();
        if (!$user) {
            $this->addFlash('warning', 'Veuillez vous connecter pour passer commande.');
            return $this->redirectToRoute('app_login');
        }

        // Bloquer si l'utilisateur n'est pas vérifié
        $redirect = $this->checkVerifiedUser();
        if ($redirect) return $redirect;

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

        // Création du formulaire d'adresse
        $commande = new Commande();
        $form = $this->createForm(AdresseType::class, $commande);

        // Gestion de la soumission du formulaire
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // Vérification du stock avant de continuer
            foreach ($paniervalide as $item) {
                $product = $item['product'];
                $quantitepanier = $item['quantity'];
                if ($product->getStock() < $quantitepanier) {
                    $this->addFlash('danger', sprintf(
                        'Le stock du produit "%s" est insuffisant. Stock disponible : %d',
                        $product->getNom(),
                        $product->getStock()
                    ));
                    return $this->redirectToRoute('app_panier');
                }
            }

            // Génère un numéro de commande temporaire
            $aleatoire = random_int(10000, 99999);

            // Sauvegarde les données dans la session
            $session->set('temp_commande_numero', $aleatoire);
            $session->set('temp_commande_adresse', [
                'nom' => $commande->getNom(),
                'prenom' => $commande->getPrenom(),
                'adresse' => $commande->getAdresse(),
                'ville' => $commande->getVille(),
                'codePostal' => $commande->getCodePostal(),
                'tel' => $commande->getTel(),
            ]);

            return $this->redirectToRoute('app_paiement_stripe');
        }

        // Afficher la vue avec le formulaire
        return $this->render('commande/index.html.twig', [
            'formAdresse' => $form,
            'panier' => $paniervalide,
            'total' => $total,
        ]);
    }

    #[Route('/create-checkout-session', name: 'create_checkout_session', methods: ['POST'])]
    public function createCheckoutSession(Request $request): Response
    {
        \Stripe\Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);
        $session = $request->getSession();
        $panier = $session->get('panier', []);
        if (empty($panier)) {
            return new Response(json_encode(['error' => 'Votre panier est vide']), 400, ['Content-Type' => 'application/json']);
        }

        $lineItems = [];
        foreach ($panier as $id => $quantity) {
            $produit = $this->produitRepository->find($id);
            if (!$produit) continue;
            $lineItems[] = [
                'price_data' => [
                    'currency' => 'eur',
                    'product_data' => ['name' => $produit->getNom()],
                    'unit_amount' => (int) ($produit->getPrix() * 100),
                ],
                'quantity' => $quantity,
            ];
        }

        if (empty($lineItems)) {
            return new Response(json_encode(['error' => 'Aucun produit valide trouvé']), 400, ['Content-Type' => 'application/json']);
        }

        try {
            $aleatoire = $session->get('temp_commande_numero', random_int(10000, 99999));
            $successUrl = $this->generateUrl('app_confirmation', ['numero' => $aleatoire], UrlGeneratorInterface::ABSOLUTE_URL);
            $cancelUrl = $this->generateUrl('app_cancel', [], UrlGeneratorInterface::ABSOLUTE_URL);

            // Récupérer l'utilisateur connecté
            $user = $this->getUser();

            $sessionStripe = \Stripe\Checkout\Session::create([
                'customer_email' => $user->getEmail(), // Email de l'utilisateur donné à Stripe
                'payment_method_types' => ['card'],
                'line_items' => $lineItems,
                'mode' => 'payment',
                'success_url' => $successUrl,
                'cancel_url' => $cancelUrl,
            ]);

            return new Response(json_encode(['id' => $sessionStripe->id]), 200, ['Content-Type' => 'application/json']);
        } catch (\Exception $e) {
            return new Response(json_encode(['error' => 'Erreur lors de la création de la session Stripe : ' . $e->getMessage()]), 500, ['Content-Type' => 'application/json']);
        }
    }

    #[Route('/paiement/stripe', name: 'app_paiement_stripe')]
    public function paiementStripe(SessionInterface $session): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        // Vérifie que les informations d'adresse sont présentes en session
        $adresse = $session->get('temp_commande_adresse');
        if (empty($adresse) || empty($adresse['nom']) || empty($adresse['prenom']) || empty($adresse['adresse']) || empty($adresse['ville']) || empty($adresse['codePostal']) || empty($adresse['tel'])) {
            $this->addFlash('danger', 'Veuillez remplir correctement le formulaire d\'adresse avant de procéder au paiement.');
            return $this->redirectToRoute('app_commande');
        }

        return $this->render('commande/paiement_stripe.html.twig', [
            'stripe_key' => $_ENV['STRIPE_PUBLISHABLE_KEY']
        ]);
    }

    #[Route('/mes-commandes', name: 'app_mes_commandes')]
    public function mesCommandes(CommandeRepository $commandeRepository, Request $request, PaginatorInterface $paginator): Response
    {
        // Bloquer si l'utilisateur n'est pas vérifié
        $redirect = $this->checkVerifiedUser();
        if ($redirect) return $redirect;

        $user = $this->getUser();
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

    #[Route('/annuler-commande', name: 'app_cancel')]
    public function cancel(SessionInterface $session): Response
    {
        // Vérifier si le panier est déjà vide
        if (empty($session->get('panier'))) {
            $this->addFlash('info', 'Votre panier est déjà vide.');
            return $this->redirectToRoute('app_boutique');
        }

        // Supprimer les données temporaires de la session
        $session->remove('panier');
        $session->remove('temp_commande_adresse');
        $session->remove('temp_commande_numero');

        // Ajouter un message flash pour informer l'utilisateur
        $this->addFlash('info', 'Le panier est désormais vide.');

        // Rediriger vers la page de la boutique ou une autre page
        return $this->redirectToRoute('app_boutique');
    }

    #[Route('/confirmation/{numero}', name: 'app_confirmation')]
    public function confirmation(string $numero): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        // Créer les commandes à partir de la session
        $commandes = $this->createCommandeFromSession($numero);

        $total = 0;
        foreach ($commandes as $commande) {
            $total += $commande->getProduit()->getPrix() * $commande->getQuantite();
        }

        $dateCommande = $commandes[0]->getDate();

        // Récupérer l'utilisateur connecté
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException('Utilisateur non connecté');
        }

        // Préparer les données pour l'e-mail
        $paniervalide = [];
        foreach ($commandes as $commande) {
            $produit = $commande->getProduit();
            $paniervalide[] = [
                'nom' => $produit->getNom(),
                'quantite' => $commande->getQuantite(),
                'prixUnitaire' => $produit->getPrix(),
                'total' => $produit->getPrix() * $commande->getQuantite()
            ];
        }

        // Récupère l'adresse depuis la première commande
        $adresseLivraison = $commandes[0]->getAdresse();
        $ville = $commandes[0]->getVille();
        $codePostal = $commandes[0]->getCodePostal();
        $tel = $commandes[0]->getTel();

        // Générer le lien vers "Mes commandes"
        $mesCommandesUrl = $this->generateUrl(
            'app_mes_commandes',
            [],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        // Envoyer un e-mail de confirmation
        $email = (new Email())
            ->from('dngo3819@example.com')
            ->to($user->getEmail())
            ->subject('Mobile World : Confirmation de votre commande')
            ->html($this->renderView('commande/email.html.twig', [
                'user' => $user,
                'numero' => $numero,
                'panier' => $paniervalide,
                'total' => $total,
                'date' => $dateCommande,
                'adresse' => $adresseLivraison,
                'ville' => $ville,
                'code_postal' => $codePostal,
                'tel' => $tel,
                'mesCommandesUrl' => $mesCommandesUrl, // ✅ passer l'URL dans le contexte Twig
            ]));

        $this->mailer->send($email);

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

        $dateCommande = new \DateTime('now', new \DateTimeZone('Europe/Paris'));
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

        // Supprimer les données temporaires
        $session->remove('panier');
        $session->remove('temp_commande_adresse');
        $session->remove('temp_commande_numero');

        return $commandes;
    }
}
