<?php

/*
 * This file is part of the Claroline Connect package.
 *
 * (c) Claroline Consortium <consortium@claroline.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Claroline\CoreBundle\Library\Transfert\ConfigurationBuilders\Tools;

use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\Config\Definition\Processor;
use Claroline\CoreBundle\Library\Transfert\Importer;
use Claroline\CoreBundle\Library\Transfert\RichTextInterface;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Claroline\CoreBundle\Manager\RightsManager;
use Claroline\CoreBundle\Manager\ResourceManager;
use Claroline\CoreBundle\Manager\RoleManager;
use Claroline\CoreBundle\Manager\MaskManager;
use Claroline\CoreBundle\Persistence\ObjectManager;
use Claroline\CoreBundle\Entity\Resource\Directory;
use Claroline\CoreBundle\Entity\Resource\ResourceNode;
use Claroline\CoreBundle\Entity\Workspace\Workspace;
use Claroline\CoreBundle\Entity\Role;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @DI\Service("claroline.tool.resource_manager_importer")
 * @DI\Tag("claroline.importer")
 */
class ResourceManagerImporter extends Importer implements ConfigurationInterface, RichTextInterface
{
    private $result;
    private $data;
    private $rightManager;
    private $resourceManager;
    private $roleManager;
    private $maskManager;
    private $availableParents;
    private $om;
    private $availableCreators;
    private $env;

    /**
     * @DI\InjectParams({
     *     "rightManager"    = @DI\Inject("claroline.manager.rights_manager"),
     *     "maskManager"     = @DI\Inject("claroline.manager.mask_manager"),
     *     "resourceManager" = @DI\Inject("claroline.manager.resource_manager"),
     *     "roleManager"     = @DI\Inject("claroline.manager.role_manager"),
     *     "om"              = @DI\Inject("claroline.persistence.object_manager"),
     *     "container"       = @DI\Inject("service_container")
     * })
     */
    public function __construct(
        RightsManager $rightManager,
        MaskManager $maskManager,
        ResourceManager $resourceManager,
        RoleManager $roleManager,
        ObjectManager $om,
        ContainerInterface $container
    )
    {
        $this->rightManager    = $rightManager;
        $this->resourceManager = $resourceManager;
        $this->maskManager     = $maskManager;
        $this->roleManager     = $roleManager;
        $this->om              = $om;
        $this->container       = $container;
        $this->env             = $container->get('kernel')->getEnvironment();
    }

    public function  getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('data');
        $this->addResourceSection($rootNode);

