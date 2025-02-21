<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use Symfony\Component\Mime\Email;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

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

    // voir dans le security.yaml, après une connexion réussie, l'utilisateur est redirigé vers la page d'accueil
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
    
        // Générer un nouveau token (optionnel)
        $user->setToken(uniqid('', true));
        $entityManager->flush();
    
        // Générer le lien de confirmation
        $confirmationUrl = $this->generateUrl(
            'app_confirm_email',
            ['token' => $user->getToken()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
    
        // Créer et envoyer l'e-mail
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

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
