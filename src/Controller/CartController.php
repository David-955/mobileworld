<?php

namespace App\Controller;

use App\Repository\ProduitRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class CartController extends AbstractController
{
    private $produitRepository;

    public function __construct(ProduitRepository $produitRepository)
    {
        $this->produitRepository = $produitRepository;
    }

    #[Route('/cart', name: 'app_cart')]
    public function index(SessionInterface $session): Response
    {
        // Récupérer le contenu du panier depuis la session sinon un tableau vide
        $cart = $session->get('cart', []);

        // Récupérer les produits correspondants
        $cartData = [];
        $total = 0;
        foreach ($cart as $id => $quantity) {
            $product = $this->produitRepository->find($id);
            if (!$product) {
                continue; // Produit introuvable
            }
            $cartData[] = [
                'product' => $product,
                'quantity' => $quantity,
                'total' => $product->getPrix() * $quantity
            ];
            $total += $product->getPrix() * $quantity;
        }

        return $this->render('cart/index.html.twig', [
            'cart' => $cartData,
            'total' => $total
        ]);
    }

    #[Route('/cart/add/{id}', name: 'app_cart_add')]
    public function add(int $id, SessionInterface $session): Response
    {
        $product = $this->produitRepository->find($id);
        // Vérifier si le produit existe
        if (!$product) {
            throw $this->createNotFoundException('Le produit n\'existe pas.');
        }

        // Récupérer le panier actuel
        $cart = $session->get('cart', []);

        // Ajouter ou mettre à jour la quantité
        if (!empty($cart[$id])) {
            $cart[$id]++;
        } else {
            $cart[$id] = 1;
        }

        // Sauvegarder le panier mis à jour
        $session->set('cart', $cart);

        return $this->redirectToRoute('app_cart');
    }

    #[Route('/cart/remove/{id}', name: 'app_cart_remove')]
    public function remove(int $id, SessionInterface $session): Response
    {
        // Récupérer le panier actuel
        $cart = $session->get('cart', []);

        // Supprimer le produit du panier
        if (!empty($cart[$id])) {
            unset($cart[$id]);
        }

        // Sauvegarder le panier mis à jour
        $session->set('cart', $cart);

        return $this->redirectToRoute('app_cart');
    }

    #[Route('/cart/update/{id}/{action}', name: 'app_cart_update', requirements: ['action' => 'plus|moins'])]
    public function update(int $id, string $action, SessionInterface $session): Response
    {
        // Vérifier si le produit existe
        $product = $this->produitRepository->find($id);
        if (!$product) {
            throw $this->createNotFoundException('Le produit n\'existe pas.');
        }

        $cart = $session->get('cart', []);
    
        // Mettre à jour la quantité en fonction de l'action
        if ($action === 'plus') {
            // Augmenter la quantité (sans dépasser le stock)
            if (!empty($cart[$id])) {
                $cart[$id]++;
            } else {
                $cart[$id] = 1;
            }
            if ($cart[$id] > $product->getStock()) {
                $cart[$id] = $product->getStock();
                $this->addFlash('warning', 'Vous avez atteint le stock maximum pour ce produit.');
                return $this->redirectToRoute('app_cart');
            }
        } elseif ($action === 'moins') {
            // Diminuer la quantité (ne pas descendre en dessous de 1)
            if (!empty($cart[$id])) {
                $cart[$id]--;
                if ($cart[$id] <= 0) {
                    unset($cart[$id]); // Supprimer le produit si la quantité atteint 0
                }
            }
        }
    
        // Sauvegarder le panier mis à jour
        $session->set('cart', $cart);
    
        return $this->redirectToRoute('app_cart');
    }
}
