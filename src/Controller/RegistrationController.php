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
            // Hashage du mot de passe
            $user->setMotdepasse($userPasswordHasher->hashPassword($user, $form->get('plainPassword')->getData()));

            // Attribuer un rôle par défaut
            $user->setRole('ROLE_CLIENT');

            // Créer une Personnalisation et l'associer à l'utilisateur
            $personnalisation = new Personnalisation();
            $personnalisation->setPresentation('Bonjour, ceci est un message de présentation par défaut.');
            $personnalisation->setAvatar('/images/profils/defaut.png');
            $personnalisation->setPseudo('utilisateur' . random_int(10000, 99999));

            // Associer la Personnalisation à l'utilisateur (relation bidirectionnelle)
            $user->setPersonnalisation($personnalisation);
            $personnalisation->setUtilisateur($user); // Définir la relation inverse

            // Persist et flush
            $entityManager->persist($user);
            $entityManager->persist($personnalisation);
            $entityManager->flush();

            // Connecter l'utilisateur
            return $security->login($user, 'form_login', 'main');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }
}