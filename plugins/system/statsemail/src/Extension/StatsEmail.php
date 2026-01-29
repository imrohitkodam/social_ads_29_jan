<?php
/**
 * @package     Joomla.Console
 * @subpackage  statsemail
 *
 * @copyright   Copyright (C) 2005 - 2021 Clifford E Ford. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// namespace Joomla\Plugin\System\StatsEmail\Extension;

\defined('JPATH_PLATFORM') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\CMSPlugin;
// use Joomla\Plugin\System\StatsEmail\Console\StatsEmailCommand;

class StatsEmail extends CMSPlugin
{
	protected $app;

	public function __construct(&$subject, $config = [])
	{
		parent::__construct($subject, $config);

		if (!$this->app->isClient('cli'))
		{
			return;
		}

		$this->registerCLICommands();
	}

	public static function getSubscribedEvents(): array
	{
		if ($this->app->isClient('cli'))
		{
			return [
				Joomla\Application\ApplicationEvents\ApplicationEvents::BEFORE_EXECUTE => 'registerCLICommands',
			];
		}
	}

	public function registerCLICommands()
	{
		require_once JPATH_SITE . '/plugins/system/statsemail/src/Console/StatsEmailCommand.php';
		$commandObject = new StatsEmailCommand;
		$this->app->addCommand($commandObject);
	}
}