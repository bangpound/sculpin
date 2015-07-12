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

        $definition = $container->getDefinition('sculpin.command.self_update');
        $definition->replaceArgument(0, 'self-update');
        if ($definition->hasMethodCall('setAliases')) {
            $calls = $definition->getMethodCalls();
            $definition->removeMethodCall('setAliases');

            foreach ($calls as $call) {
                if ($call[0] === 'setAliases') {
                    foreach ($call[1] as $aliases) {
                        $newAliases = array();
                        foreach ($aliases as $alias) {
                            $newAliases[] = substr($alias, 8);
                        }
                        $definition->addMethodCall('setAliases', array($newAliases));
                    }
                }
            }
        }

        $definition = $container->getDefinition('sculpin.command.serve');
        $definition->replaceArgument(0, 'serve');
    }
}
