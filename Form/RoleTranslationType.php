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

use Claroline\CoreBundle\Validator\Constraints\RoleName;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class RoleTranslationType extends AbstractType
{
    private $wsGuid;

    public function __construct($wsGuid = null)
    {
        $this->wsGuid = $wsGuid;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'displayedName',
            'translatable',
            array(
                'data' => $builder->getData(),
                'theme_options' => array(
                    'titlePlaceHolder' => 'tool name',
                    'contentText' => false,
                    'tinymce' => false
                ),
                'label' => 'name'
            )
        );
    }

    public function getName()
    {
        return 'role_name_type_form';
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array('translation_domain' => 'platform'));
    }
}
