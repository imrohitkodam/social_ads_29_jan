<?php
/**
 * @package    TJ-Fields
 *
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2019 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;

/**
 * Migration file for TJ-Fields
 *
 * @since  1.0
 */
class TjHouseKeepingFileFieldFix extends TjModelHouseKeeping
{
	public $title = "Update File field with TJFile field";

	public $description = "Remove file field class used for TJ-FIelds and replace it with TJ-File field";

	/**
	 * Default value migration script
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function migrate()
	{
		$result = array();

		try
		{
			$db = Factory::getDbo();
			$query = $db->getQuery(true);

			$fields = array(
				$db->quoteName('type') . ' = ' . $db->quote('tjfile')
			);

			$conditions = array(
				$db->quoteName('type') . ' = ' . $db->quote('file')
			);

			$query->update($db->quoteName('#__tjfields_fields'))->set($fields)->where($conditions);
			$db->setQuery($query);
			$result = $db->execute();

			$result['status']   = true;
			$result['message']  = "Migration successful";
		}
		catch (Exception $e)
		{
			$result['err_code'] = '';
			$result['status']   = false;
			$result['message']  = $e->getMessage();
		}

		return $result;
	}
}
