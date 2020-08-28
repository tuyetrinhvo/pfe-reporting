<?php
/**
 * Created by PhpStorm.
 * User: trinh
 * Date: 18/02/2020
 */

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PeriodReportingType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     * @throws \Exception
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('begin', DateType::class, [
                'placeholder' => '',
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'js-datepicker form-control',
                    "autocomplete" => "off"
                ],
                'required' => false,
                'label' => false,
                'html5' => false,
                'format' => 'dd/MM/yyyy',
            ])
            ->add('end', DateType::class, [
                'placeholder' => '',
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'js-datepicker form-control',
                    "autocomplete" => "off"
                ],
                'required' => false,
                'label' => false,
                'html5' => false,
                'format' => 'dd/MM/yyyy'
            ])
            ->add('choice', ChoiceType::class, [
                'choices' => [
                    'Avec la date de fermeture' => false,
                    'Avec la date de création' => true,
                ],
                'expanded' => true,
                'multiple' => false,
                'label' => false,
                'required' => true,
            ])
            ->add('period', ChoiceType::class, [
                'choices' => [
                    'entre' => 'custom_range',
                    'aujourd\'hui' => 'today',
                    'hier' => 'yesterday',
                    'cette semaine' => 'this_week',
                    'semaine dernière' => 'last_week',
                    'ce mois' => 'this_month',
                    'mois dernier' => 'last_month',
                    'trois derniers mois' => 'last_three_months',
                    'six derniers mois' => 'last_six_months',
                    'année dernière' => 'last_year',
                ],
                'label' => 'Période : ',
                'attr' => [
                    'class' => 'form-control',
                ],
                'required' => true,
                'multiple' => false
            ])
            ->add('report', SubmitType::class)
            ->add('export', SubmitType::class)
            ->add('saveSearch', SubmitType::class);

    }

    public function configureOptions(OptionsResolver $resolver)
    {

    }
}