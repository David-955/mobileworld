<?php

namespace App\Controller\Admin;

use App\Entity\Personnalisation;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;

class PersonnalisationCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Personnalisation::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('avatar'),
            TextareaField::new('presentation'),
            TextField::new('pseudo'),
        ];
    }
}
