<?php
namespace App\Controller;

use App\Entity\Commande;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use App\Repository\ProduitRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Form\AdresseType; // Formulaire pour l'adresse
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

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
        $cart = $session->get('cart', []);
        if (empty($cart)) {
            $this->addFlash('error', 'Votre panier est vide. Ajoutez des produits avant de passer commande.');
            return $this->redirectToRoute('app_boutique');
        }

        // Récupérer les produits correspondants
        $cartWithData = [];
        $total = 0;
        foreach ($cart as $id => $quantity) {
            $product = $this->produitRepository->find($id);
            if (!$product) {
                continue; // Produit introuvable
            }
            $cartWithData[] = [
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

        // Créer le formulaire pour l'adresse
        $form = $this->createForm(AdresseType::class, $user);

        // Gérer la soumission du formulaire
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // Enregistrer les informations mises à jour dans l'utilisateur
            $this->entityManager->persist($user);
            $this->entityManager->flush();

            // Génération du numéro de commande aléatoire
            $aleatoire = random_int(10000, 99999);

            $dateCommande = new \DateTime(); // Date actuelle

            // Créer les commandes pour chaque produit dans le panier
            foreach ($cartWithData as $item) {
                $commande = new Commande();
                $commande->setUtilisateur($user);
                $commande->setProduit($item['product']);
                $commande->setDate($dateCommande);
                $commande->setStatut('en_attente'); // Statut initial
                $commande->setQuantite($item['quantity']);
                $commande->setNumero($aleatoire);
                $this->entityManager->persist($commande);
            }

            $this->entityManager->flush();

            // Effacer le panier après la commande
            $session->remove('cart');
            
            // Envoyer un e-mail de confirmation
            $email = (new Email())
                ->from('dngo3819@example.com')
                ->to($user->getEmail()) // Adresse e-mail de l'utilisateur
                ->subject('Mobile World : Confirmation de votre commande')
                ->html($this->renderView('commande/email.html.twig', [
                    'user' => $user,
                    'numero' => $aleatoire,
                    'cart' => $cartWithData,
                    'total' => $total,
                    'date' => $dateCommande,
                ]));

            $mailer->send($email);
            // Rediriger vers la page de confirmation en passant le numéro de commande
            return $this->redirectToRoute('app_confirmation', ['numero' => $aleatoire]);
        }

        return $this->render('commande/index.html.twig', [
            'cart' => $cartWithData,
            'total' => $total,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/confirmation/{numero}', name: 'app_confirmation')]
    public function confirmation(string $numero): Response
    {
        // Récupérer les commandes associées au numéro de commande
        $commandes = $this->entityManager->getRepository(Commande::class)->findBy(['numero' => $numero]);

        if (empty($commandes)) {
            $this->addFlash('error', 'Aucune commande trouvée avec ce numéro.');
            return $this->redirectToRoute('app_accueil');
        }

        // Calculer le total de la commande
        $total = 0;
        foreach ($commandes as $commande) {
            $total += $commande->getProduit()->getPrix() * $commande->getQuantite();
        }
        
        // Récupérer la date de la première commande (elles partagent toutes la même date)
        $dateCommande = $commandes[0]->getDate(); // Supposons que getDate() renvoie un objet DateTime

        // Afficher la page de confirmation
        return $this->render('commande/confirmation.html.twig', [
            'commandes' => $commandes,
            'total' => $total,
            'date' => $dateCommande,
        ]);
    }
}