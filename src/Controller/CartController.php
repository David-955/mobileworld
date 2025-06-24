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
        if (!$product) {
            throw $this->createNotFoundException('Le produit n\'existe pas.');
        }

        $cart = $session->get('cart', []);

        // Ajouter ou mettre à jour la quantité
        if (!empty($cart[$id])) {
            $cart[$id]++;
        } else {
            $cart[$id] = 1;
        }

        $session->set('cart', $cart);

        return $this->redirectToRoute('app_cart');
    }

    #[Route('/cart/remove/{id}', name: 'app_cart_remove')]
    public function remove(int $id, SessionInterface $session): Response
    {
        $cart = $session->get('cart', []);

        if (!empty($cart[$id])) {
            unset($cart[$id]);
        }

        $session->set('cart', $cart);

        return $this->redirectToRoute('app_cart');
    }

    #[Route('/cart/update/{id}/{action}', name: 'app_cart_update', requirements: ['action' => 'plus|moins'])]
    public function update(int $id, string $action, SessionInterface $session): Response
    {
        $product = $this->produitRepository->find($id);
        if (!$product) {
            throw $this->createNotFoundException('Le produit n\'existe pas.');
        }

        $cart = $session->get('cart', []);
    
        if ($action === 'plus') {
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
            if (!empty($cart[$id])) {
                $cart[$id]--;
                if ($cart[$id] <= 0) {
                    unset($cart[$id]); // Supprimer le produit si la quantité atteint 0
                }
            }
        }
    
        $session->set('cart', $cart);
    
        return $this->redirectToRoute('app_cart');
    }
}
