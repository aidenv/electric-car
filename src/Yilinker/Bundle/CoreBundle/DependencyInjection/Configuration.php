<?php

namespace Yilinker\Bundle\CoreBundle\DependencyInjection;

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
        // Here you should define the parameters that are allowed to
        // configure your bundle. See the documentation linked above for
        // more information on that topic.

        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('yilinker_core');

        $rootNode->children()
                    ->arrayNode('sms_semaphore')
                        ->children()
                            ->scalarNode('api_key')->end()
                            ->scalarNode('from')->end()
                            ->scalarNode('outbound_endpoint')->end()
                        ->end()
                    ->end()
                    ->arrayNode('sms_mobiweb')
                        ->children()
                            ->scalarNode('ip_address')->end()
                            ->scalarNode('username')->end()
                            ->scalarNode('password')->end()
                            ->scalarNode('originator')->end()
                        ->end()
                    ->end()
                    ->arrayNode('sms_ucpass')
                        ->children()
                            ->scalarNode('accountSid')->end()
                            ->scalarNode('token')->end()
                            ->scalarNode('appId')->end()                          
                        ->end()
                    ->end()
                    ->arrayNode('yilinker_express')
                        ->children()
                            ->scalarNode('baseurl')->end()
                            ->arrayNode('routes')
                                ->children()
                                    ->scalarNode('create_package')->end()
                                    ->scalarNode('create_internal_package')->end()
                                    ->scalarNode('handling_fee')->end()
                                    ->scalarNode('create_products')->end()
                                    ->scalarNode('cancel_package')->end()
                                    ->scalarNode('test_create_internal_package')->end()
                                    ->scalarNode('test_trigger_package_updates')->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                    ->arrayNode('yilinker_account')
                        ->children()
                            ->arrayNode('routes')
                                ->children()
                                    ->scalarNode('login')->end()
                                    ->scalarNode('user_create')->end()
                                    ->scalarNode('user_update')->end()
                                    ->scalarNode('get_details')->end()
                                    ->scalarNode('check_email_exists')->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                    ->arrayNode('yilinker_trading')
                        ->children()
                            ->scalarNode('baseurl')->end()
                            ->scalarNode('appurl')->end()
                            ->scalarNode('imageurl')->end()
                            ->scalarNode('api_key')->end()
                            ->arrayNode('routes')
                                ->children()
                                    ->scalarNode('get_brands')->end()
                                    ->scalarNode('get_categories')->end()
                                    ->scalarNode('get_suppliers')->end()
                                    ->scalarNode('get_supplier_detail')->end()
                                    ->scalarNode('get_products')->end()
                                    ->scalarNode('get_product_detail')->end()
                                    ->scalarNode('get_countries')->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()

        ;

        return $treeBuilder;
    }
}
