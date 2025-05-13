<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Entity\Personnalisation;
use Symfony\Component\Mime\Email;
use App\Form\RegistrationFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

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
            $user->setMotdepasse($userPasswordHasher->hashPassword($user, $form->get('plainPassword')->getData()));
            $user->setRole('ROLE_CLIENT');
            $user->setVerification(false);
            $user->setToken(uniqid('', true));
    
            $personnalisation = new Personnalisation();
            $personnalisation->setPresentation('Bonjour, ceci est un message de présentation par défaut.');
            $personnalisation->setAvatar('/images/profils/defaut.webp');
            $personnalisation->setPseudo('utilisateur' . random_int(10000, 99999));
    
            $user->setPersonnalisation($personnalisation);
            $personnalisation->setUtilisateur($user);
    
            $entityManager->persist($user);
            $entityManager->persist($personnalisation);
            $entityManager->flush();
    
            $confirmationUrl = $this->generateUrl(
                'app_confirm_email',
                ['token' => $user->getToken()],
                UrlGeneratorInterface::ABSOLUTE_URL
            );
    
            $email = (new Email())
                ->from('dngo3819@gmail.com')
                ->to($user->getEmail())
                ->subject('Mobile World - Confirmation de votre inscription')
                ->html(
                    '<h1>Bienvenue chez Mobile World !</h1>' .
                    '<p>Merci de vous être inscrit sur notre plateforme.</p>' .
                    '<p>Veuillez confirmer votre compte en cliquant sur le lien ci-dessous :</p>' .
                    '<a href="' . htmlspecialchars($confirmationUrl) . '">Confirmer mon compte</a>'
                );
    
            $mailer->send($email);
    
            $this->addFlash('success', 'Un e-mail de confirmation a été envoyé. Veuillez vérifier votre boîte de réception.');
            return $this->redirectToRoute('app_login');

            // Éventuellement : return $this->redirectToRoute('app_login');
        } elseif ($form->isSubmitted()) {
            $this->addFlash('danger', 'Votre inscription comporte des erreurs. Veuillez corriger les champs. Minimum de 6 caractères pour le mot de passe, et maximum de 100 caractères.');
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

    #[Route('/check-verified', name: 'app_check_verified')]
    public function checkVerified(Request $request): RedirectResponse
    {
        // Récupérer l'utilisateur connecté
        $user = $this->getUser();
        if ($user && !$user->isVerification()) {
            // Déconnecter l'utilisateur s'il n'est pas vérifié
            $this->container->get('security.token_storage')->setToken(null);
            $request->getSession()->invalidate();
            // Rediriger vers /verification-pending
            return $this->redirectToRoute('app_verification_pending');
        }
        // Rediriger vers la page d'accueil si l'utilisateur est vérifié
        return $this->redirectToRoute('app_accueil');
    }

    #[Route('/verification-pending', name: 'app_verification_pending')]
    public function verificationPending(): Response
    {
        return $this->render('security/verification-pending.html.twig');
    }

    #[Route('/resend-confirmation', name: 'app_resend_confirmation')]
    public function resendConfirmation(
        Request $request,
        EntityManagerInterface $entityManager,
        MailerInterface $mailer
    ): Response {
        // Récupérer l'e-mail depuis la requête
        $email = $request->query->get('email');
        if (!$email) {
            $this->addFlash('error', 'Veuillez fournir une adresse e-mail.');
            return $this->redirectToRoute('app_verification_pending');
        }
        // Rechercher l'utilisateur par son email
        $user = $entityManager->getRepository(Utilisateur::class)->findOneBy(['email' => $email]);
        if (!$user) {
            $this->addFlash('error', 'Aucun compte trouvé avec cette adresse e-mail.');
            return $this->redirectToRoute('app_verification_pending');
        }
        // Vérifier si l'utilisateur est déjà vérifié
        if ($user->isVerification()) {
            $this->addFlash('info', 'Votre compte est déjà vérifié.');
            return $this->redirectToRoute('app_login');
        }
        // Générer un nouveau token pour la confirmation
        $user->setToken(uniqid('', true));
        $entityManager->flush();
        // Générer le lien de confirmation
        $confirmationUrl = $this->generateUrl(
            'app_confirm_email',
            ['token' => $user->getToken()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
        // Envoyer l'e-mail de confirmation
        $emailContent = (new Email())
            ->from('dngo3819@gmail.com')
            ->to($user->getEmail())
            ->subject('Nouveau lien de confirmation')
            ->html(
                '<h1>Bienvenue chez Mobile World !</h1>' .
                    '<p>Voici votre nouveau lien de confirmation :</p>' .
                    '<a href="' . htmlspecialchars($confirmationUrl) . '">Confirmer mon compte</a>'
            );
        $mailer->send($emailContent);
        $this->addFlash('success', 'Un nouveau lien de confirmation a été envoyé à votre adresse e-mail.');
        return $this->redirectToRoute('app_login');
    }
}
