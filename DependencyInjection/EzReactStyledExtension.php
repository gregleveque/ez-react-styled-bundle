<?php

namespace Gie\EzReactStyledBundle\DependencyInjection;

use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Config\FileLocator;


/**
 * This is the class that loads and manages your bundle configuration.
 *
 * @link http://symfony.com/doc/current/cookbook/bundles/extension.html
 */
class EzReactStyledExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $rootDir = $container->getParameter('kernel.root_dir');
        $parameters = $container->getParameter('ez_react_styled');
        $parameters += [
            'auto_webpack_config' => $config['auto_webpack_config'],
            'deferred_json_props' => $config['deferred_json_props'],
            'components' => $config['components'],
            'export_dir' => $rootDir . '/Resources/webpack'
        ];

        $container->setParameter('limenius_react.default_rendering', $config['default_rendering']);
        $container->setParameter('limenius_react.fail_loud', $config['server_side']['fail_loud']);
        $container->setParameter('limenius_react.trace', $config['server_side']['trace']);
        $container->setParameter('ez_react_styled', $parameters);
    }
}