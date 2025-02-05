<?php

namespace App\Controller\Admin;

use App\Entity\Produit;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class ProduitCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Produit::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            // configuration champs nom, description, prix, image et categorie
            TextField::new('nom', 'Nom du produit'),
            TextField::new('description', 'Description du produit'),
            TextField::new('prix', 'Prix du produit'),
            // Upload d'une image
            ImageField::new('image', 'Image')
                ->setUploadDir('public/images/produits')
                ->setBasePath('uploads/images')
                ->setRequired(false),
                // Ajout champs pour la clé étrangère categorie_id
            AssociationField::new('categorie', 'Catégorie du produit')
                ->setCrudController(CategorieCrudController::class),
            IntegerField::new('stock', 'Stock du produit'),
        ];
    }
}
