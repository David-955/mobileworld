<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use Symfony\Component\Mime\Email;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class SecurityController extends AbstractController
{
    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // Récupérer l'erreur d'authentification s'il y en a une
        $error = $authenticationUtils->getLastAuthenticationError();
        // Dernier nom d'utilisateur saisi par l'utilisateur
        $lastUsername = $authenticationUtils->getLastUsername();
        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error, // Passer l'erreur au template
        ]);
    }

    #[Route('/forgot-password', name: 'app_forgot_password')]
    public function forgotPassword(
        Request $request,
        EntityManagerInterface $entityManager,
        MailerInterface $mailer
    ): Response {
        // Créer un formulaire simple pour saisir l'email
        $form = $this->createFormBuilder()
            ->add('email', \Symfony\Component\Form\Extension\Core\Type\EmailType::class)
            ->getForm();
    
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // Récupérer l'email depuis le formulaire
            $email = $form->get('email')->getData();
    
            // Rechercher l'utilisateur par son email
            $user = $entityManager->getRepository(Utilisateur::class)->findOneBy(['email' => $email]);
            if (!$user) {
                $this->addFlash('error', 'Aucun compte trouvé avec cette adresse e-mail.');
                return $this->redirectToRoute('app_forgot_password');
            }
    
            // Générer un token unique pour la réinitialisation
            $resetToken = uniqid('', true);
            $user->setToken($resetToken); // Ajoutez une méthode `setToken` dans votre entité Utilisateur
            $entityManager->flush();
    
            // Générer le lien de réinitialisation
            $resetUrl = $this->generateUrl(
                'app_reset_password',
                ['token' => $resetToken],
                UrlGeneratorInterface::ABSOLUTE_URL
            );
    
            // Envoyer l'e-mail de réinitialisation
            $emailContent = (new Email())
                ->from('dngo3819@gmail.com')
                ->to($user->getEmail())
                ->subject('Mobile World - Réinitialisation de votre mot de passe')
                ->html(
                    '<h1>Réinitialisation de votre mot de passe</h1>' .
                    '<p>Vous avez demandé à réinitialiser votre mot de passe. Cliquez sur le lien ci-dessous pour profiter pleinement de l\'univers Mobile World :</p>' .
                    '<a href="' . htmlspecialchars($resetUrl) . '">Réinitialiser mon mot de passe</a>'
                );
    
            $mailer->send($emailContent);
            
            $this->addFlash('success', 'Email pour réinitialiser le mot de passe envoyé avec succès.');
            return $this->redirectToRoute('app_login');
            }
    
        // Afficher le formulaire
        return $this->render('security/forgot-password.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/new-password', name: 'app_new_password')]
    public function newPassword(
        EntityManagerInterface $entityManager,
    ): Response {
        // Vérifier si l'utilisateur est connecté
        $user = $this->getUser();
        if (!$user) {
            $this->addFlash('error', 'Vous devez être connecté pour changer votre mot de passe.');
            return $this->redirectToRoute('app_login');
        }
    
        // Générer un token unique pour la réinitialisation
        $resetToken = uniqid('', true);
        $user->setToken($resetToken); // Assurez-vous que votre entité Utilisateur a une méthode `setToken`
        $entityManager->flush();
    
        // Rediriger vers la page de réinitialisation avec le token
        return $this->redirectToRoute('app_reset_password', ['token' => $resetToken]);
    }

    #[Route('/reset-password/{token}', name: 'app_reset_password')]
    public function resetPassword(
        string $token,
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        // Rechercher l'utilisateur par son token
        $user = $entityManager->getRepository(Utilisateur::class)->findOneBy(['token' => $token]);
        if (!$user) {
            throw $this->createNotFoundException('Token invalide.');
        }
    
        // Formulaire pour saisir le nouveau mot de passe
        $form = $this->createFormBuilder()
            ->add('plainPassword', \Symfony\Component\Form\Extension\Core\Type\PasswordType::class)
            ->add('confirmPassword', \Symfony\Component\Form\Extension\Core\Type\PasswordType::class)
            ->getForm();
    
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
    
            // Vérifier que les deux mots de passe correspondent
            if ($data['plainPassword'] !== $data['confirmPassword']) {
                $this->addFlash('error', 'Les mots de passe ne correspondent pas.');
                return $this->redirectToRoute('app_reset_password', ['token' => $token]);
            }
    
            // Hasher le nouveau mot de passe
            $hashedPassword = $passwordHasher->hashPassword($user, $data['plainPassword']);
            $user->setMotdepasse($hashedPassword);
    
            // Effacer le token après utilisation
            $user->setToken(null);
            $entityManager->flush();
    
            $this->addFlash('success', 'Votre mot de passe a été réinitialisé avec succès.');
            return $this->redirectToRoute('app_login');
        }
    
        return $this->render('security/reset-password.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}