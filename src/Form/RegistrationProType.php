<?php
namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class RegistrationProType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('profession', ChoiceType::class, [
                'label'   => 'Profession',
                'choices' => [
                    "Courtier / Agent d'assurance" => 'courtier',
                    'Avocat'                        => 'avocat',
                    'Syndic de copropriete'         => 'syndic',
                    'Administrateur de biens'       => 'adb',
                    'Expert-comptable'              => 'expert',
                    'Autre profession'              => 'autre',
                ],
                'attr'        => ['onchange' => 'adaptReg(this.value)'],
                'constraints' => [new Assert\NotBlank()],
            ])
            ->add('raisonSociale', TextType::class, [
                'label'       => 'Raison sociale',
                'attr'        => ['placeholder' => 'Cabinet Durand & Associes'],
                'constraints' => [new Assert\NotBlank()],
            ])
            ->add('registryNumber', TextType::class, [
                'label'    => 'N° de registre',
                'required' => false,
                'attr'     => ['id' => 'regInput'],
            ])
            ->add('siret', TextType::class, [
                'label'    => 'SIRET',
                'required' => false,
                'attr'     => ['placeholder' => '123 456 789 00012'],
            ])
            ->add('email', EmailType::class, [
                'label'       => 'Email professionnel',
                'attr'        => ['placeholder' => 'contact@cabinet.fr'],
                'constraints' => [new Assert\NotBlank(), new Assert\Email()],
            ])
            ->add('plainPassword', PasswordType::class, [
                'label'       => 'Mot de passe',
                'mapped'      => false,
                'constraints' => [new Assert\NotBlank(), new Assert\Length(['min' => 8])],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => User::class]);
    }
}
