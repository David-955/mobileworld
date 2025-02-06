<?php

namespace App\Controller;

use App\Entity\Commande;
use App\Repository\ProduitRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Form\AdresseType; // Formulaire pour l'adresse
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface; // Importez cette interface

class CommandeController extends AbstractController
{
    private $produitRepository;
    private $entityManager; // Ajoutez cette propriété

    public function __construct(ProduitRepository $produitRepository, EntityManagerInterface $entityManager)
    {
        $this->produitRepository = $produitRepository;
        $this->entityManager = $entityManager; // Injectez le manager d'entités ici
    }

    #[Route('/commande', name: 'app_commande')]
    public function index(SessionInterface $session, Request $request): Response
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
            $this->entityManager->persist($user); // Utilisez le manager injecté
            $this->entityManager->flush();

            // Créer les commandes pour chaque produit dans le panier
            foreach ($cartWithData as $item) {
                $commande = new Commande();
                $commande->setUtilisateur($user);
                $commande->setProduit($item['product']);
                $commande->setDate(new \DateTime());
                $commande->setStatut('en_attente'); // Statut initial
                $commande->setQuantite($item['quantity']);

                $this->entityManager->persist($commande); // Utilisez le manager injecté
            }

            $this->entityManager->flush(); // Sauvegardez toutes les commandes

            // Effacer le panier après la commande
            $session->remove('cart');

            $this->addFlash('success', 'Votre commande a été passée avec succès.');
            return $this->redirectToRoute('app_accueil');
        }

        return $this->render('commande/index.html.twig', [
            'cart' => $cartWithData,
            'total' => $total,
            'form' => $form->createView(),
        ]);
    }
}