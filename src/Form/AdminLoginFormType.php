<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AdminLoginFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('_username', TextType::class, [
                'label' => 'Логин',
                'attr' => [
                    'autocomplete' => 'username',
                    'autofocus' => true,
                ],
            ])
            ->add('_password', PasswordType::class, [
                'label' => 'Пароль',
                'attr' => [
                    'autocomplete' => 'current-password',
                ],
                'row_attr' => ['class' => 'mb-4'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => true,
            'csrf_field_name' => '_csrf_token',
            'csrf_token_id' => 'authenticate',
        ]);
    }

    public function getBlockPrefix(): string
    {
        return '';
    }
}
