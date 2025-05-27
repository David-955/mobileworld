<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class ContactType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Votre nom',
                'constraints' => [
                    new Assert\NotBlank(message: 'Le nom est obligatoire.'),
                    new Assert\Regex([
                        'pattern' => '/^[a-zA-ZÀ-ÿ\s\-]+$/',
                        'message' => 'Le nom contient des caractères invalides.',
                    ]),
                    new Assert\Length([
                        'max' => 100,
                        'maxMessage' => 'Le nom ne peut pas dépasser {{ limit }} caractères.',
                    ]),
                ],
            ])
            ->add('prenom', TextType::class, [
                'label' => 'Votre prénom',
                'constraints' => [
                    new Assert\NotBlank(message: 'Le prénom est obligatoire.'),
                    new Assert\Regex([
                        'pattern' => '/^[a-zA-ZÀ-ÿ\s\-]+$/',
                        'message' => 'Le prénom contient des caractères invalides.',
                    ]),
                    new Assert\Length([
                        'max' => 100,
                        'maxMessage' => 'Le prénom ne peut pas dépasser {{ limit }} caractères.',
                    ]),
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Votre adresse Email',
                'constraints' => [
                    new Assert\NotBlank(message: 'L\'adresse Email est obligatoire.'),
                    new Assert\Email(message: 'L\'adresse Email n\'est pas valide.'),
                ],
            ])
            ->add('sujet', TextType::class, [
                'label' => 'Sujet du message',
                'constraints' => [
                    new Assert\NotBlank(message: 'Le sujet est obligatoire.'),
                    new Assert\Regex([
                        'pattern' => '/^[a-zA-Z0-9À-ÿ\s\-\.,!?]+$/',
                        'message' => 'Le sujet contient des caractères invalides.',
                    ]),
                    new Assert\Length([
                        'max' => 500,
                        'maxMessage' => 'Le sujet ne peut pas dépasser {{ limit }} caractères.',
                    ]),
                ],
            ])
            ->add('message', TextareaType::class, [
                'label' => 'Votre message',
                'attr' => [
                    'class' => 'form-control', // Style standard de Bootstrap
                    'rows' => 6, // Hauteur fixe
                    'style' => 'height: auto;', // Permettre à rows de fonctionner correctement

                ],
                'constraints' => [
                    new Assert\NotBlank(message: 'Le message est obligatoire.'),
                    new Assert\Length([
                        'max' => 2500,
                        'maxMessage' => 'Le message ne peut pas dépasser {{ limit }} caractères.',
                    ]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Définir les options par défaut ici si nécessaire
        ]);
    }
}
