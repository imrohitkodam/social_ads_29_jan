<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  System.statsemail
 *
 * @copyright   (C) 2021 Clifford E Ford.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') || die;

use Joomla\CMS\Factory;
use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Event\DispatcherInterface;

return new class implements ServiceProviderInterface 
{
	/**
	 * Registers the service provider with a DI container.
	 *
	 * @param   Container  $container  The DI container.
	 *
	 * @return  void
	 *
	 * @since   4.0.0
	 */
	public function register(Container $container)
	{
		require_once JPATH_SITE . '/plugins/system/statsemail/src/Extension/StatsEmail.php';
		$container->set(
			PluginInterface::class,

			function (Container $container) {
				$subject = $container->get(DispatcherInterface::class);
				$config  = (array) PluginHelper::getPlugin('system', 'statsemail');

				return new StatsEmail($subject, $config);
			}
		);

	}
};