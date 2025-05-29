<?php

namespace App\Controller;

use App\Repository\ProduitRepository;
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
        // Récupérer le contenu du panier depuis la session sinon un tableau vide
        $panier = $session->get('panier', []);

        // Récupérer les produits correspondants
        $panierData = [];
        $total = 0;
        foreach ($panier as $id => $quantity) {
            $product = $this->produitRepository->find($id);
            if (!$product) {
                continue; // Produit introuvable
            }
            $panierData[] = [
                'product' => $product,
                'quantity' => $quantity,
                'total' => $product->getPrix() * $quantity
            ];
            $total += $product->getPrix() * $quantity;
        }

        return $this->render('panier/index.html.twig', [
            'panier' => $panierData,
            'total' => $total
        ]);
    }

    #[Route('/panier/ajouter/{id}', name: 'app_panier_ajouter')]
    public function add(int $id, SessionInterface $session): Response
    {
        $product = $this->produitRepository->find($id);
        // Vérifier si le produit existe
        if (!$product) {
            throw $this->createNotFoundException('Le produit n\'existe pas.');
        }

        // Récupérer le panier actuel
        $panier = $session->get('panier', []);

        // Ajouter ou mettre à jour la quantité
        if (!empty($panier[$id])) {
            $panier[$id]++;
        } else {
            $panier[$id] = 1;
        }

        // Sauvegarder le panier mis à jour
        $session->set('panier', $panier);

        return $this->redirectToRoute('app_panier');
    }

    #[Route('/panier/supprimer/{id}', name: 'app_panier_supprimer')]
    public function remove(int $id, SessionInterface $session): Response
    {
        // Récupérer le panier actuel
        $panier = $session->get('panier', []);

        // Supprimer le produit du panier
        if (!empty($panier[$id])) {
            unset($panier[$id]);
        }

        // Sauvegarder le panier mis à jour
        $session->set('panier', $panier);

        return $this->redirectToRoute('app_panier');
    }

    #[Route('/panier/modifier/{id}/{action}', name: 'app_panier_modifier', requirements: ['action' => 'plus|moins'])]
    public function update(int $id, string $action, SessionInterface $session): Response
    {
        // Vérifier si le produit existe
        $product = $this->produitRepository->find($id);
        if (!$product) {
            throw $this->createNotFoundException('Le produit n\'existe pas.');
        }
    
        // Récupérer le panier actuel
        $panier = $session->get('panier', []);
    
        // Mettre à jour la quantité en fonction de l'action
        if ($action === 'plus') {
            // Augmenter la quantité (sans dépasser le stock)
            if (!empty($panier[$id])) {
                $panier[$id]++;
            } else {
                $panier[$id] = 1;
            }
            if ($panier[$id] > $product->getStock()) {
                $panier[$id] = $product->getStock();
                $this->addFlash('warning', 'Vous avez atteint le stock maximum pour ce produit.');
            }
        } elseif ($action === 'moins') {
            // Diminuer la quantité (ne pas descendre en dessous de 1)
            if (!empty($panier[$id])) {
                $panier[$id]--;
                if ($panier[$id] <= 0) {
                    unset($panier[$id]); // Supprimer le produit si la quantité atteint 0
                }
            }
        }
    
        // Sauvegarder le panier mis à jour
        $session->set('panier', $panier);
    
        return $this->redirectToRoute('app_panier');
    }
}
