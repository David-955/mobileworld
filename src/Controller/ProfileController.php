<?php

namespace App\Controller;

use App\Entity\Personnalisation;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
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

        if (!$personnalisation) {
            throw $this->createNotFoundException('Personnalisation non trouvée');
        }

        $avatarFile = $request->files->get('avatar');
        if ($avatarFile) {
            $newFilename = uniqid().'.'.$avatarFile->guessExtension();

            try {
                $avatarFile->move(
                    // voir dans services.yaml
                    $this->getParameter('avatars_directory'),
                    $newFilename
                );
            } catch (FileException $e) {
                // handle exception if something happens during file upload
            }

            // chemin de l'avatar dans la bdd
            $personnalisation->setAvatar('/images/profils/'.$newFilename);
            $doctrine->getManager()->flush();
        }

        return $this->redirectToRoute('app_profil');
    }

    #[Route('/profil/update', name: 'app_profil_update_presentation', methods: ['GET', 'POST'])]
    public function updateProfile(ManagerRegistry $doctrine, RequestStack $requestStack): Response
    {

        $user = $this->getUser();
        $entityManager = $doctrine->getManager();
        $personnalisation = $doctrine->getRepository(Personnalisation::class)->findOneBy(['utilisateur' => $user]);

        // vérif si l'objet personnalisation existe déjà, sinon on le crée
        // par défaut non
        if (!$personnalisation) {
            $personnalisation = new Personnalisation();
            // l'id de l'utilisateur sera associé à l'objet de personnalisation et stocké dans la colonne utilisateur_id de la table personnalisation
            $personnalisation->setUtilisateur($user);
        }

        $request = $requestStack->getCurrentRequest();
        $presentation = $request->request->get('presentation');
        $personnalisation->setPresentation($presentation);

        $entityManager->persist($personnalisation);
        $entityManager->flush();

        return $this->redirectToRoute('app_profil');
    }
}
