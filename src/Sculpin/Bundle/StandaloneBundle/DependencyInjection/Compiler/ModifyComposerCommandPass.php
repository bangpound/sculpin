<?php

namespace Sculpin\Bundle\StandaloneBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ModifyComposerCommandPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $ids = array(
          'sculpin.command.dump_autoload',
          'sculpin.command.install',
          'sculpin.command.update',
        );
        foreach ($ids as $id) {
            $definition = $container->getDefinition($id);
            $definition->replaceArgument(0, '');
        }

        $definition = $container->getDefinition('sculpin.command.generate');
        $definition->replaceArgument(0, 'generate');

        $definition = $container->getDefinition('sculpin.command.serve');
        $definition->replaceArgument(0, 'serve');
    }
}
