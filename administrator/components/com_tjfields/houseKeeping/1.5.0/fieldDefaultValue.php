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
use Joomla\CMS\Table\Table;

/**
 * Migration file for TJ-Fields
 *
 * @since  1.0
 */
class TjHouseKeepingFieldDefaultValue extends TjModelHouseKeeping
{
	public $title = "Field Default Value Migration";

	public $description = "Move the default value of fields to field params";

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
			JLoader::import('components.com_tjfields.tables.option', JPATH_ADMINISTRATOR);
			$optionTable = Table::getInstance('Option', 'TjfieldsTable', array('dbo', $db));
			$optionTableColumns = $optionTable->getFields();

			// No migration needed if the default_option column is already removed
			if (!array_key_exists('default_option', $optionTableColumns))
			{
				$result['status']   = true;
				$result['message']  = "Migration successful";

				return $result;
			}

			$query = $db->getQuery(true);
			$query->select('*');
			$query->from($db->quoteName('#__tjfields_options'));
			$query->where($db->quoteName('default_option') . '=1');
			$db->setQuery($query);
			$fieldOptions = $db->loadObjectList();

			if (!empty($fieldOptions))
			{
				JLoader::import('components.com_tjfields.tables.field', JPATH_ADMINISTRATOR);
				$fieldTable = Table::getInstance('Field', 'TjfieldsTable', array('dbo', $db));

				foreach ($fieldOptions as $fieldOption)
				{
					if (!empty($fieldOption->default_option))
					{
						$fieldTable->load($fieldOption->field_id);
						$fieldParams = json_decode($fieldTable->params);
						$fieldParams->default = $fieldOption->value;
						$fieldTable->params = json_encode($fieldParams);
						$fieldTable->store();
					}
				}
			}

			// Drop 'default_option' column from options table
			$query = $db->getQuery(true);
			$query = "ALTER TABLE `#__tjfields_options` DROP `default_option`";
			$db->setQuery($query);
			$db->execute();

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
