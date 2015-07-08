<?php

namespace Naoned\OaiPmhServerBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('naoned_oai_pmh_server');

        $rootNode
            ->children()
                ->scalarNode('data_provider_service_name')
                    ->defaultValue('naoned.oaipmh.data_provider')
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
