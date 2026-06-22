<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;

class SubscriberType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('category', ChoiceType::class, [
                'choices' => [
                    'Particulier' => 'particulier',
                    'Professionnel' => 'professionnel',
                ],
                'expanded' => true,
            ])
            ->add('company', TextType::class, ['required' => false])
            ->add('siret', TextType::class, ['required' => false])
            ->add('civility', ChoiceType::class, [
                'choices' => ['Madame' => 'Madame', 'Monsieur' => 'Monsieur'],
                'data' => 'Monsieur',
            ])
            ->add('last_name', TextType::class, ['constraints' => [new NotBlank()]])
            ->add('birth_name', TextType::class, ['required' => false])
            ->add('first_name', TextType::class, ['constraints' => [new NotBlank()]])
            ->add('address', TextType::class, ['constraints' => [new NotBlank()]])
            ->add('address_complement', TextType::class, ['required' => false])
            ->add('zip_code', TextType::class, ['constraints' => [new NotBlank()]])
            ->add('city', TextType::class, ['constraints' => [new NotBlank()]])
            ->add('country', TextType::class, ['data' => 'France', 'constraints' => [new NotBlank()]])
            ->add('email', EmailType::class, [
                'constraints' => [new NotBlank(), new Email()]
            ]);

        if ($options['is_pro_user']) {
            $builder->add('client_reference', TextType::class, ['required' => false]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'empty_data' => [],
            'is_pro_user' => false,
        ]);
    }
}