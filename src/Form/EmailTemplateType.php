<?php

namespace App\Form;

use App\Entity\EmailTemplate;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class EmailTemplateType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('code', ChoiceType::class, [
                'choices' => [
                    'Registration Confirmation' => 'registration_confirmation',
                    'Account Approved' => 'account_approved',
                    'Reset Password' => 'reset_password',
                    'Role Change' => 'role_change',
                    'Permission Update' => 'permission_update',
                ],
                'attr' => ['class' => 'form-select'],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please select a template code',
                    ]),
                ],
            ])
            ->add('subject', TextType::class, [
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter a subject',
                    ]),
                ],
            ])
            ->add('htmlContent', TextareaType::class, [
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 20,
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter HTML content',
                    ]),
                ],
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
                        'message' => 'Please select a locale',
                    ]),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => EmailTemplate::class,
        ]);
    }
}
