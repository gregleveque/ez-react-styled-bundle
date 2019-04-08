<?php

namespace Gie\EzReactStyledBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/configuration.html}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('ez_react_styled');
        $rootNode
            ->children()
                ->booleanNode('auto_webpack_config')
                    ->info('Set to false to disable webpack.config.js auto generation.')
                    ->defaultTrue()
                ->end()
                ->enumNode('default_rendering')
                    ->values(['server_side', 'client_side', 'both'])
                    ->defaultValue('both')
                ->end()
                ->arrayNode('server_side')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('fail_loud')
                            ->defaultFalse()
                        ->end()
                        ->booleanNode('trace')
                            ->defaultFalse()
                        ->end()
                        ->booleanNode('deferred_json_data')
                            ->defaultTrue()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('components')
                    ->info('Format: "EntryName: EntryFile (relative to %kernel.project_dir%)".')
                    ->scalarPrototype()->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
