<?php

namespace App\Controller;

use App\Entity\Personnalisation;
use App\Entity\Utilisateur;
use App\Form\RegistrationFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, Security $security, EntityManagerInterface $entityManager): Response
    {
        $user = new Utilisateur();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var string $plainPassword */
            $plainPassword = $form->get('plainPassword')->getData();
            // Hashage du mot de passe dans la bdd (sécurité)
            $user->setMotdepasse($userPasswordHasher->hashPassword($user, $plainPassword));
            // Attribuer un rôle par défaut à l'inscription : ROLE_CLIENT
            $user->setRole('ROLE_CLIENT');
            // persist pour dire à doctrine (gère les interactions dans les bdd) de prendre en compte l'entité $user pour mettre dans la bdd ensuite avec flush
            $entityManager->persist($user);

            // Créer une instance de Personnalisation et la lier à l'utilisateur
            $personnalisation = new Personnalisation();
            $personnalisation->setUtilisateur($user);
            $personnalisation->setPresentation('Bonjour, ceci est un message de présentation par défaut.');
            $personnalisation->setAvatar('/images/profils/defaut.png'); // Définir l'image par défaut
            $entityManager->persist($personnalisation);


            // méthode flush pour enregistrer les données dans la bdd, execute les opérations de persistance: insert $user dans la bdd dans la table correspondante
            $entityManager->flush();

            return $security->login($user, 'form_login', 'main');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }
}
