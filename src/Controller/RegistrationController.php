<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Entity\Personnalisation;
use Symfony\Component\Mime\Email;
use App\Form\RegistrationFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        EntityManagerInterface $entityManager,
        MailerInterface $mailer
    ): Response {
        $user = new Utilisateur();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Hashage du mot de passe
            $user->setMotdepasse($userPasswordHasher->hashPassword($user, $form->get('plainPassword')->getData()));

            // Attribuer un rôle par défaut
            $user->setRole('ROLE_CLIENT');
            // compte non vérifié de base car il faut confirmer grâce à l'email envoyé avec un lien
            $user->setVerification(false);

            // Générer un token unique basé sur le temps
            $user->setToken(uniqid('', true));

            // Créer une Personnalisation et l'associer à l'utilisateur
            $personnalisation = new Personnalisation();
            $personnalisation->setPresentation('Bonjour, ceci est un message de présentation par défaut.');
            $personnalisation->setAvatar('/images/profils/defaut.webp');
            $personnalisation->setPseudo('utilisateur' . random_int(10000, 99999));

            // Associer la Personnalisation à l'utilisateur (relation bidirectionnelle)
            $user->setPersonnalisation($personnalisation);
            $personnalisation->setUtilisateur($user);

            // Persist et flush
            $entityManager->persist($user);
            $entityManager->persist($personnalisation);
            $entityManager->flush();

            // Générer le lien de confirmation
            $confirmationUrl = $this->generateUrl(
                'app_confirm_email',
                ['token' => $user->getToken()],
                UrlGeneratorInterface::ABSOLUTE_URL
            );

            // Créer et envoyer l'e-mail
            $email = (new Email())
                ->from('dngo3819@gmail.com')
                ->to($user->getEmail())
                ->subject('Confirmation de votre inscription')
                ->html(
                    '<h1>Bienvenue chez Mobile World !</h1>' .
                    '<p>Merci de vous être inscrit sur notre plateforme.</p>' .
                    '<p>Veuillez confirmer votre compte en cliquant sur le lien ci-dessous :</p>' .
                    '<a href="' . htmlspecialchars($confirmationUrl) . '">Confirmer mon compte</a>'
                );
            
            $mailer->send($email);
            // (message ne s'affiche pas à revoir)
            $this->addFlash('success', 'Un e-mail de confirmation a été envoyé. Veuillez vérifier votre boîte de réception.');
        }

        return $this->render('security/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }

    #[Route('/confirm-email/{token}', name: 'app_confirm_email')]
    public function confirmEmail(string $token, EntityManagerInterface $entityManager): Response
    {
        // Rechercher l'utilisateur par son token
        $user = $entityManager->getRepository(Utilisateur::class)->findOneBy(['token' => $token]);

        if (!$user) {
            throw $this->createNotFoundException('Token invalide.');
        }

        // Marquer l'utilisateur comme confirmé
        $user->setVerification(true);

        $entityManager->flush();

        return $this->render('security/email-confirme.html.twig', [
            'message' => 'Votre compte a été confirmé avec succès.',
        ]);
    }
}
