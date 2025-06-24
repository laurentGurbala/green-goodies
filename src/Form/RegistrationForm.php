<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\NotBlank;

class RegistrationForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add("lastName", options: [
                "label" => "Nom",
                ])
            ->add("firstName", options: [
                "label" => "Prénom",
                ])
            ->add('email')
            ->add('password', RepeatedType::class, [
                "type" => PasswordType::class,
                "required" => true,
                "first_options" => ["label" => "Mot de passe"],
                "second_options" => ["label" => "Confirmation mot de passe"],
                "invalid_message" => "les mots de passe doivent correspondre.",
                "constraints" => [new NotBlank()],
                "mapped" => false
            ])
            ->add('agreeTerms', CheckboxType::class, [
                'mapped' => false,
                'constraints' => [
                    new IsTrue([
                        'message' => 'Vous devez accepter les CGU.',
                    ]),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
