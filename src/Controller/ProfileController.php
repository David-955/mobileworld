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
    #[Route('/profil/', name: 'app_profil')]
    public function profile(ManagerRegistry $doctrine): Response
    {
        $user = $this->getUser();
        $personnalisation = $doctrine->getRepository(Personnalisation::class)->findOneBy(['utilisateur' => $user]);
        return $this->render('profile/index.html.twig', [
            'personnalisation' => $personnalisation,
        ]);
    }

    #[Route('/profile/avatar', name: 'app_profil_update_avatar', methods: ['POST'])]
    public function updateAvatar(Request $request, ManagerRegistry $doctrine): Response
    {
        $user = $this->getUser();
        $personnalisation = $doctrine->getRepository(Personnalisation::class)->findOneBy(['utilisateur' => $user]);

        $avatarFile = $request->files->get('avatar');
        if ($avatarFile) {
            $newFilename = uniqid().'.webp';
            $uploadDir = $this->getParameter('avatars_directory');

            $currentAvatar = $personnalisation->getAvatar();
            if ($currentAvatar && $currentAvatar !== '/images/profils/defaut.png') {
                $currentAvatarPath = $this->getParameter('kernel.project_dir').'/public'.$currentAvatar;
                if (file_exists($currentAvatarPath)) {
                    unlink($currentAvatarPath);
                }
            }

            // Convertir l'image en .webp
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
                default:
                    throw new \Exception('Format d\'image non supporté');
            }

            if ($image) {
                // Redimensionner l'image à 350x350 pixels
                $resizedImage = imagescale($image, 350, 350);

                // Convertir l'image redimensionnée en .webp
                imagewebp($resizedImage, $uploadDir.'/'.$newFilename);
                imagedestroy($image);
                imagedestroy($resizedImage);

                // Mettre à jour le chemin de l'avatar dans la base de données
                $personnalisation->setAvatar('/images/profils/'.$newFilename);
                $doctrine->getManager()->flush();
            }
        }

        return $this->redirectToRoute('app_profil');
    }

    #[Route('/profil/reset/avatar', name: 'app_profil_reset_avatar', methods: ['POST'])]
    public function resetAvatar(ManagerRegistry $doctrine): Response
    {
        $user = $this->getUser();
        $personnalisation = $doctrine->getRepository(Personnalisation::class)->findOneBy(['utilisateur' => $user]);

        // Définir l'avatar par défaut
        $personnalisation->setAvatar('/images/profils/defaut.png');
        $doctrine->getManager()->flush();

        return $this->redirectToRoute('app_profil');
    }

    #[Route('/profil/update', name: 'app_profil_update_presentation', methods: ['GET', 'POST'])]
    public function updateProfile(ManagerRegistry $doctrine, RequestStack $requestStack): Response
    {

        $user = $this->getUser();
        $entityManager = $doctrine->getManager();
        $personnalisation = $doctrine->getRepository(Personnalisation::class)->findOneBy(['utilisateur' => $user]);

        $request = $requestStack->getCurrentRequest();
        $presentation = $request->request->get('presentation');
        $personnalisation->setPresentation($presentation);

        $entityManager->persist($personnalisation);
        $entityManager->flush();

        return $this->redirectToRoute('app_profil');
    }

    #[Route('/profil/update/pseudo', name: 'app_profil_update_pseudo', methods: ['POST'])]
    public function updatePseudo(Request $request, ManagerRegistry $doctrine): Response
    {
        $user = $this->getUser();
        $personnalisation = $doctrine->getRepository(Personnalisation::class)->findOneBy(['utilisateur' => $user]);

        $pseudo = $request->request->get('pseudo');
        $personnalisation->setPseudo($pseudo);
        $doctrine->getManager()->flush();

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

