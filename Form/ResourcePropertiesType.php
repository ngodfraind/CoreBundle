<?php

/*
 * This file is part of the Claroline Connect package.
 *
 * (c) Claroline Consortium <consortium@claroline.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Claroline\CoreBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class ResourcePropertiesType extends AbstractType
{
    private $creator;

    public function __construct($creator)
    {
        $this->creator = $creator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('name', 'text', array('label' => 'name'));
        $builder->add(
            'newIcon',
            'file',
            array(
                'required' => false,
                'mapped' => false,
                'label' => 'icon'
            )
        );
        $builder->add(
            'creationDate',
            'date',
            array(
                'disabled' => true,
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd',
                'label' => 'creation_date'
            )
        );
        $builder->add(
            'published',
            'checkbox',
            array( 'required' => true, 'label' => 'published')
        );
        $builder->add(
            'accessibleFrom',
            'date',
            array(
                'required' => false,
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd',
                'label' => 'accessible_from'
            )
        );
        $builder->add(
            'accessibleUntil',
            'date',
            array(
                'required' => false,
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd',
                'label' => 'accessible_until'
            )
        );
        $builder->add(
            'resourceType',
            'entity',
            array(
                'class' => 'Claroline\CoreBundle\Entity\Resource\ResourceType',
                'choice_translation_domain' => true,
                'expanded' => false,
                'multiple' => false,
                'property' => 'name',
                'disabled' => true,
                'label' => 'resource_type'
            )
        );
        $builder->add(
            'creator',
            'text',
            array(
                'data' => $this->creator,
                'mapped' => false,
                'disabled' => true,
                'label' => 'creator'
            )
        );
        $builder->add(
            'license',
            'text',
            array(
                'label' => 'license',
                'required' => false
            )
        );
        $builder->add(
            'author',
            'text',
            array(
                'label' => 'author',
                'required' => false
            )
        );
    }

    public function getName()
    {
        return 'resource_properties_form';
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array('translation_domain' => 'platform'));
    }
}
