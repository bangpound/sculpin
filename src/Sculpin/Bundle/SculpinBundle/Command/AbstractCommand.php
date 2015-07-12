<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sculpin\Bundle\SculpinBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Command.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
abstract class AbstractCommand extends Command implements ContainerAwareInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @return ContainerInterface
     */
    protected function getContainer()
    {
        return $this->container;
    }

    /**
     * Set Container.
     *
     * @param ContainerInterface $container Container
     *
     * @see ContainerAwareInterface::setContainer()
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }
}
