<?php

namespace App\Form;

use App\Entity\Commande;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Validator\Constraints as Assert;

class AdresseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom',
                'constraints' => [
                    new Assert\Length([
                        'max' => 100,
                        'maxMessage' => 'Votre Nom ne doit pas dépasser {{ limit }} caractères.',
                    ]),
                ],
            ])
            ->add('prenom', TextType::class, ['label' => 'Prénom'])
            ->add('adresse', TextType::class, ['label' => 'Adresse'])
            ->add('ville', TextType::class, ['label' => 'Ville'])
            ->add('codePostal', TextType::class, ['label' => 'Code Postal'])
            ->add('tel', TextType::class, ['label' => 'Téléphone']);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Commande::class,
        ]);
    }
}
