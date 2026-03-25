<?php

namespace App\Form;

use App\Entity\Candidacy;
use App\Entity\Offer;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class CandidacyType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('message')
            ->add('file', FileType::class, [
                'mapped' => false,
                'constraints' => [
                    new Assert\File(
                        maxSize: '5M',
                        mimeTypes: ['application/pdf'],
                        mimeTypesMessage: 'Only PDF files are allowed'
                    )
                ]
            ])
            ->add('offer', EntityType::class, [
                'class' => Offer::class,
                'choice_label' => 'title',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Candidacy::class,
        ]);
    }
}
