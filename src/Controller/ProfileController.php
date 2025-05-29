<?php

namespace App\Controller;

use App\Entity\Personnalisation;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Request;

final class ProfileController extends AbstractController
{
    // Vérifie si l'utilisateur est vérifié    
    protected function checkVerifiedUser(): ?Response
    {
        $user = $this->getUser();
        if (!$user || !$user->isVerification()) {
            return $this->redirectToRoute('app_verification_pending');
        }
        return null;
    }

    #[Route('/profil/', name: 'app_profil')]
    public function profile(ManagerRegistry $doctrine): Response
    {
        $user = $this->getUser();
        // Bloquer si l'utilisateur n'est pas vérifié
        $redirect = $this->checkVerifiedUser();
        if ($redirect) return $redirect;

        $personnalisation = $doctrine->getRepository(Personnalisation::class)->findOneBy(['Utilisateur' => $user]);
        return $this->render('profile/index.html.twig', [
            'personnalisation' => $personnalisation,
        ]);
    }

    #[Route('/profile/avatar', name: 'app_profil_update_avatar', methods: ['POST'])]
    public function updateAvatar(Request $request, ManagerRegistry $doctrine): Response
    {
        $user = $this->getUser();
        // Bloquer si l'utilisateur n'est pas vérifié
        $redirect = $this->checkVerifiedUser();
        if ($redirect) return $redirect;

        $personnalisation = $doctrine->getRepository(Personnalisation::class)->findOneBy(['Utilisateur' => $user]);

        $avatarFile = $request->files->get('avatar');
        if ($avatarFile) {
            $newFilename = uniqid() . '.webp';
            $uploadDir = $this->getParameter('avatars_directory');

            $currentAvatar = $personnalisation->getAvatar();
            if ($currentAvatar && $currentAvatar !== '/images/profils/defaut.webp') {
                $currentAvatarPath = $this->getParameter('kernel.project_dir') . '/public' . $currentAvatar;
                if (file_exists($currentAvatarPath)) {
                    unlink($currentAvatarPath);
                }
            }

            $image = null;
            switch ($avatarFile->getMimeType()) {
                case 'image/jpeg':
                    $image = imagecreatefromjpeg($avatarFile->getPathname());
                    break;
                case 'image/png':
                    $image = imagecreatefrompng($avatarFile->getPathname());
                    break;
                case 'image/gif':
                    $image = imagecreatefromgif($avatarFile->getPathname());
                    break;
                case 'image/webp':
                    $image = imagecreatefromwebp($avatarFile->getPathname());
                    break;
                default:
                    $this->addFlash('danger', 'Format d\'image non supporté. Veuillez choisir une image en JPEG, PNG, GIF ou WEBP.');
                    return $this->redirectToRoute('app_profil');
            }

            if ($image) {
                $resizedImage = imagescale($image, 350, 350);
                imagewebp($resizedImage, $uploadDir . '/' . $newFilename);
                imagedestroy($image);
                imagedestroy($resizedImage);

                $personnalisation->setAvatar('/images/profils/' . $newFilename);
                $doctrine->getManager()->flush();

                $this->addFlash('success', 'Votre avatar a été mis à jour avec succès.');
            }
        } else {
            $this->addFlash('danger', 'Aucun fichier envoyé.');
        }

        return $this->redirectToRoute('app_profil');
    }


    #[Route('/profil/reset/avatar', name: 'app_profil_reset_avatar', methods: ['POST'])]
    public function resetAvatar(ManagerRegistry $doctrine): Response
    {
        $user = $this->getUser();
        // Bloquer si l'utilisateur n'est pas vérifié
        $redirect = $this->checkVerifiedUser();
        if ($redirect) return $redirect;

        $personnalisation = $doctrine->getRepository(Personnalisation::class)->findOneBy(['Utilisateur' => $user]);

        $personnalisation->setAvatar('/images/profils/defaut.webp');
        $doctrine->getManager()->flush();

        $this->addFlash('success', 'Votre avatar a été réinitialisé avec succès.');

        return $this->redirectToRoute('app_profil');
    }

    #[Route('/profil/update', name: 'app_profil_update_presentation', methods: ['GET', 'POST'])]
    public function updateProfile(ManagerRegistry $doctrine, RequestStack $requestStack): Response
    {
        $user = $this->getUser();
        // Bloquer si l'utilisateur n'est pas vérifié
        $redirect = $this->checkVerifiedUser();
        if ($redirect) return $redirect;

        $entityManager = $doctrine->getManager();
        $personnalisation = $doctrine->getRepository(Personnalisation::class)->findOneBy(['Utilisateur' => $user]);

        $request = $requestStack->getCurrentRequest();
        $presentation = $request->request->get('presentation');

        if (strlen($presentation) > 1000) {
            $this->addFlash('danger', 'Votre présentation ne peut pas dépasser 1000 caractères.');
            return $this->redirectToRoute('app_profil');
        }

        $personnalisation->setPresentation($presentation);
        $entityManager->persist($personnalisation);
        $entityManager->flush();

        $this->addFlash('success', 'Votre présentation a bien été mise à jour.');

        return $this->redirectToRoute('app_profil');
    }


    #[Route('/profil/update/pseudo', name: 'app_profil_update_pseudo', methods: ['POST'])]
    public function updatePseudo(Request $request, ManagerRegistry $doctrine): Response
    {
        $user = $this->getUser();
        $redirect = $this->checkVerifiedUser();
        if ($redirect) return $redirect;

        $entityManager = $doctrine->getManager();
        $personnalisationRepo = $doctrine->getRepository(Personnalisation::class);
        $personnalisation = $personnalisationRepo->findOneBy(['Utilisateur' => $user]);

        $pseudo = trim($request->request->get('pseudo'));

        // Vérifie longueur max
        if (strlen($pseudo) > 50) {
            $this->addFlash('danger', 'Le pseudo ne peut pas dépasser 50 caractères.');
            return $this->redirectToRoute('app_profil');
        }

        // Vérifie si ce pseudo est déjà utilisé
        $existingPseudo = $personnalisationRepo->findOneBy(['pseudo' => $pseudo]);

        if ($existingPseudo && $existingPseudo !== $personnalisation) {
            $this->addFlash('danger', 'Ce pseudo est déjà utilisé.');
            return $this->redirectToRoute('app_profil');
        }

        // Sauvegarde
        $personnalisation->setPseudo($pseudo);
        $entityManager->flush();

        $this->addFlash('success', 'Pseudo mis à jour avec succès.');
        return $this->redirectToRoute('app_profil');
    }


    // Page de profil pour chaque utilisateur
    #[Route('/profil/{pseudo}', name: 'app_profil_pseudo')]
    public function profileByPseudo($pseudo, ManagerRegistry $doctrine): Response
    {
        $personnalisation = $doctrine->getRepository(Personnalisation::class)->findOneBy(['pseudo' => $pseudo]);

        return $this->render('profile/show.html.twig', [
            'personnalisation' => $personnalisation,
        ]);
    }
}
