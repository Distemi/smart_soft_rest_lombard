<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ClientLoginFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('ticket_number', TextType::class, [
                'label' => 'Номер залогового билета',
                'attr' => [
                    'autofocus' => true,
                ],
            ])
            ->add('full_name', TextType::class, [
                'label' => 'ФИО',
                'attr' => [
                    'placeholder' => 'Иванов Иван Иванович',
                ],
                'row_attr' => ['class' => 'mb-4'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => true,
            'csrf_field_name' => '_csrf_token',
            'csrf_token_id' => 'client_authenticate',
        ]);
    }

    public function getBlockPrefix(): string
    {
        return '';
    }
}
