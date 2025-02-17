<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class ContactController extends AbstractController
{
    #[Route('/contact', name: 'app_contact')]
    public function index(Request $request, MailerInterface $mailer): Response
    {
        // Initialiser les variables du formulaire
        $formData = [
            'nom' => '',
            'prenom' => '',
            'email' => '',
            'sujet' => '',
            'message' => '',
        ];

        // Vérifier si le formulaire a été soumis
        if ($request->isMethod('POST')) {
            // Récupérer les données du formulaire
            $formData['nom'] = $request->request->get('nom');
            $formData['prenom'] = $request->request->get('prenom');
            $formData['email'] = $request->request->get('email');
            $formData['sujet'] = $request->request->get('sujet');
            $formData['message'] = $request->request->get('message');

            if (!empty($formData['nom']) && !empty($formData['email']) && !empty($formData['message'])) {
                // Envoyer l'email à l'admin
                $email = (new Email())
                    ->from($formData['email'])
                    ->to('dngo3819@gmail.com')
                    ->subject($formData['sujet'])
                    ->text("Message de {$formData['prenom']} {$formData['nom']} ({$formData['email']}):\n\n{$formData['message']}");
                $mailer->send($email);

                // Rediriger avec un message de succès
                $this->addFlash('success', 'Merci, votre message a bien été envoyé.');
                return $this->redirectToRoute('app_contact');
            } else {
                // Ajouter un message d'erreur si des champs sont manquants
                $this->addFlash('error', 'Veuillez remplir tous les champs obligatoires.');
            }
        }

        // Afficher le formulaire
        return $this->render('contact/index.html.twig', [
            'formData' => $formData,
        ]);
    }
}
