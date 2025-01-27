<?php

namespace App\Controller;

use App\Entity\Personnalisation;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RequestStack;

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

    #[Route('/profil/update', name: 'app_profil_update', methods: ['GET', 'POST'])]
    public function updateProfile(ManagerRegistry $doctrine, RequestStack $requestStack): Response
    {
        $user = $this->getUser();
        $entityManager = $doctrine->getManager();
        $personnalisation = $doctrine->getRepository(Personnalisation::class)->findOneBy(['utilisateur' => $user]);

        // vérif si l'objet personnalisation existe déjà, sinon on le crée
        if (!$personnalisation) {
            $personnalisation = new Personnalisation();
            // l'id de l'utilisateur sera associé à l'objet de personnalisation et stocké dans la colonne utilisateur_id de la table personnalisation
            $personnalisation->setUtilisateur($user);
        }

        $request = $requestStack->getCurrentRequest();
        $presentation = $request->request->get('presentation');
        $avatar = $request->request->get('avatar');
        $personnalisation->setPresentation($presentation);
        $personnalisation->setAvatar($avatar);

        $entityManager->persist($personnalisation);
        $entityManager->flush();

        return $this->redirectToRoute('app_profil');
    }
}
