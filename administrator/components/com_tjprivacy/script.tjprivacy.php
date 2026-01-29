<?php
/**
 * @version    SVN: <svn_id>
 * @package    TJPrivacy
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2017-2018 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */
 
defined('_JEXEC') or die();
use Joomla\CMS\Factory;
use Joomla\CMS\Installer\InstallerHelper;
use Joomla\CMS\Language\Text;

class com_tjprivacyInstallerScript
{
	// Used to identify new install or update
	private $componentStatus = "install";

	/**
	 * Method to run before an install/update/uninstall method
	 *
	 * @param   string      $type    install, update or discover_update
	 * @param   JInstaller  $parent  Class calling this method
	 *
	 * @return void
	 */
	public function preflight($type, $parent)
	{
	}

	/**
	 * Runs after install, update or discover_update
	 *
	 * @param   string      $type    install, update or discover_update
	 * @param   JInstaller  $parent  Class calling this method
	 *
	 * @return void
	 */
	public function postflight($type, $parent)
	{
	}

	/**
	 * Method to install the component
	 *
	 * @param   JInstaller  $parent  Class calling this method
	 *
	 * @return void
	 */
	public function install($parent)
	{
		$this->installSqlFiles($parent);
	}

	/**
	 * method to update the component
	 *
	 * @param   JInstaller  $parent  Class calling this method
	 *
	 * @return void
	 */
	public function update($parent)
	{
		$this->componentStatus = "update";
	}

	/**
	 * method to install sql files
	 *
	 * @param   JInstaller  $parent  Class calling this method
	 *
	 * @return void
	 */
	public function installSqlFiles($parent)
	{
		// Lets create the table
		$this->runSQL($parent, 'install.sql');
	}

	/**
	 * Execute sql files
	 *
	 * @param   JInstaller  $parent   Class calling this method
	 * @param   string      $sqlfile  Sql file name
	 *
	 * @return  boolean
	 */
	private function runSQL($parent,$sqlfile)
	{
		$db = Factory::getDBO();

		// Obviously you may have to change the path and name if your installation SQL file ;)
		if (method_exists($parent, 'extension_root'))
		{
			$sqlfile = $parent->getPath('extension_root') . '/admin/sqlfiles/' . $sqlfile;
		}
		else
		{
			$sqlfile = $parent->getParent()->getPath('extension_root') . '/sqlfiles/' . $sqlfile;
		}

		// Don't modify below this line
		$buffer = file_get_contents($sqlfile);

		if ($buffer !== false)
		{
			$queries = \JDatabaseDriver::splitSql($buffer);

			if (count($queries) != 0)
			{
				foreach ($queries as $query)
				{
					$query = trim($query);

					if ($query != '' && $query[0] != '#')
					{
						$db->setQuery($query);

						if (!$db->execute())
						{
							$this->setMessage(
								Text::sprintf(
									'JLIB_INSTALLER_ERROR_SQL_ERROR',
									$db->stderr(true)
								),
								'error'
							);

							return false;
						}
					}
				}
			}
		}
	}
}
