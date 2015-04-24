<?php
/*
 * This file is part of the Claroline Connect package.
 *
 * (c) Claroline Consortium <consortium@claroline.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Claroline\CoreBundle\Library\Installation\Updater;

use Claroline\InstallationBundle\Updater\Updater;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;

class Updater050000 extends Updater
{
    private $container;
    private $om;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->om = $container->get('claroline.persistence.object_manager');
    }

    public function postUpdate()
    {
        $this->moveTemplate();
    }

    public function moveTemplate()
    {
        $this->log('Moving template directory...');
        $fs = new FileSystem();
        $fs->rename(
            $this->container->getParameter('kernel.root_dir') . '/../templates',
            $this->container->getParameter('claroline.param.templates_directory')
        );
    }
}
