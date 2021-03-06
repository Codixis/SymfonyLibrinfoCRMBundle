<?php

namespace Librinfo\CRMBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('librinfo_crm');

        // Here you should define the parameters that are allowed to
        // configure your bundle. See the documentation linked above for
        // more information on that topic.
        $rootNode
            ->children()
                ->arrayNode('Circle')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('allow_organisms')->defaultTrue()->end()
                        ->booleanNode('allow_contacts')->defaultFalse()->end()
                        ->booleanNode('allow_positions')->defaultFalse()->end()
                    ->end()
                ->end()
                ->arrayNode('Organism')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('normalize')
                            ->defaultValue([])
                            ->prototype('array')
                                ->prototype('scalar')->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('Contact')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('normalize')
                            ->defaultValue([])
                            ->prototype('array')
                                ->prototype('scalar')->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
