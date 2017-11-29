<?php

namespace Yilinker\Bundle\CoreBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class YilinkerCoreExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
      
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('repository.yml');
        $loader->load('security.yml');
        $loader->load('services.yml');
        $loader->load('form.yml');
        $loader->load('twig.yml');
        $loader->load('subscriber.yml');
        $loader->load('listeners.yml');

        /**
         * Set service configurations
         */
        $semaphoreSmsDefintion = $container->getDefinition('yilinker_core.service.sms.semaphore_sms');
        $semaphoreSmsDefintion->addMethodCall('setConfig', array($config['sms_semaphore']));

        $semaphoreSmsDefintion = $container->getDefinition('yilinker_core.service.sms.mobiweb_sms');       
        $semaphoreSmsDefintion->addMethodCall('setConfig', array($config['sms_mobiweb']));
        
        $semaphoreSmsDefintion = $container->getDefinition('yilinker_core.service.sms.ucpass_sms');
        $semaphoreSmsDefintion->addMethodCall('setConfig', array($config['sms_ucpass']));
        
        $yilinkerExpressDefinition = $container->getDefinition('yilinker_core.logistics.yilinker.express');
        $yilinkerExpressDefinition->addMethodCall('setConfig', array($config['yilinker_express']));
        
        $yilinkerAccountDefinition = $container->getDefinition('yilinker_core.service.yla_service');
        $yilinkerAccountDefinition->addMethodCall('setConfig', array($config['yilinker_account']));

        $yilinkerTradingDefinition = $container->getDefinition('yilinker_core.import_export.yilinker.trading');
        $yilinkerTradingDefinition->addMethodCall('setConfig', array($config['yilinker_trading']));
    }
}
