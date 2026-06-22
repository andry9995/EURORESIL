<?php

namespace App\Form;

use App\Entity\Cancellation;
use App\Enum\ContractType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Form\CallbackTransformer;

class CancellationContractType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('contractType', HiddenType::class, [
                'constraints' => [new NotBlank(message: 'Please select a contract type.')]
            ])
            ;

        $builder->get('contractType')->addModelTransformer(new CallbackTransformer(
            function (?ContractType $enum): string {
                return $enum ? $enum->value : '';
            },
            function (?string $string): ?ContractType {
                return $string ? ContractType::from($string) : null;
            }
        ));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Cancellation::class,
        ]);
    }
}