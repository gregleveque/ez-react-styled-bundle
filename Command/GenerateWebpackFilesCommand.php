<?php
/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace Gie\EzReactStyledBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
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
     * FileGenerator constructor.
     * @param TwigEngine $templating
     * @param array $config
     * @param $projectDir
     */
    public function __construct(
        TwigEngine $templating,
        array $config,
        string $projectDir
    ) {
        $this->templating = $templating;
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
    public function createReactOnRailsEntryPoints(): void
    {
        $filesystem = new Filesystem();
        $railsComponentsPath = $this->config['export_dir'] . $this->config['rails_components_dir'] . '/';

        $filesystem->remove($railsComponentsPath);

        foreach ($this->config['components'] as $name => $path) {
            $content = $this->templating->render(
                '@EzReactStyled/react-on-rails.register.js.twig',
                [
                    'componentName' => $name,
                    'componentPath' => realpath($this->projectDir . '/' . $path)
                ]
            );

            $filesystem->dumpFile($railsComponentsPath . $name . '.js', $content);
            $this->output->writeln("Component '$name' registered.");
        }
    }

    /**
     * @throws TwigError
     */
    public function createWebpackConfigs(): void
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
        $filesystem = new Filesystem();
        $filesystem->dumpFile($webpackConfigPath . 'webpack.config.' . $side . '.js', $content);

        $this->output->writeln("[$side] config exported.");
    }
}
