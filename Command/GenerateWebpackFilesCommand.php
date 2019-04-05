<?php
/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace Gie\EzReactStyledBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Bundle\TwigBundle\TwigEngine;
use Twig\Error\Error as TwigError;

class GenerateWebpackFilesCommand extends Command
{
    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * @var TwigEngine
     */
    protected $templating;

    /**
     * @var array
     */
    protected $config;

    /**
     * @var string
     */
    protected $projectDir;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var Finder
     */
    protected $finder;

    /**
     * GenerateWebpackFilesCommand constructor.
     * @param TwigEngine $templating
     * @param Filesystem $filesystem
     * @param array $config
     * @param string $projectDir
     */
    public function __construct(
        TwigEngine $templating,
        Filesystem $filesystem,
        array $config,
        string $projectDir
    ) {
        $this->templating = $templating;
        $this->filesystem = $filesystem;
        $this->config = $config;
        $this->projectDir = $projectDir;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('ez-react:webpack')
            ->setDescription("Generates the file for EzReactStyledBundle");
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void|null
     * @throws TwigError
     */
    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $this->output = $output;

        $this->createReactOnRailsEntryPoints();
        $this->createWebpackConfigs();
    }

    /**
     * @throws TwigError
     */
    private function createReactOnRailsEntryPoints(): void
    {
        $railsComponentsPath = $this->config['export_dir'] . $this->config['rails_components_dir'] . '/';

        $this->filesystem->remove($railsComponentsPath);

        foreach ($this->config['components'] as $name => $path) {
            $realPath = $this->getComponentPath($name, $path);
            $content = $this->templating->render(
                '@EzReactStyled/react-on-rails.register.js.twig',
                [
                    'componentName' => $name,
                    'componentPath' => $realPath
                ]
            );

            $this->filesystem->dumpFile($railsComponentsPath . $name . '.js', $content);
            $this->output->writeln("Component '$name' registered.");
        }
    }

    /**
     * @throws TwigError
     */
    private function createWebpackConfigs(): void
    {
        if ($this->config['auto_webpack_config']) {
            foreach (['client', 'server'] as $side) {
                $this->createWebpackConfig($side);
            }
        } else {
            $this->output->writeln("[auto_webpack_config] option disabled. SKIP.");
        }
    }

    /**
     * @param string $side
     * @throws TwigError
     */
    public function createWebpackConfig(string $side): void
    {
        $webpackConfigPath = $this->config['export_dir'] . $this->config['webpack_config_dir'] . '/';
        $railsComponentsPath = $this->config['export_dir'] . $this->config['rails_components_dir'];
        $serverBundleDir = $this->config['export_dir'] . $this->config['server_bundle_dir'];

        $finder = new Finder();
        $reactOnRailsRegisteredFiles = iterator_to_array(
            $finder
                ->in($railsComponentsPath)
                ->name('*.js')
                ->files()
                ->getIterator()
        );

        $content = $this->templating->render(
            '@EzReactStyled/webpack.config.' . $side . '.js.twig',
            [
                'serverBundleDir' => $serverBundleDir,
                'serverBundleName' => $this->config['server_bundle_name'],
                'entryPointName' => $this->config['entry_point_name'],
                'entryPoints' => array_combine(
                    array_keys($this->config['components']),
                    array_keys($reactOnRailsRegisteredFiles)
                )

            ]
        );

        $this->filesystem->dumpFile($webpackConfigPath . 'webpack.config.' . $side . '.js', $content);
        $this->output->writeln("[$side] config exported.");
    }

    /**
     * @param $name
     * @param $path
     * @return bool|string
     */
    private function getComponentPath($name, $path)
    {
        if (preg_match('/^[^.\/]/', $path)
            && $this->filesystem->exists($this->projectDir . '/node_modules/' . $path)) {
            return $path;
        } else {
            $realPath = realpath($this->projectDir . '/' . $path);

            if ($realPath === false) {
                throw new FileNotFoundException("Component '$name' does not exist in '$path' with root_dir: '$this->projectDir'.");
            }

            return $realPath;
        }
    }
}
