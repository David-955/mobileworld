<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AdresseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => '* Nom',
                'attr' => [
                    'placeholder' => 'Entrez votre nom',
                    'minlength' => 3,
                    'maxlength' => 100,
                    'class' => 'form-control'
                ],
            ])
            ->add('prenom', TextType::class, [
                'label' => '* Prénom',
                'attr' => [
                    'placeholder' => 'Entrez votre prénom',
                    'minlength' => 3,
                    'maxlength' => 100,
                    'class' => 'form-control'
                ],
            ])
            ->add('adresse', TextType::class, [
                'label' => '* Adresse',
                'attr' => [
                    'placeholder' => 'Entrez votre adresse',
                    'minlength' => 3,
                    'maxlength' => 150,
                    'class' => 'form-control'
                ],
            ])
            ->add('ville', TextType::class, [
                'label' => '* Ville',
                'attr' => [
                    'placeholder' => 'Entrez votre ville',
                    'minlength' => 1,
                    'maxlength' => 100,
                    'class' => 'form-control'
                ],
            ])
            ->add('codePostal', TextType::class, [
                'label' => '* Code Postal',
                'attr' => [
                    'placeholder' => 'Exemple : 75000',
                    'minlength' => 5,
                    'maxlength' => 5,
                    'class' => 'form-control'
                ],
            ])
            ->add('tel', TextType::class, [
                'label' => '* Téléphone',
                'attr' => [
                    'placeholder' => 'Exemple : 0612345678',
                    'minlength' => 10,
                    'maxlength' => 10,
                    'class' => 'form-control'
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => \App\Entity\Commande::class,
        ]);
    }
}