<?php
/**
 * @package     Techjoomla.Libraries
 * @subpackage  Model
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

defined('_JEXEC') or die;

use Joomla\CMS\Schema\ChangeSet;
use Joomla\CMS\Factory;

if (JVERSION < '4.0')
{
	require_once JPATH_ADMINISTRATOR . '/components/com_installer/models/database.php';
}
else
{
	require_once JPATH_ADMINISTRATOR . '/components/com_installer/src/Model/DatabaseModel.php';
}


/**
 * Jlike Manage Model
 *
 * @since  1.6
 */
class TjModelDatabase extends InstallerModelDatabase
{
	protected $extension_name = '';

	/**
	 * Gets the changeset object.
	 *
	 * @return  JSchemaChangeset
	 */
	public function getItems()
	{
		if ($this->extension_name)
		{
			$folder = JPATH_ADMINISTRATOR . '/components/' . $this->extension_name . '/sql/updates/';

			try
			{
				$changeSet = ChangeSet::getInstance($this->getDbo(), $folder);
			}
			catch (RuntimeException $e)
			{
				Factory::getApplication()->enqueueMessage($e->getMessage(), 'warning');

				return false;
			}

			return $changeSet;
		}
	}

	/**
	 * + Techjoomla - Dummy override
	 * Fix schema version if wrong.
	 *
	 * @param   JSchemaChangeSet  $changeSet  Schema change set.
	 *
	 * @return   mixed  string schema version if success, false if fail.
	 */
	public function fixSchemaVersion($changeSet)
	{
		// We don't want to update anything related to core Joomla after db upgrade fix
		$schema = $this->getSchemaVersion();

		return $schema;
	}
}
