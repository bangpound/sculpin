<?php

/*
 * This file is a part of Sculpin.
 *
 * (c) Dragonfly Development Inc.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sculpin\Bundle\StandaloneBundle\Console;

use Dflydev\EmbeddedComposer\Core\EmbeddedComposer;
use Composer\Factory;
use Dflydev\EmbeddedComposer\Core\EmbeddedComposerAwareInterface;
use Sculpin\Core\Sculpin;
use Dflydev\EmbeddedComposer\Console\Command\DumpAutoloadCommand;
use Dflydev\EmbeddedComposer\Console\Command\InstallCommand;
use Sculpin\Bundle\StandaloneBundle\Command\SelfUpdateCommand;
use Dflydev\EmbeddedComposer\Console\Command\UpdateCommand;
use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Application
 *
 * @author Beau Simensen <beau@dflydev.com>
 */
class Application extends BaseApplication implements EmbeddedComposerAwareInterface
{
    protected $kernel;
    protected $embeddedComposer;
    
    private $commandsRegistered = false;

    /**
     * Constructor.
     *
     * @param KernelInterface  $kernel           A KernelInterface instance
     * @param EmbeddedComposer $embeddedComposer Composer Class Loader
     */
    public function __construct(KernelInterface $kernel, EmbeddedComposer $embeddedComposer)
    {
        $this->kernel = $kernel;
        $this->embeddedComposer = $embeddedComposer;

        if (function_exists('date_default_timezone_set') && function_exists('date_default_timezone_get')) {
            date_default_timezone_set(@date_default_timezone_get());
        }

        $version = $embeddedComposer->findPackage('sculpin/sculpin')->getPrettyVersion();
        if ($version !== Sculpin::GIT_VERSION && Sculpin::GIT_VERSION !== '@'.'git_version'.'@') {
            $version .= ' ('.Sculpin::GIT_VERSION.')';
        }

        parent::__construct('Sculpin', $version.' - '.$kernel->getName().'/'.$kernel->getEnvironment().($kernel->isDebug() ? '/debug' : ''));

        $this->getDefinition()->addOption(new InputOption('--project-dir', null, InputOption::VALUE_REQUIRED, 'The project directory.', '.'));
        $this->getDefinition()->addOption(new InputOption('--env', '-e', InputOption::VALUE_REQUIRED, 'The Environment name.', $kernel->getEnvironment()));
        $this->getDefinition()->addOption(new InputOption('--no-debug', null, InputOption::VALUE_NONE, 'Switches off debug mode.'));
        $this->getDefinition()->addOption(new InputOption('--safe', null, InputOption::VALUE_NONE, 'Enable safe mode (no bundles loaded, no kernel booted)'));
        $this->getDefinition()->addOption(new InputOption('--git-version', null, InputOption::VALUE_NONE, 'See Git version'));
    }

    /**
     * {@inheritdoc}
     */
    public function getEmbeddedComposer()
    {
        return $this->embeddedComposer;
    }

    /**
     * {@inheritDoc}
     */
    public function run(InputInterface $input = null, OutputInterface $output = null)
    {
        if (null === $output) {
            $styles = Factory::createAdditionalStyles();
            $formatter = new OutputFormatter(null, $styles);
            $output = new ConsoleOutput(ConsoleOutput::VERBOSITY_NORMAL, null, $formatter);
        }

        return parent::run($input, $output);
    }

    /**
     * {@inheritdoc}
     */
    public function doRun(InputInterface $input, OutputInterface $output)
    {
        if ($input->hasParameterOption('--git-version')) {
            $output->writeln(Sculpin::GIT_VERSION);

            return;
        }

        if ($input->hasParameterOption('--safe')) {
            // For safe mode we should enable some commands
            // manually because we won't enable any others.
            $this->add(new DumpAutoloadCommand(''));
            $this->add(new InstallCommand(''));
            $this->add(new SelfUpdateCommand(''));
            $this->add(new UpdateCommand(''));
        } else {
            if (!$this->commandsRegistered) {
                $this->registerCommands();

                $this->commandsRegistered = true;
            }

            $container = $this->kernel->getContainer();

            foreach ($this->all() as $command) {
                if ($command instanceof ContainerAwareInterface) {
                    $command->setContainer($container);
                }
            }

            $this->setDispatcher($container->get('event_dispatcher'));
        }

        return parent::doRun($input, $output);
    }

    /**
     * Get Kernel
     *
     * @return Kernel
     */
    public function getKernel()
    {
        return $this->kernel;
    }

    protected function registerCommands()
    {
        $this->kernel->boot();
        $container = $this->kernel->getContainer();

        foreach ($this->kernel->getBundles() as $bundle) {
            if ($bundle instanceof BundleInterface) {
                $bundle->registerCommands($this);
            }
        }

        if ($container->hasParameter('console.command.ids')) {
            foreach ($container->getParameter('console.command.ids') as $id) {
                $this->add($container->get($id));
            }
        }
    }
}
