<?php

namespace App\DataFixtures;

use App\Entity\CategoriesBoutique;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $categories = [
            ['coque', "coque.jpg", "coque"],
            ['housse', "housse.jpg", "housse"],
            ['etui', "etui.jpg", "etui"]
        ];
        foreach ($categories as [$nom, $image, $alias]) {
                $categorie = new CategoriesBoutique();
                $categorie->setNom($nom);
                $categorie->setImage("/image/categories/" . $image);
                $categorie->setAlias($alias);
                $manager->persist($categorie);
            }
        $manager->flush();
    }
}
