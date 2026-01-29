<?php
/**
 * @package     Techjoomla.Libraries
 * @subpackage  Controller
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

JLoader::register('TjModelHouseKeeping', JPATH_SITE . "/libraries/techjoomla/model/houseKeeping.php");

use Joomla\CMS\Factory;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Language\Text;

/**
 * HouseKeeping controller
 *
 * @since   1.2.1
 */
trait TjControllerHouseKeeping
{
	/**
	 * Function to initialise houseKeeping
	 *
	 * @return  void
	 *
	 * @since   1.2.1
	 */
	public function init()
	{
		Session::checkToken('get') or jexit(Text::_('JINVALID_TOKEN'));

		$clientExtension = Factory::getApplication()->input->get('option', '', 'STRING');

		$tjHouseKeeping = new TjModelHouseKeeping;
		$data = $tjHouseKeeping->getHouseKeepingScripts($clientExtension);

		echo json_encode($data);
		jexit();
	}

	/**
	 * Function to execute houseKeeping script
	 *
	 * @return  void
	 *
	 * @since   1.2.1
	 */
	public function executeHouseKeeping()
	{
		Session::checkToken('get') or jexit(Text::_('JINVALID_TOKEN'));

		$input = Factory::getApplication()->input;
		$clientExtension = $input->get('client', '', 'STRING');
		$version = $input->get('version', '', 'STRING');
		$scriptFile = $input->get('script', '', 'STRING');

		$tjHouseKeeping = new TjModelHouseKeeping;
		$data = $tjHouseKeeping->executeHouseKeeping($clientExtension, $version, $scriptFile);

		echo json_encode($data);
		jexit();
	}
}
