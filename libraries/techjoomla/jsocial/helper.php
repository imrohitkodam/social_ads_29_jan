<?php
/**
 * @package     Techjoomla.Libraries
 * @subpackage  JSocial
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

// No direct access.
defined('_JEXEC') or die();

use Joomla\CMS\Factory;

/**
 * Helper class for common functions in JSocial library
 *
 * @package     Techjoomla.Libraries
 * @subpackage  JSocial
 * @since       1.0.4
 */
class JSocialHelper
{
	/**
	 * Get itemid for given link
	 *
	 * @param   string   $link          link
	 * @param   integer  $skipIfNoMenu  Decide to use Itemid from $input
	 *
	 * @return  item id
	 *
	 * @since  3.0
	 */
	public static function getItemId($link, $skipIfNoMenu = 0)
	{
		$itemid    = 0;
		$mainframe = Factory::getApplication();
		$input     = Factory::getApplication()->input;

		if ($mainframe->isClient('site'))
		{
			$menu  = $mainframe->getMenu();
			$items = $menu->getItems('link', $link);

			if (isset($items[0]))
			{
				$itemid = $items[0]->id;
			}
		}

		if (!$itemid)
		{
			$db = Factory::getDbo();

			$query = "SELECT id FROM #__menu
				WHERE link LIKE '%" . $link . "%'
				AND published =1
				LIMIT 1";

			$db->setQuery($query);
			$itemid = $db->loadResult();
		}

		if (!$itemid)
		{
			if ($skipIfNoMenu)
			{
				$itemid = 0;
			}
			else
			{
				$itemid  = $input->get->get('Itemid', '0', 'INT');
			}
		}

		return $itemid;
	}
}
