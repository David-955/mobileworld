<?php

namespace App\Controller\Admin;

use App\Entity\Produit;
use App\Entity\Categorie;
use App\Entity\Personnalisation;
use App\Entity\Utilisateur;
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
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');
        // Lien vers le site
        yield MenuItem::linktoRoute('Back to the website', 'fas fa-home', 'app_accueil');
        // Relier les CRUDs
        yield MenuItem::linkToCrud('Catégories de la boutique', 'fas fa-list', Categorie::class);
        yield MenuItem::linkToCrud('Produits de la boutique', 'fas fa-list', Produit::class);
        yield MenuItem::linkToCrud('Utilisateurs', 'fas fa-list', Utilisateur::class);
        yield MenuItem::linkToCrud('Personnalisations', 'fas fa-list', Personnalisation::class);
    }
}

