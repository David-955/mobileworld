<?php

namespace App\Controller\Admin;

use App\Entity\Personnalisation;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class PersonnalisationCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Personnalisation::class;
    }

    /*
    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id'),
            TextField::new('title'),
            TextEditorField::new('description'),
        ];
    }
    */
}
