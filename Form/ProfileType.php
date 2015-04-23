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

use Claroline\CoreBundle\Entity\Role;
use Claroline\CoreBundle\Repository\RoleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Validator\Constraints\Image;

class ProfileType extends AbstractType
{
    private $platformRoles;
    private $isAdmin;
    private $isGrantedUserAdministration;
    private $langs;
    private $authenticationDrivers;
    private $accesses;

    /**
     * Constructor.
     *
     * @param Role[]   $platformRoles
     * @param boolean  $isAdmin
     * @param string[] $langs
     */
    public function __construct(
        array $platformRoles,
        $isAdmin,
        $isGrantedUserAdministration,
        array $langs,
        $accesses,
        $authenticationDrivers = null
    )
    {
        $this->accesses = $accesses;
        $this->platformRoles = new ArrayCollection($platformRoles);
        $this->isAdmin = $isAdmin;
        $this->isGrantedUserAdministration = $isGrantedUserAdministration;

        if (!empty($langs)) {
            $this->langs = $langs;
        } else {
            $this->langs = array('en' => 'en', 'fr' => 'fr');
        }

        $this->authenticationDrivers = $authenticationDrivers;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder
            ->add('firstName', 'text', array('label' => 'First name', 'read_only' => !$this->accesses['firstName'], 'disabled' => !$this->accesses['firstName']))
            ->add('lastName', 'text', array('label' => 'Last name',  'read_only' => !$this->accesses['lastName'], 'disabled' => !$this->accesses['lastName']))
            ->add('username', 'text', array('read_only' => true, 'disabled' => true, 'label' => 'User name', 'read_only' => !$this->accesses['username'], 'disabled' => !$this->accesses['username']))
            ->add(
                'administrativeCode',
                'text',
                array('required' => false, 'read_only' => !$this->accesses['administrativeCode'], 'disabled' => !$this->accesses['administrativeCode'], 'label' => 'administrative_code')
            )
            ->add('mail', 'email', array('required' => false, 'label' => 'email', 'read_only' => !$this->accesses['email'], 'disabled' => !$this->accesses['email']))
            ->add('phone', 'text', array('required' => false, 'label' => 'phone', 'read_only' => !$this->accesses['phone'], 'disabled' => !$this->accesses['phone']))
            ->add('locale', 'choice', array('choices' => $this->langs, 'required' => false, 'label' => 'Language'))
            ->add(
                'pictureFile',
                'file',
                array(
                    'required' => false,
                    'constraints' => new Image(
                        array(
                            'minWidth'  => 50,
                            'maxWidth'  => 800,
                            'minHeight' => 50,
                            'maxHeight' => 800,
                        )
                    ),
                    'label' => 'picture_profile',
                    'read_only' => !$this->accesses['picture'],
                    'disabled' => !$this->accesses['picture']
                )
            )
            ->add(
                'description',
                'tinymce',
                array('required' => false, 'label' => 'description',  'read_only' => !$this->accesses['description'], 'disabled' => !$this->accesses['description'])
            );

        if ($this->isAdmin || $this->isGrantedUserAdministration) {
            $isAdmin = $this->isAdmin;
            $builder
                ->add('firstName', 'text', array('label' => 'First name'))
                ->add('lastName', 'text', array('label' => 'Last name'))
                ->add('username', 'text', array('label' => 'User name'))
                ->add('administrativeCode', 'text', array('required' => false, 'label' => 'administrative_code'))
                ->add('mail', 'email', array('required' => false, 'label' => 'email'))
                ->add('phone', 'text', array('required' => false, 'label' => 'phone'))
                ->add('locale', 'choice', array('choices' => $this->langs, 'required' => false, 'label' => 'Language'))
                ->add(
                    'authentication',
                    'choice',
                    array(
                        'choices' => $this->authenticationDrivers,
                        'required' => false,
                        'label' => 'authentication'
                    )
                )
                ->add(
                    'platformRoles',
                    'entity',
                    array(
                        'mapped' => false,
                        'data' => $this->platformRoles,
                        'class' => 'Claroline\CoreBundle\Entity\Role',
                        'expanded' => true,
                        'multiple' => true,
                        'property' => 'displayedName',
                        'query_builder' => function (RoleRepository $roleRepository) use ($isAdmin) {
                            $query = $roleRepository->createQueryBuilder('r')
                                    ->where("r.type = " . Role::PLATFORM_ROLE)
                                    ->andWhere("r.name != 'ROLE_ANONYMOUS'")
                                    ->andWhere("r.name != 'ROLE_USER'");
                            if (!$isAdmin) {
                                $query->andWhere("r.name != 'ROLE_ADMIN'");
                            }

                            return $query;
                        },
                        'label' => 'roles'
                    )
                )
                ->add(
                    'pictureFile',
                    'file',
                    array(
                        'required' => false,
                        'constraints' => new Image(
                            array(
                                'minWidth'  => 50,
                                'maxWidth'  => 800,
                                'minHeight' => 50,
                                'maxHeight' => 800,
                            )
                        ),
                        'label' => 'picture_profile'
                    )
                )
                ->add(
                    'description',
                    'tinymce',
                    array('required' => false, 'label' => 'description')
                );
        }
    }

    public function getName()
    {
        return 'profile_form';
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            array(
                'data_class'         => 'Claroline\CoreBundle\Entity\User',
                'translation_domain' => 'platform'
            )
        );
    }
}
