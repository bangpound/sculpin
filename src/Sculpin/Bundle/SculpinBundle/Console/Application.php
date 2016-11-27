<?php

/*
 * This file is a part of Sculpin.
 *
 * (c) Dragonfly Development Inc.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sculpin\Bundle\SculpinBundle\Console;

use Sculpin\Core\Sculpin;
use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Application
 *
 * @author Beau Simensen <beau@dflydev.com>
 */
class Application extends BaseApplication
{
    protected $kernel;
    
    /**
     * Constructor.
     *
     * @param KernelInterface  $kernel           A KernelInterface instance
     */
    public function __construct(KernelInterface $kernel)
    {
        $this->kernel = $kernel;

        if (function_exists('date_default_timezone_set') && function_exists('date_default_timezone_get')) {
            date_default_timezone_set(@date_default_timezone_get());
        }

        $version = ' ('.Sculpin::GIT_VERSION.')';

        parent::__construct('Sculpin', $version.' - '.$kernel->getName().'/'.$kernel->getEnvironment().($kernel->isDebug() ? '/debug' : ''));

        $this->getDefinition()->addOption(new InputOption('--project-dir', null, InputOption::VALUE_REQUIRED, 'The project directory.', '.'));
        $this->getDefinition()->addOption(new InputOption('--env', '-e', InputOption::VALUE_REQUIRED, 'The Environment name.', $kernel->getEnvironment()));
        $this->getDefinition()->addOption(new InputOption('--no-debug', null, InputOption::VALUE_NONE, 'Switches off debug mode.'));
        $this->getDefinition()->addOption(new InputOption('--safe', null, InputOption::VALUE_NONE, 'Enable safe mode (no bundles loaded, no kernel booted)'));
        $this->getDefinition()->addOption(new InputOption('--git-version', null, InputOption::VALUE_NONE, 'See Git version'));
    }

    /**
     * {@inheritDoc}
     */
    public function run(InputInterface $input = null, OutputInterface $output = null)
    {
        if (null === $output) {
            $styles = array(
                'highlight' => new OutputFormatterStyle('red'),
                'warning' => new OutputFormatterStyle('black', 'yellow'),
            );
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
        } else {
            $this->registerCommands();
        }

        parent::doRun($input, $output);

        foreach ($this->getMissingSculpinBundlesMessages() as $message) {
            $output->writeln($message);
        }
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

        foreach ($this->kernel->getBundles() as $bundle) {
            if ($bundle instanceof BundleInterface) {
                $bundle->registerCommands($this);
            }
        }
    }
}
