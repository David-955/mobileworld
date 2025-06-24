<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class SecurityController extends AbstractController
{
    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();
        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error, 
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
            $email = $form->get('email')->getData();

            $user = $entityManager->getRepository(Utilisateur::class)->findOneBy(['email' => $email]);
            if (!$user) {
                $this->addFlash('danger', 'Aucun compte trouvé avec cette adresse Email.');
                return $this->redirectToRoute('app_forgot_password');
            }

            $resetToken = uniqid('', true);
            $user->setToken($resetToken);
            $entityManager->flush();

            $resetUrl = $this->generateUrl(
                'app_reset_password',
                ['token' => $resetToken],
                UrlGeneratorInterface::ABSOLUTE_URL
            );

            $emailContent = (new Email())
                ->from(new Address('dngo3819@example.com', 'Mobile World'))
                ->to($user->getEmail())
                ->subject('Réinitialisation de votre mot de passe')
                ->html(
                    '<h1>Réinitialisation de votre mot de passe</h1>' .
                        '<p>Vous avez demandé à réinitialiser votre mot de passe. Cliquez sur le lien ci-dessous pour profiter pleinement de l\'univers Mobile World :</p>' .
                        '<a href="' . htmlspecialchars($resetUrl) . '">Réinitialiser mon mot de passe</a>'
                );

            $mailer->send($emailContent);

            $this->addFlash('success', 'Email pour réinitialiser le mot de passe envoyé avec succès.');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/forgot-password.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/new-password', name: 'app_new_password')]
    public function newPassword(
        EntityManagerInterface $entityManager,
    ): Response {
        // Vérifier si l'utilisateur est connecté
        $user = $this->getUser();
        if (!$user) {
            $this->addFlash('danger', 'Vous devez être connecté pour changer votre mot de passe.');
            return $this->redirectToRoute('app_login');
        }

        $resetToken = uniqid('', true);
        $user->setToken($resetToken);
        $entityManager->flush();

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

        $form = $this->createFormBuilder()
            ->add('plainPassword', PasswordType::class, [
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Veuillez entrer un mot de passe.']),
                    new Assert\Length([
                        'min' => 6,
                        'minMessage' => 'Minimum de 6 caractères pour le mot de passe, et maximum de 100 caractères.',
                        'max' => 100,
                    ]),
                    new Assert\Regex([
                        'pattern' => '/^(?=.*[A-Z])(?=.*\d).+$/',
                        'message' => 'Votre mot de passe doit contenir au moins 1 majuscule et 1 chiffre.',
                    ]),
                ],
                'label' => 'Nouveau mot de passe'
            ])
            ->add('confirmPassword', PasswordType::class, [
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Veuillez confirmer votre mot de passe.']),
                ],
                'label' => 'Confirmation'
            ])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            if ($data['plainPassword'] !== $data['confirmPassword']) {
                $this->addFlash('danger', 'Les mots de passe ne correspondent pas.');
                return $this->redirectToRoute('app_reset_password', ['token' => $token]);
            }

            $hashedPassword = $passwordHasher->hashPassword($user, $data['plainPassword']);
            $user->setMotdepasse($hashedPassword);

            // Effacer le token après utilisation
            $user->setToken(null);
            $entityManager->flush();

            $this->addFlash('success', 'Votre mot de passe a été modifié avec succès.');
            return $this->redirectToRoute('app_home');
        }

        return $this->render('security/reset-password.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
