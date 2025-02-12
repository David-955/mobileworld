<?php

namespace App\Controller;

use App\Repository\ProduitRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class PanierController extends AbstractController
{
    private $produitRepository;

    public function __construct(ProduitRepository $produitRepository)
    {
        $this->produitRepository = $produitRepository;
    }

    #[Route('/panier', name: 'app_panier')]
    public function index(SessionInterface $session): Response
    {
        // Récupérer le contenu du panier depuis la session
        $cart = $session->get('cart', []);

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

        return $this->render('panier/index.html.twig', [
            'cart' => $cartWithData,
            'total' => $total
        ]);
    }

    #[Route('/panier/ajouter/{id}', name: 'app_panier_ajouter')]
    public function add(int $id, SessionInterface $session): Response
    {
        // Vérifier si le produit existe
        $product = $this->produitRepository->find($id);
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

        return $this->redirectToRoute('app_panier');
    }

    #[Route('/panier/supprimer/{id}', name: 'app_panier_supprimer')]
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

        return $this->redirectToRoute('app_panier');
    }

    #[Route('/panier/modifier/{id}', name: 'app_panier_modifier')]
    public function update(int $id, Request $request, SessionInterface $session): Response
    {
        // Récupérer la quantité depuis les données POST
        $quantity = (int) $request->request->get('quantity', 0);

        // Vérifier si le produit existe
        $product = $this->produitRepository->find($id);
        if (!$product) {
            throw $this->createNotFoundException('Le produit n\'existe pas.');
        }

        // Vérifier si la quantité demandée est valide
        if ($quantity <= 0) {
            return $this->redirectToRoute('app_panier_supprimer', ['id' => $id]);
        }

        // Vérifier si la quantité demandée dépasse le stock disponible
        if ($quantity > $product->getStock()) {
            $this->addFlash('error', 'La quantité demandée dépasse le stock disponible.');
            return $this->redirectToRoute('app_panier');
        }

        // Récupérer le panier actuel
        $cart = $session->get('cart', []);

        // Mettre à jour la quantité
        $cart[$id] = $quantity;

        // Sauvegarder le panier mis à jour
        $session->set('cart', $cart);

        $this->addFlash('success', 'La quantité a été mise à jour.');
        return $this->redirectToRoute('app_panier');
    }
}
