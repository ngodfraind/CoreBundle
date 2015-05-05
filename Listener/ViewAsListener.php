<?php

/*
 * This file is part of the Claroline Connect package.
 *
 * (c) Claroline Consortium <consortium@claroline.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Claroline\CoreBundle\Listener;

use Doctrine\ORM\EntityManager;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use JMS\DiExtraBundle\Annotation as DI;
use Claroline\CoreBundle\Library\Security\Token\ViewAsToken;
use Claroline\CoreBundle\Manager\RoleManager;

/**
 * @DI\Service
 */
class ViewAsListener
{
    private $tokenStorage;
    private $authorization;
    private $roleManager;

    /**
     * @DI\InjectParams({
     *     "authorization"   = @DI\Inject("security.authorization_checker"),
     *     "tokenStorage"    = @DI\Inject("security.token_storage"),
     *     "em"             = @DI\Inject("doctrine.orm.entity_manager"),
     *     "roleManager"    = @DI\Inject("claroline.manager.role_manager")
     * })
     *
     * @param SecurityContextInterface $context
     */
    public function __construct(
        TokenStorageInterface $tokenStorage,
        AuthorizationCheckerInterface $authorization,
        EntityManager $em,
        RoleManager $roleManager
    )
    {
        $this->tokenStorage = $tokenStorage;
        $this->authorization = $authorization;
        $this->em = $em;
        $this->roleManager = $roleManager;
    }

    /**
     * @DI\Observe("kernel.request")
     */
    public function onViewAs(GetResponseEvent $event)
    {
        $request = $event->getRequest();
        $attributes = $request->query->all();

        if (array_key_exists('view_as', $attributes)) {
            $user = $this->tokenStorage->getToken()->getUser();
            $viewAs = $attributes['view_as'];
            if ($viewAs === 'exit') {
                if ($this->authorization->isGranted('ROLE_USURPATE_WORKSPACE_ROLE')) {
                    $token = new UsernamePasswordToken($user, null, 'main', $user->getRoles());
                    $this->tokenStorage->setToken($token);
                }
            } else {
                $guid = substr($viewAs, strripos($viewAs, '_') + 1);
                $baseRole = substr($viewAs, 0, strripos($viewAs, '_'));

                if ($this->authorization->isGranted('ROLE_WS_MANAGER_'.$guid)) {
                    if ($baseRole === 'ROLE_ANONYMOUS') {
                        throw new \Exception('No implementation yet');
                    } else {
                        $role = $this->roleManager->getRoleByName($viewAs);

                        if ($role === null) {
                            throw new \Exception("The role {$viewAs} does not exists");
                        }

                        $token = new ViewAsToken(array('ROLE_USER', $viewAs, 'ROLE_USURPATE_WORKSPACE_ROLE'));
                        $token->setUser($user);
                        $this->tokenStorage->setToken($token);
                    }
                } else {
                    throw new AccessDeniedException();
                }
            }
        }
    }
}
