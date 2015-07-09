<?php

namespace Naoned\OaiPmhServerBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Bundle\MonologBundle\DependencyInjection\Configuration;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class NaonedOaiPmhServerExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {

        $configuration = new Configuration();
        // TODO, instad of beyond lines
        // $config = $this->processConfiguration($configuration, $configs);

        $config = array();
        foreach ($configs as $subConfig) {
            $config = array_merge($config, $subConfig);
        }

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');

        if (!isset($config['data_provider_service_name'])) {
            throw new \InvalidArgumentException('The "data_provider_service_name" option must be set');
        }

        $container->setParameter(
            'naoned.oaipmh_server.data_provider_service_name',
            $config['data_provider_service_name']
        );

        $container->setParameter(
            'naoned.oaipmh_server.count_per_load',
            isset($config['count_per_load']) ? $config['count_per_load'] : 50
        );
    }
}
