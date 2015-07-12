<?php

namespace Sculpin\Bundle\SculpinBundle\EventListener;

use Sculpin\Bundle\SculpinBundle\HttpKernel\AbstractKernel;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ConsoleListener implements EventSubscriberInterface
{
    private $kernel;

    public function __construct(AbstractKernel $kernel)
    {
        $this->kernel = $kernel;
    }

    public function getMissingSculpinBundlesMessages()
    {
        $messages = array();

        // Display missing bundle to user.
        if ($missingBundles = $this->kernel->getMissingSculpinBundles()) {
            $messages[] = '';
            $messages[] = '<comment>Missing Sculpin Bundles:</comment>';
            foreach ($missingBundles as $bundle) {
                $messages[] = "  * <highlight>$bundle</highlight>";
            }
            $messages[] = '';
        }

        return $messages;
    }

    public function onEvent(ConsoleEvent $event)
    {
        $output = $event->getOutput();

        foreach ($this->getMissingSculpinBundlesMessages() as $message) {
            $output->writeln($message);
        }
    }

    public static function getSubscribedEvents()
    {
        return array(
          ConsoleEvents::COMMAND => 'onEvent',
          ConsoleEvents::EXCEPTION => 'onEvent',
          ConsoleEvents::TERMINATE => 'onEvent',
        );
    }
}
