<?php

namespace App\Controller\Admin;

use App\Entity\Produit;
use App\Entity\Categorie;
use App\Entity\Commentaire;
use App\Entity\Utilisateur;
use App\Entity\Personnalisation;
use App\Entity\Commande;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;

class DashboardController extends AbstractDashboardController
{
    #[Route('/admin', name: 'admin')]
    public function index(): Response
    {
        // on modifie le chemin de la page d'accueil de l'admin
        return $this->render('admin/dashboard.html.twig');
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Mobile World - Administration');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Tableau de bord', 'fa fa-home');
        // Lien vers le site et stripe
        yield MenuItem::linktoRoute('Retour vers le site', 'fas fa-home', 'app_home');
        yield MenuItem::linkToUrl('Stripe (Test)', 'fab fa-stripe', 'https://dashboard.stripe.com/test/dashboard')->setLinkTarget('_blank');
        // Relier les CRUDs
        yield MenuItem::linkToCrud('Catégories de la boutique', 'fas fa-list', Categorie::class);
        yield MenuItem::linkToCrud('Produits de la boutique', 'fas fa-list', Produit::class);
        yield MenuItem::linkToCrud('Utilisateurs', 'fas fa-list', Utilisateur::class);
        yield MenuItem::linkToCrud('Personnalisations', 'fas fa-list', Personnalisation::class);
        yield MenuItem::linkToCrud('Commentaires', 'fas fa-list', Commentaire::class);
        yield MenuItem::linkToCrud('Commandes', 'fas fa-list', Commande::class);
    }
}