        return $treeBuilder;
    }

    public function supports($type)
    {
        return $type == 'yml' ? true: false;
    }

    public function validate(array $data)
    {
        $this->setData($data);
        $processor = new Processor();
        $this->result = $processor->processConfiguration($this, $data);

        if (isset($data['data']['items'])) {
            foreach ($data['data']['items'] as $item) {
                $importer = $this->getImporterByName($item['item']['type']);

                if (!$importer && $this->env === 'dev') {
                    //throw new InvalidConfigurationException('The importer ' . $item['item']['type'] . ' does not exist');
                }

                if ($importer && isset($item['item']['data'])) {
                    $importer->validate(['data' => $item['item']['data']]);
                }
            }
        }
    }

    public function import(array $data, $workspace, $entityRoles, Directory $root)
    {
        /*
         * Each directory is created without parent.
         * The parent is set after the ResourceManager::create method is fired.
         * When there is no parent and no right array, the resource creation will copy
         * the parent rights (ROLE_USER and ROLE_ANONYMOUS) and we only need to add the roles from the $data
         * instead of the full array with default perms.
         * The implementation will change later (if we need to change the perms of
         * ROLE_USER and ROLE_ANONYMOUS) but it's easier to code it that way.
         */

        $createdResources = array();
        $directories[$data['data']['root']['uid']] = $root;
        $resourceNodes = [];

        /*************************/
        /* WORKSPACE DIRECTORIES */
        /*************************/

        if (isset($data['data']['directories'])) {
            //build the nodes
            foreach ($data['data']['directories'] as $directory) {
                $directoryEntity = new Directory();
                $directoryEntity->setName($directory['directory']['name']);

                if ($directory['directory']['creator']) {
                    $owner = $this->om
                        ->getRepository('ClarolineCoreBundle:User')
                        ->findOneByUsername($directory['directory']['creator']);
                } else {
                    $owner = $this->getOwner();
                }

                $directories[$directory['directory']['uid']] = $this->resourceManager->create(
                    $directoryEntity,
                    $this->om->getRepository('Claroline\CoreBundle\Entity\Resource\ResourceType')->findOneByName('directory'),
                    $owner,
                    $workspace,
                    null,
                    null,
                    array()
                );

                //add the missing roles
                foreach ($directory['directory']['roles'] as $role) {
                    $this->setPermissions($role, $entityRoles[$role['role']['name']], $directoryEntity);
                }
            }

            //set the correct parent
            foreach ($data['data']['directories'] as $directory) {
                $node = $directories[$directory['directory']['uid']]->getResourceNode();
                $node->setParent($directories[$directory['directory']['parent']]->getResourceNode());
                $this->om->persist($node);
            }
        }

        /*************/
        /* RESOURCES */
        /*************/

        if (isset($data['data']['items'])) {
            foreach ($data['data']['items'] as $item) {
                $res = array();
                if (isset($item['item']['data'])) $res['data'] = $item['item']['data'];
                //get the entity from an importer
                $importer = $this->getImporterByName($item['item']['type']);

                if ($importer) {
                    $entity = $importer->import($res, $item['item']['name']);
                    //some importers are not fully functionnal yet
                    if ($entity) {
                        $entity->setName($item['item']['name']);
                        $type = $this->om
                            ->getRepository('Claroline\CoreBundle\Entity\Resource\ResourceType')
                            ->findOneByName($item['item']['type']);

                        if ($item['item']['creator']) {
                            $owner = $this->om
                                ->getRepository('ClarolineCoreBundle:User')
                                ->findOneByUsername($item['item']['creator']);
                        } else {
                            $owner = $this->getOwner();
                        }

                        $entity = $this->resourceManager->create(
                            $entity,
                            $type,
                            $owner,
                            $workspace,
                            null,
                            null,
                            array()
                        );

                        $entity->getResourceNode()->setParent($directories[$item['item']['parent']]->getResourceNode());
                        $this->om->persist($entity);

                        //add the missing roles
                        if (isset($item['item']['roles'])) {
                            foreach ($item['item']['roles'] as $role) {
                                $this->setPermissions($role, $entityRoles[$role['role']['name']], $entity);
                            }
                        }

                        $resourceNodes[$item['item']['uid']] = $entity;
                    }
                }
            }
        }

        /***************/
        /* ROOT RIGHTS */
        /***************/

        //add the missing roles
        foreach ($data['data']['root']['roles'] as $role) {
            $this->setPermissions($role, $entityRoles[$role['role']['name']], $root);
        }

        //We need to force the flush in order to add the rich text.
        //$this->om->forceFlush();

        /*************/
        /* RICH TEXT */
        /*************/

        /*
        if (isset($data['data']['items'])) {
            foreach ($data['data']['items'] as $item) {
                if (isset ($item['item']['is_rich'])) {
                    if ($item['item']['is_rich']) {
                        $this->getImporterByName($item['item']['type'])
                            ->format($res, array('directories' => $directories, 'items' => $resourceNodes));
                    }
                }
            }
        }*/
    }

    public function importResources(array $data, $workspace, ResourceNode $root)
    {
        $directories = array();
        $resourceNodes = array();

        /*************************/
        /* WORKSPACE DIRECTORIES */
        /*************************/

        if (isset($data['data']['directories'])) {
            //build the nodes
            foreach ($data['data']['directories'] as $directory) {
                $directoryEntity = new Directory();
                $directoryEntity->setName($directory['directory']['name']);

                if ($directory['directory']['creator']) {
                    $owner = $this->om
                        ->getRepository('ClarolineCoreBundle:User')
                        ->findOneByUsername($directory['directory']['creator']);
                } else {
                    $owner = $this->getOwner();
                }

                $directories[$directory['directory']['uid']] = $this->resourceManager->create(
                    $directoryEntity,
                    $this->om->getRepository('Claroline\CoreBundle\Entity\Resource\ResourceType')->findOneByName('directory'),
                    $owner,
                    $workspace,
                    null,
                    null,
                    array(),
                    true,
                    false
                );
            }

            //set the correct parent
            foreach ($data['data']['directories'] as $directory) {
                $node = $directories[$directory['directory']['uid']]->getResourceNode();

                if ($directory['directory']['parent'] && isset($directories[$directory['directory']['parent']])) {
                    $node->setParent($directories[$directory['directory']['parent']]->getResourceNode());
                } else {
                    $node->setParent($root);
                }
                $this->resourceManager->setRights($node, $root);
                $this->om->persist($node);
            }
        }

        /*************/
        /* RESOURCES */
        /*************/

        if (isset($data['data']['items'])) {

            foreach ($data['data']['items'] as $item) {
                $res = array();

                if (isset($item['item']['data'])) {
                    $res['data'] = $item['item']['data'];
                }
                //get the entity from an importer
                $importer = $this->getImporterByName($item['item']['type']);

                if ($importer) {
                    $entity = $importer->import($res, $item['item']['name']);
                    //some importers are not fully functionnal yet
                    if ($entity) {
                        $entity->setName($item['item']['name']);
                        $type = $this->om
                            ->getRepository('Claroline\CoreBundle\Entity\Resource\ResourceType')
                            ->findOneByName($item['item']['type']);

                        if ($item['item']['creator']) {
                            $owner = $this->om
                                ->getRepository('ClarolineCoreBundle:User')
                                ->findOneByUsername($item['item']['creator']);
                        } else {
                            $owner = $this->getOwner();
                        }

                        $entity = $this->resourceManager->create(
                            $entity,
                            $type,
                            $owner,
                            $workspace,
                            null,
                            null,
                            array(),
                            true,
                            false
                        );


                        if ($item['item']['parent'] && isset($directories[$item['item']['parent']])) {
                            $entity->getResourceNode()->setParent($directories[$item['item']['parent']]->getResourceNode());
                        } else {
                            $entity->getResourceNode()->setParent($root);
                        }
                        $this->resourceManager->setRights($entity->getResourceNode(), $root);
                        $this->om->persist($entity);

                        $resourceNodes[$item['item']['uid']] = $entity;
                    }
                }
            }
        }
    }

    public function export(Workspace $workspace, array &$files, $object)
    {
        $data = [];
        //first we get the root
        $root = $this->resourceManager->getWorkspaceRoot($workspace);
        $rootRights = $root->getRights();
        $data['root'] = array(
            'uid'   => $root->getId(),
            'roles' => $this->getPermsArray($root)
        );
        $directory = $this->resourceManager->getResourceTypeByName('directory');
        $resourceNodes = $this->resourceManager->getByWorkspaceAndResourceType($workspace, $directory);

        foreach ($resourceNodes as $resourceNode) {
            if ($resourceNode->getParent() !== null) {
                $data['directories'][] = array('directory' => array(
                    'name'    => $resourceNode->getName(),
                    'creator' => null,
                    'parent'  => $resourceNode->getParent()->getId(),
                    'uid'     => $resourceNode->getId(),
                    'roles'   => $this->getPermsArray($resourceNode)
                ));
            }
        }

        foreach ($resourceNodes as $resourceNode) {
            $children = $resourceNode->getChildren();

            foreach ($children as $child) {
                if ($child->getResourceType()->getName() !== 'directory') {
                    $importer = $this->getImporterByName($child->getResourceType()->getName());
                    $childData = array();

                    if ($importer) {
                        $childData = $importer->export(
                            $workspace,
                            $files,
                            $this->resourceManager->getResourceFromNode($child)
                        );
                    }

                    $data['items'][] = array('item' => array(
                        'name'    => $child->getName(),
                        'creator' => null,
                        'parent'  => $resourceNode->getId(),
                        'type'    => $child->getResourceType()->getName(),
                        'roles'   => $this->getPermsArray($child),
                        'uid'     => $child->getId(),
                        'data'    => $childData
                    ));
                }
            }
        }

        return $data;
    }

    public function exportResources(Workspace $workspace, array $resourceNodes, array &$files)
    {
        $data = array();

        foreach ($resourceNodes as $resourceNode) {
            $resourceTypeName = $resourceNode->getResourceType()->getName();

            if ($resourceTypeName === 'directory') {
                $data['directories'][] = array(
                    'directory' => array(
                        'name'    => $resourceNode->getName(),
                        'creator' => null,
                        'parent'  => null,
                        'uid'     => $resourceNode->getId(),
                        'roles'   => $this->getPermsArray($resourceNode)
                    )
                );
                $this->exportChildrenResources(
                    $workspace,
                    $resourceNode->getChildren()->toArray(),
                    $files,
                    $data,
                    $resourceNode->getId()
                );
            } else {
                $nodeData = array();
                $importer = $this->getImporterByName($resourceTypeName);

                if ($importer) {
                    $nodeData = $importer->export(
                        $workspace,
                        $files,
                        $this->resourceManager->getResourceFromNode($resourceNode)
                    );
                }

                $data['items'][] = array(
                    'item' => array(
                        'name'    => $resourceNode->getName(),
                        'creator' => null,
                        'parent'  => null,
                        'type'    => $resourceTypeName,
                        'roles'   => $this->getPermsArray($resourceNode),
                        'uid'     => $resourceNode->getId(),
                        'data'    => $nodeData
                    )
                );
            }
        }

        return $data;
    }

    private function exportChildrenResources(
        Workspace $workspace,
        array $children,
        array &$files,
        array &$data,
        $parentId
    )
    {
        foreach ($children as $child) {
            $resourceTypeName = $child->getResourceType()->getName();

            if ($resourceTypeName === 'directory') {
                $data['directories'][] = array(
                    'directory' => array(
                        'name'    => $child->getName(),
                        'creator' => null,
                        'parent'  => $parentId,
                        'uid'     => $child->getId(),
                        'roles'   => $this->getPermsArray($child)
                    )
                );
                $this->exportChildrenResources(
                    $workspace,
                    $child->getChildren()->toArray(),
                    $files,
                    $data,
                    $child->getId()
                );
            } else {
                $childData = array();
                $importer = $this->getImporterByName($resourceTypeName);

                if ($importer) {
                    $childData = $importer->export(
                        $workspace,
                        $files,
                        $this->resourceManager->getResourceFromNode($child)
                    );
                }

                $data['items'][] = array(
                    'item' => array(
                        'name'    => $child->getName(),
                        'creator' => null,
                        'parent'  => $parentId,
                        'type'    => $resourceTypeName,
                        'roles'   => $this->getPermsArray($child),
                        'uid'     => $child->getId(),
                        'data'    => $childData
                    )
                );
            }
        }
    }

    public function getName()
    {
        return 'resource_manager';
    }

    public function addResourceSection($rootNode)
    {
        $availableRoleName = [];
        $configuration = $this->getConfiguration();
        $data = $this->getData();

        if (isset($configuration['roles'])) {
            foreach ($configuration['roles'] as $role) {
                $availableRoleName[] = $role['role']['name'];
            }
        }

        $existingBaseRoles = $this->roleManager->getAllPlatformRoles();

        //adding platform roles
        foreach ($existingBaseRoles as $existingBaseRole) {
            $availableRoleName[] = $existingBaseRole->getName();
        }

        //adding ROLE_ANONYMOUS
        $availableRoleName[] = 'ROLE_ANONYMOUS';
        $this->availableParents = [];

        if (isset($data['data']['directories'])) {
            foreach ($data['data']['directories'] as $directory) {
                $this->availableParents[] = $directory['directory']['uid'];
            }
        }

        if (isset($data['data']['root'])) {
            $this->availableParents[] = $data['data']['root']['uid'];
        }

        $availableParents = $this->availableParents;

        $this->availableCreators = [];

        if (isset($data['data']['members'])) {
            if (isset($data['data']['members']['users'])) {
                foreach ($data['data']['members']['users'] as $user) {
                    $this->availableCreators[] = $user['user']['username'];
                }
            }

            if (isset($data['data']['members']['owner'])) {
                //do something
            }
        }

        $users = $this->om->getRepository('ClarolineCoreBundle:User')->findAll();

        foreach ($users as $user) {
            $this->availableCreators[] = $user->getUsername();
        }

        $availableCreators = $this->availableCreators;

        $rootNode
            ->children()
                ->arrayNode('root')->isRequired()
                    ->children()
                        ->scalarNode('uid')->isRequired()->end()
                        ->arrayNode('roles')
                            ->prototype('array')
                                ->children()
                                    ->arrayNode('role')
                                        ->children()
                                            ->scalarNode('name')
                                                ->validate()
                                                    ->ifTrue(
                                                        function ($v) use ($availableRoleName) {
                                                            return call_user_func_array(
                                                                __CLASS__ . '::roleNameExists',
                                                                array($v, $availableRoleName)
                                                            );
                                                        }
                                                    )
                                                    ->thenInvalid("The role name %s doesn't exists")
                                                ->end()
                                            ->end()
                                            ->variableNode('rights')->end()
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('directories')
                    ->prototype('array')
                        ->children()
                            ->arrayNode('directory')
                                ->children()
                                    ->scalarNode('name')->isRequired()->end()
                                    ->scalarNode('uid')->isRequired()->end()
                                    ->scalarNode('creator')->isRequired()
                                        ->validate()
                                            ->ifTrue(
                                                function ($v) use ($availableCreators) {
                                                    return call_user_func_array(
                                                        __CLASS__ . '::creatorExists',
                                                        array($v, $availableCreators)
                                                    );
                                                }
                                            )
                                            ->thenInvalid("The creator username %s doesn't exists")
                                        ->end()
                                    ->end()
                                    ->scalarNode('parent')->isRequired()
                                        ->validate()
                                            ->ifTrue(
                                                function ($v) use ($availableParents) {
                                                    return call_user_func_array(
                                                        __CLASS__ . '::parentExists',
                                                        array($v, $availableParents)
                                                    );
                                                }
                                            )
                                            ->thenInvalid("The parent name %s doesn't exists")
                                        ->end()
                                    ->end()
                                    ->arrayNode('roles')
                                        ->prototype('array')
                                            ->children()
                                                ->arrayNode('role')
                                                    ->children()
                                                        ->scalarNode('name')
                                                            ->validate()
                                                                ->ifTrue(
                                                                    function ($v) use ($availableRoleName) {
                                                                        return call_user_func_array(
                                                                            __CLASS__ . '::roleNameExists',
                                                                            array($v, $availableRoleName)
                                                                        );
                                                                    }
                                                                )
                                                                ->thenInvalid("The role name %s doesn't exists")
                                                            ->end()
                                                        ->end()
                                                        ->variableNode('rights')->end()
                                                    ->end()
                                                ->end()
                                            ->end()
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('items')
                    ->prototype('array')
                        ->children()
                            ->arrayNode('item')
                                ->children()
                                    ->scalarNode('name')->end()
                                    ->scalarNode('creator')->end()
                                    ->scalarNode('uid')->end()
                                    ->booleanNode('is_rich')->defaultFalse()->end()
                                    ->scalarNode('parent')
                                        ->validate()
                                        ->ifTrue(
                                            function ($v) use ($availableParents) {
                                                return call_user_func_array(
                                                    __CLASS__ . '::parentExists',
                                                    array($v, $availableParents)
                                                );
                                            }
                                        )
                                        ->thenInvalid("The parent uid %s doesn't exists")
                                        ->end()
                                    ->end()
                                    ->scalarNode('type')->end()
                                    ->variableNode('data')->end()
                                    ->arrayNode('import')
                                        ->prototype('array')
                                            ->children()
                                                ->scalarNode('path')->end()
                                            ->end()
                                        ->end()
                                    ->end()
                                    ->arrayNode('roles')
                                        ->prototype('array')
                                            ->children()
                                                ->arrayNode('role')
                                                    ->children()
                                                        ->scalarNode('name')
                                                            ->validate()
                                                                ->ifTrue(
                                                                    function ($v) use ($availableRoleName) {
                                                                        return call_user_func_array(
                                                                            __CLASS__ . '::roleNameExists',
                                                                            array($v, $availableRoleName)
                                                                        );
                                                                    }
                                                                )
                                                                ->thenInvalid("The role name %s doesn't exists")
                                                            ->end()
                                                        ->end()
                                                        ->variableNode('rights')->end()
                                                    ->end()
                                                ->end()
                                            ->end()
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }

    public static function roleNameExists($v, $roles)
    {
        return !in_array($v, $roles);
    }

    public static function parentExists($v, $parents)
    {
        return !in_array($v, $parents);
    }

    public static function creatorExists($v, $creators)
    {
        if ($v === null) {
            return false;
        }

        return !in_array($v, $creators);
    }

    public function setData($data)
    {
        $this->data = $data;
    }

    public function getData()
    {
        return $this->data;
    }

    private function getCreationRightsArray ($rights)
    {
        $creations = array();

        if ($rights !== null) {

            foreach ($rights as $el) {
                $creations[] = $this->om->getRepository('ClarolineCoreBundle:Resource\ResourceType')
                    ->findOneByName($el['name']);
            }
        }

        return $creations;
    }

    private function getPermsArray(ResourceNode $node)
    {
        $rights = $node->getRights();
        $roles = [];

        foreach ($rights as $right) {
            $perms = $this->maskManager->decodeMask($right->getMask(), $node->getResourceType());

            //we only keep workspace in the current workspace and platform roles
            if ($right->getRole()->getWorkspace() === $node->getWorkspace() /*|| $right->getRole()->getWorkspace() === null*/) {

                //creation rights are missing here but w/e
                $name = $this->roleManager->getWorkspaceRoleBaseName($right->getRole());

                $data = array(
                    'name' => $name,
                    'rights' => $perms
                );

                //don't keep the role manager
                if (!strpos('_' . $name, 'ROLE_WS_MANAGER')) $roles[] = array('role' => $data);
            }
        }

        return $roles;
    }

    private function setPermissions(array $role, Role $entityRole, $resourceEntity)
    {
        $creations = (isset($role['role']['rights']['create'])) ?
            $this->getCreationRightsArray($role['role']['rights']['create']):
            array();

        $uow = $this->om->getUnitOfWork();
        $map = $uow->getIdentityMap();

        $createdRights = $this->rightManager->getRightsFromIdentityMap(
            $role['role']['name'],
            $resourceEntity->getResourceNode()
        );
        //There is no ResourceRight in the IdentityMap so we must create it
        if ($createdRights === null) {
            $this->rightManager->create(
                $role['role']['rights'],
                $entityRole,
                $resourceEntity->getResourceNode(),
                false,
                $creations
            );
            //We use the ResourceRight from the IdentityMap
        } else {
            $createdRights->setMask($this->maskManager->encodeMask(
                    $role['role']['rights'],
                    $createdRights->getResourceNode()->getResourceType())
            );
        }
    }

    public function format($data)
    {
        foreach ($data['data']['items'] as $item) {
            foreach ($this->getListImporters() as $importer) {
                if ($importer->getName() == $item['item']['type']) {
                    $resourceImporter = $importer;
                }
            }

            if ($resourceImporter instanceof RichTextInterface) {
                if (isset($item['item']['data']) && $resourceImporter) {
                    $itemData = $item['item']['data'];
                    $resourceImporter->format($itemData);
                }
            }
        }
    }
}
