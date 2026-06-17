<?php
namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class RegistrationParticulierType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName', TextType::class, [
                'label'       => 'Prenom',
                'mapped'      => false,
                'attr'        => ['placeholder' => 'Camille'],
                'constraints' => [new Assert\NotBlank(), new Assert\Length(['min' => 2])],
            ])
            ->add('lastName', TextType::class, [
                'label'       => 'Nom',
                'mapped'      => false,
                'attr'        => ['placeholder' => 'Durand'],
                'constraints' => [new Assert\NotBlank(), new Assert\Length(['min' => 2])],
            ])
            ->add('email', EmailType::class, [
                'label'       => 'Email',
                'attr'        => ['placeholder' => 'vous@email.fr'],
                'constraints' => [new Assert\NotBlank(), new Assert\Email()],
            ])
            ->add('plainPassword', PasswordType::class, [
                'label'       => 'Mot de passe',
                'mapped'      => false,
                'attr'        => ['placeholder' => '8 caracteres minimum'],
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length(['min' => 8]),
                    new Assert\Regex(['pattern' => '/[A-Z]/', 'message' => 'Doit contenir au moins une majuscule.']),
                ],
            ])
            ->add('agreeTerms', CheckboxType::class, [
                'label'       => "J'accepte les conditions d'utilisation",
                'mapped'      => false,
                'constraints' => [new Assert\IsTrue(['message' => 'Vous devez accepter les conditions.'])],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => User::class]);
    }
}
