<?php
/**
 * @package     plugin VMProductLocExclude
 * @author      Seyi Awofadeju
 * @website     https://awodev.com
 *
 * @copyright   Copyright (C) 2005 - 2020 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Profiler\Profiler;
use Joomla\CMS\Router\SiteRouter;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Event\DispatcherInterface;
use AwoDev\Plugin\System\Vmproductlocexclude\Extension\Vmproductlocexclude;

return new class () implements ServiceProviderInterface {

	public function register(Container $container)
	{
		$container->set(
			PluginInterface::class,
			function (Container $container) {
				$dispatcher = $container->get(DispatcherInterface::class);
				$plugin     = new Vmproductlocexclude(
					$dispatcher,
					(array) PluginHelper::getPlugin('system', 'vmproductlocexclude')
				);
				$plugin->setApplication(Factory::getApplication());

				return $plugin;
			}
		);
	}
};
