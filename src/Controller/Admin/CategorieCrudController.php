<?php

namespace App\Controller\Admin;

use App\Entity\Categorie;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class CategorieCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Categorie::class;
    }

    
    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('nom', 'Nom de la catégorie'),
            // Téléverser une image
            ImageField::new('image', 'Image')
            ->setUploadDir('public/images/categories')
            ->setBasePath('uploads/images')
            ->setRequired(false),
        ];
    }
    
}
