<?php

namespace App\Form;

use App\Entity\User;
use App\Enum\Occupation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
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
            ->add('profession', EnumType::class, [
                'class' => Occupation::class,
                'label' => 'Profession',
                'choice_label' => fn(Occupation $choice) => $choice->label(),
                'placeholder' => 'Sélectionnez une profession',
                'attr' => ['onchange' => 'adaptReg(this.value)'],
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Veuillez sélectionner votre profession.',
                    ]),
                ],
            ])
            ->add('raisonSociale', TextType::class, [
                'label' => 'Raison sociale',
                'attr' => ['placeholder' => 'Cabinet Durand & Associes'],
                'constraints' => [new Assert\NotBlank([
                    'message' => 'Veuillez renseigner votre raison sociale.'
                ])],
            ])
            ->add('registryNumber', TextType::class, [
                'label' => 'N° de registre',
                'required' => false,
                'attr' => ['id' => 'regInput'],
            ])
            ->add('siret', TextType::class, [
                'label' => 'SIRET',
                'required' => false,
                'attr' => ['placeholder' => '123 456 789 00012'],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email professionnel',
                'attr' => ['placeholder' => 'contact@cabinet.fr'],
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Veuillez renseigner votre email professionnel.'
                    ]),
                    new Assert\Email([
                        'message' => 'Veuillez renseigner un email valide.'
                    ])
                ],
            ])
            ->add('plainPassword', PasswordType::class, [
                'label' => 'Mot de passe',
                'mapped' => false,
                'constraints' => [new Assert\NotBlank([
                    'message' => 'Veuillez renseigner un mot de passe.'
                ]), new Assert\Length([
                    'min' => 8,
                ])],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => User::class]);
    }
}
