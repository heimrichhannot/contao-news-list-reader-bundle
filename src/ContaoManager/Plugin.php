<?php

/*
 * Copyright (c) 2023 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\NewsListReaderBundle\ContaoManager;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Config\ConfigInterface;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Contao\ManagerPlugin\Config\ConfigPluginInterface;
use Contao\ManagerPlugin\Config\ContainerBuilder;
use Contao\ManagerPlugin\Config\ExtensionPluginInterface;
use Contao\NewsBundle\ContaoNewsBundle;
use HeimrichHannot\ListBundle\HeimrichHannotContaoListBundle;
use HeimrichHannot\NewsListReaderBundle\ContaoNewsListReaderBundle;
use HeimrichHannot\ReaderBundle\HeimrichHannotContaoReaderBundle;
use HeimrichHannot\UtilsBundle\Container\ContainerUtil;
use Symfony\Component\Config\Loader\LoaderInterface;

class Plugin implements BundlePluginInterface, ExtensionPluginInterface, ConfigPluginInterface
{
    /**
     * Gets a list of autoload configurations for this bundle.
     *
     * @return ConfigInterface[]
     */
    public function getBundles(ParserInterface $parser): array
    {
        return [
            BundleConfig::create(ContaoNewsListReaderBundle::class)->setLoadAfter([
                ContaoCoreBundle::class,
                ContaoNewsBundle::class,
                HeimrichHannotContaoListBundle::class,
                HeimrichHannotContaoReaderBundle::class,
            ]),
        ];
    }

    /**
     * Allows a plugin to override extension configuration.
     *
     * @param string $extensionName
     *
     * @return array<string,mixed>
     */
    public function getExtensionConfig($extensionName, array $extensionConfigs, ContainerBuilder $container): array
    {
        $extensionConfigs = ContainerUtil::mergeConfigFile('huh_list', $extensionName, $extensionConfigs, __DIR__.'/../Resources/config/config_list.yml');

        $extensionConfigs = ContainerUtil::mergeConfigFile('huh_reader', $extensionName, $extensionConfigs, __DIR__.'/../Resources/config/config_reader.yml');

        return $extensionConfigs;
    }

    public function registerContainerConfiguration(LoaderInterface $loader, array $managerConfig): void
    {
        $loader->load('@ContaoNewsListReaderBundle/Resources/config/services.yml');
    }
}
