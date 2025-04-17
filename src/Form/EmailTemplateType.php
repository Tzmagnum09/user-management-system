<?php

namespace App\Form;

use App\Entity\EmailTemplate;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class EmailTemplateType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Si c'est un nouveau modèle, afficher le champ code personnalisé
        if ($options['is_new']) {
            // Option pour sélectionner un modèle prédéfini ou saisir un nouveau code
            $builder->add('template_selection', ChoiceType::class, [
                'mapped' => false,
                'required' => false,
                'choices' => [
                    'Sélectionner un modèle' => [
                        'Confirmation d\'inscription' => 'registration_confirmation',
                        'Compte approuvé' => 'account_approved',
                        'Réinitialisation de mot de passe' => 'reset_password',
                        'Changement de rôle' => 'role_change',
                        'Mise à jour des permissions' => 'permission_update',
                    ],
                    'Ajouter un nouveau code' => 'custom',
                ],
                'attr' => [
                    'class' => 'form-select',
                    'id' => 'template_selection',
                ],
                'label' => 'Type de modèle',
            ]);
            
            // Champ pour le code personnalisé
            $builder->add('code', TextType::class, [
                'attr' => [
                    'class' => 'form-control',
                    'id' => 'custom_code',
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez saisir un code de modèle',
                    ]),
                ],
                'label' => 'Code du modèle',
                'help' => 'Utilisez uniquement des lettres minuscules, chiffres et underscores (ex: newsletter_bienvenue)',
            ]);
        } else {
            // Pour l'édition, le code est en lecture seule
            $builder->add('code', TextType::class, [
                'attr' => [
                    'class' => 'form-control', 
                    'readonly' => true
                ],
                'label' => 'Code du modèle',
            ]);
        }
        
        $builder
            ->add('subject', TextType::class, [
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez saisir un sujet',
                    ]),
                ],
                'label' => 'Sujet de l\'email',
            ])
            ->add('htmlContent', TextareaType::class, [
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 20,
                    'style' => 'height: 500px;', // Pour TinyMCE
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez saisir le contenu HTML',
                    ]),
                ],
                'label' => 'Contenu HTML',
            ])
            ->add('locale', ChoiceType::class, [
                'choices' => [
                    'Français' => 'fr',
                    'English' => 'en',
                    'Nederlands' => 'nl',
                    'Deutsch' => 'de',
                ],
                'attr' => ['class' => 'form-select'],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez sélectionner une langue',
                    ]),
                ],
                'label' => 'Langue',
                'disabled' => !$options['is_new'], // Désactiver le champ locale si on édite un modèle existant
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => EmailTemplate::class,
            'is_new' => false, // Par défaut, on considère qu'on édite un modèle existant
        ]);
    }
}