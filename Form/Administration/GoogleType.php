<?php

/*
 * This file is part of the Claroline Connect package.
 *
 * (c) Claroline Consortium <consortium@claroline.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace Claroline\CoreBundle\Form\Administration;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\NotBlank;

class GoogleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'google_client_id',
                'integer',
                array(
                    'constraints' => array(
                        new NotBlank(),
                        new GreaterThanOrEqual(array('value' => 0))
                    ),
                    'attr' => array('min' => 0),
                    'label' => 'google_client_id'
                )
            )
            ->add(
                'google_client_secret',
                'text',
                array(
                    'constraints' => new NotBlank(),
                    'label' => 'google_client_secret'
                )
            );
    }

    public function getName()
    {
        return 'platform_google_application_form';
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array('translation_domain' => 'platform'));
    }
}
