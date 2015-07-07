<?php

namespace Naoned\OaiPmhServerBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Bundle\MonologBundle\DependencyInjection\Configuration;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class NaonedOaiPmhServerExtension extends Extension implements PrependExtensionInterface
{
    /**
     * @link http://symfony.com/doc/current/cookbook/bundles/prepend_extension.html
     */
    public function prepend(ContainerBuilder $container)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        // $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');
    }
}
