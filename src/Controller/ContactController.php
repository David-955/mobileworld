<?php

namespace App\Controller;

use App\Form\ContactType;
use Symfony\Component\Mime\Email;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ContactController extends AbstractController
{
    #[Route('/contact', name: 'app_contact')]
    public function index(Request $request, MailerInterface $mailer): Response
    {
        // Créer un tableau vide pour les données du formulaire
        $formData = [];

        // Créer le formulaire en utilisant ContactFormType
        $form = $this->createForm(ContactType::class, $formData);

        // Gérer la soumission du formulaire
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Récupérer les données validées
            $formData = $form->getData();

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
        }

        // Afficher le formulaire
        return $this->render('contact/index.html.twig', [
            'form' => $form,
        ]);
    }
}