<?php

namespace App\Controller;

use App\Entity\Commande;
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
    public function index(SessionInterface $session, Request $request, MailerInterface $mailer): Response
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
            if (!$product) {
                continue; // Produit introuvable
            }
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

            // Valider les données d'adresse (vous pouvez ajouter des validations supplémentaires)
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
            $dateCommande = new \DateTime(); // Date actuelle

            // Créer les commandes pour chaque produit dans le panier
            foreach ($paniervalide as $item) {
                $product = $item['product'];
                $quantitepanier = $item['quantity'];

                // Créer la commande
                $commande = new Commande();
                $commande->setUtilisateur($user);
                $commande->setProduit($product);
                $commande->setDate($dateCommande);
                $commande->setStatut("En attente d'expédition"); // Statut initial
                $commande->setQuantite($quantitepanier);
                $commande->setNumero($aleatoire);

                // Ajouter les informations d'adresse à la commande
                $commande->setNom($nom);
                $commande->setPrenom($prenom);
                $commande->setAdresse($adresse);
                $commande->setVille($ville);
                $commande->setCodePostal($codePostal);
                $commande->setTel($tel);

                $this->entityManager->persist($commande);

                // Mettre à jour le stock du produit
                $newStock = $product->getStock() - $quantitepanier;
                $product->setStock($newStock);
                $this->entityManager->persist($product);
            }

            // Enregistrer toutes les modifications dans la base de données
            $this->entityManager->flush();

            // Effacer le panier après la commande
            $session->remove('panier');

            // Envoyer un e-mail de confirmation
            $email = (new Email())
                ->from('dngo3819@example.com')
                ->to($user->getEmail()) // Adresse e-mail de l'utilisateur
                ->subject('Mobile World : Confirmation de votre commande')
                ->html($this->renderView('commande/email.html.twig', [
                    'user' => $user,
                    'numero' => $aleatoire,
                    'panier' => $paniervalide,
                    'total' => $total,
                    'date' => $dateCommande,
                ]));
            $mailer->send($email);

            // Rediriger vers la page de confirmation en passant le numéro de commande
            return $this->redirectToRoute('app_confirmation', ['numero' => $aleatoire]);
        }

        // Afficher la page de commande sans formulaire AdresseType
        return $this->render('commande/index.html.twig', [
            'panier' => $paniervalide,
            'total' => $total,
        ]);
    }

    #[Route('/confirmation/{numero}', name: 'app_confirmation')]
    public function confirmation(string $numero): Response
    {
        // Récupérer les commandes associées au numéro de commande
        $commandes = $this->entityManager->getRepository(Commande::class)->findBy(['numero' => $numero]);

        // si quelqu'un tente d'accéder à une commande qui n'existe pas
        if (empty($commandes)) {
            return $this->redirectToRoute('app_accueil');
        }

        // Calculer le total de la commande
        $total = 0;
        foreach ($commandes as $commande) {
            $total += $commande->getProduit()->getPrix() * $commande->getQuantite();
        }

        // Récupérer la date de la première commande (elles partagent toutes la même date)
        $dateCommande = $commandes[0]->getDate(); // getDate() renvoie un objet DateTime (sera formatté ensuite dans le twig)

        // Afficher la page de confirmation
        return $this->render('commande/confirmation.html.twig', [
            'commandes' => $commandes,
            'total' => $total,
            'date' => $dateCommande,
        ]);
    }

    #[Route('/mes-commandes', name: 'app_mes_commandes')]
    public function mesCommandes(
        CommandeRepository $commandeRepository,
        Request $request,
        PaginatorInterface $paginator
    ): Response {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // Récupérer tous les numéros de commande uniques
        $allNumeros = $commandeRepository->findUniqueCommandeNumerosByUser($user);
        $allNumeros = array_column($allNumeros, 'numero'); // Extraire juste les numéros

        // Paginer les numéros de commande
        $pagination = $paginator->paginate(
            $allNumeros,
            $request->query->getInt('page', 1),
            5
        );

        // Regrouper les commandes par numéro
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
        // Authentification
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        // Validation CSRF
        $token = new CsrfToken('generate_pdf', $request->query->get('_csrf_token'));
        if (!$csrfTokenManager->isTokenValid($token)) {
            throw new AccessDeniedException('Jeton CSRF invalide.');
        }

        // Récupération des commandes associées au numéro
        $user = $this->getUser();
        $commandes = $this->entityManager
            ->getRepository(Commande::class)
            ->findBy(['numero' => $numero, 'utilisateur' => $user]);

        if (empty($commandes)) {
            throw $this->createNotFoundException('Commande non trouvée ou non autorisée.');
        }

        // Générer le PDF avec FPDI
        $pdfContent = $pdfGenerator->generateRecap($commandes);

        // Nom du fichier PDF
        $filename = sprintf('recapitulatif-%s.pdf', $numero);

        // Retourner le PDF en tant que réponse
        return new Response(
            $pdfContent,
            200,
            [
                'Content-Type' => 'application/pdf',
                // attachment au lieu de inline pour télécharger le PDF
                'Content-Disposition' => sprintf('inline; filename="%s"', $filename),
            ]
        );
    }

#[Route('/commande/annuler/{numero}/{produitId}', name: 'app_commande_annuler')]
    public function annulerProduit(
        string $numero,
        int $produitId,
        Request $request,
        CsrfTokenManagerInterface $csrfTokenManager,
        EntityManagerInterface $entityManager
    ): Response {
        // Vérifier le jeton CSRF
        $token = new CsrfToken('annuler_commande', $request->query->get('_csrf_token'));
        if (!$csrfTokenManager->isTokenValid($token)) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }
    
        // Récupérer la commande spécifique (par numéro et ID du produit)
        $commande = $entityManager->getRepository(Commande::class)->findOneBy([
            'numero' => $numero,
            'produit' => $produitId,
        ]);
    
        if (!$commande) {
            throw $this->createNotFoundException('Commande ou produit non trouvé.');
        }
    
        // Vérifier que le statut n'est pas déjà "annulé"
        if ($commande->getStatut() === "Produit annulé par le client") {
            $this->addFlash('warning', 'Ce produit a déjà été annulé.');
            return $this->redirectToRoute('app_mes_commandes');
        }
    
        // Mettre à jour le statut
        $commande->setStatut("Produit annulé par le client");
    
        // Réajuster le stock du produit
        $produit = $commande->getProduit();
        $nouveauStock = $produit->getStock() + $commande->getQuantite();
        $produit->setStock($nouveauStock);
    
        // Persister les modifications
        $entityManager->persist($commande);
        $entityManager->persist($produit);
        $entityManager->flush();
    
        // Message flash
        $this->addFlash('success', 'Le produit a été annulé avec succès.');
    
        // Redirection
        return $this->redirectToRoute('app_mes_commandes');
    }
}
   