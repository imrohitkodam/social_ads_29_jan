<?php
/**
 * @package     Techjoomla
 * @subpackage  TJInstaller
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2020 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Application\ApplicationHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\Installer\Installer;
use Joomla\CMS\Language\Text;

/**
 * TJInstaller class
 *
 * @since  1.0.0
 */
class TJInstaller
{
	protected $extensionName = '';

	protected $obsoleteExtensionsUninstallationQueue = array();

	/**
	 * Installs subextensions
	 *
	 * @param   Installer  $parent  The class calling this method
	 * @param   string     $type    Preflight/postflight
	 *
	 * @return  object   The subextension installation status
	 *
	 * @since  1.0.0
	 */
	protected function installSubextensions($parent, $type = 'postflight')
	{
		$src                = $parent->getParent()->getPath('source');
		$db                 = Factory::getDbo();
		$status             = new stdClass;
		$status->modules    = array();
		$status->plugins    = array();
		$status->components = array();
		$status->packages   = array();
		$status->libraries  = array();
		$status->files      = array();

		// Install components
		if (isset($this->installationQueue[$type]['components']) && count($this->installationQueue[$type]['components']))
		{
			foreach ($this->installationQueue[$type]['components'] as $component => $published)
			{
				$path = "$src/components/$component";

				if (!is_dir($path))
				{
					continue;
				}

				// Was the component already installed?
				$query = $db->getQuery(true)
					->select('COUNT(*)')
					->from($db->qn('#__extensions'))
					->where($db->qn('name') . ' = ' . $db->q($component))
					->where($db->qn('element') . ' = ' . $db->q($component));
				$db->setQuery($query);
				$count = $db->loadResult();

				$installer            = new Installer;
				$result               = $installer->install($path);
				$status->components[] = array (
					'name'      => $component,
					'result'    => $result,
					'published' => $published
				);

				if ($published && !$count)
				{
					$query = $db->getQuery(true)
						->update($db->qn('#__extensions'))
						->set($db->qn('enabled') . ' = ' . $db->q('1'))
						->where($db->qn('element') . ' = ' . $db->q($component))
						->where($db->qn('name') . ' = ' . $db->q($component));
					$db->setQuery($query);
					$db->execute();
				}
			}
		}

		// Install modules
		if (isset($this->installationQueue[$type]['modules']) && count($this->installationQueue[$type]['modules']))
		{
			foreach ($this->installationQueue[$type]['modules'] as $folder => $modules)
			{
				if (count($modules))
				{
					foreach ($modules as $module => $modulePreferences)
					{
						// Install the module
						if (empty($folder))
						{
							$folder = 'site';
						}

						$path = "$src/modules/$folder/$module";

						if (!is_dir($path))
						{
							$path = "$src/modules/$folder/mod_$module";
						}

						if (!is_dir($path))
						{
							$path = "$src/modules/$module";
						}

						if (!is_dir($path))
						{
							$path = "$src/modules/mod_$module";
						}

						if (!is_dir($path))
						{
							continue;
						}

						// Was the module already installed?
						$sql = $db->getQuery(true)
							->select('COUNT(*)')
							->from('#__modules')
							->where($db->qn('module') . ' = ' . $db->q($module));
						$db->setQuery($sql);

						$count             = $db->loadResult();
						$installer         = new Installer;
						$result            = $installer->install($path);
						$status->modules[] = array (
							'name'   => $module,
							'client' => $folder,
							'result' => $result
						);

						// Modify where it's published and its published state
						if (!$count)
						{
							// A. Position and state
							list($modulePosition, $modulePublished) = $modulePreferences;

							if ($modulePosition == 'cpanel')
							{
								$modulePosition = 'icon';
							}

							$sql = $db->getQuery(true)
								->update($db->qn('#__modules'))
								->set($db->qn('position') . ' = ' . $db->q($modulePosition))
								->where($db->qn('module') . ' = ' . $db->q($module));

							if ($modulePublished)
							{
								$sql->set($db->qn('published') . ' = ' . $db->q('1'));
							}

							$db->setQuery($sql);
							$db->execute();

							// B. Change the ordering of back-end modules to 1 + max ordering
							if ($folder == 'admin')
							{
								$query = $db->getQuery(true);
								$query->select('MAX(' . $db->qn('ordering') . ')')
									->from($db->qn('#__modules'))
									->where($db->qn('position') . '=' . $db->q($modulePosition));
								$db->setQuery($query);
								$position = $db->loadResult();
								$position++;

								$query = $db->getQuery(true);
								$query->update($db->qn('#__modules'))
									->set($db->qn('ordering') . ' = ' . $db->q($position))
									->where($db->qn('module') . ' = ' . $db->q($module));
								$db->setQuery($query);
								$db->execute();
							}

							// C. Link to all pages
							$query = $db->getQuery(true);
							$query->select('id')->from($db->qn('#__modules'))
								->where($db->qn('module') . ' = ' . $db->q($module));
							$db->setQuery($query);
							$moduleid = $db->loadResult();

							$query = $db->getQuery(true);
							$query->select('*')->from($db->qn('#__modules_menu'))
								->where($db->qn('moduleid') . ' = ' . $db->q($moduleid));
							$db->setQuery($query);
							$assignments = $db->loadObjectList();
							$isAssigned  = !empty($assignments);

							if (!$isAssigned)
							{
								$o = (object) array (
									'moduleid' => $moduleid,
									'menuid'   => 0
								);
								$db->insertObject('#__modules_menu', $o);
							}
						}
					}
				}
			}
		}

		// Install plugins
		if (isset($this->installationQueue[$type]['plugins']) && count($this->installationQueue[$type]['plugins']))
		{
			foreach ($this->installationQueue[$type]['plugins'] as $folder => $plugins)
			{
				if (count($plugins))
				{
					foreach ($plugins as $plugin => $published)
					{
						$path = "$src/plugins/$folder/$plugin";

						if (!is_dir($path))
						{
							$path = "$src/plugins/$folder/plg_$plugin";
						}

						if (!is_dir($path))
						{
							$path = "$src/plugins/$plugin";
						}

						if (!is_dir($path))
						{
							$path = "$src/plugins/plg_$plugin";
						}

						if (!is_dir($path))
						{
							continue;
						}

						// Was the plugin already installed?
						$query = $db->getQuery(true)
							->select('COUNT(*)')
							->from($db->qn('#__extensions'))
							->where($db->qn('element') . ' = ' . $db->q($plugin))
							->where($db->qn('folder') . ' = ' . $db->q($folder));
						$db->setQuery($query);
						$count = $db->loadResult();

						$installer         = new Installer;
						$result            = $installer->install($path);
						$status->plugins[] = array (
							'name'   => $plugin,
							'group'  => $folder,
							'result' => $result
						);

						if ($published && !$count)
						{
							$query = $db->getQuery(true)
								->update($db->qn('#__extensions'))
								->set($db->qn('enabled') . ' = ' . $db->q('1'))
								->where($db->qn('element') . ' = ' . $db->q($plugin))
								->where($db->qn('folder') . ' = ' . $db->q($folder));
							$db->setQuery($query);
							$db->execute();
						}
					}
				}
			}
		}

		// Install libs
		if (isset($this->installationQueue[$type]['libraries']) && count($this->installationQueue[$type]['libraries']))
		{
			foreach ($this->installationQueue[$type]['libraries'] as $folder => $published)
			{
				$path = "$src/libraries/$folder";

				if (file_exists($path))
				{
					$query = $db->getQuery(true)
						->select('COUNT(*)')
						->from($db->qn('#__extensions'))
						->where('( ' . ($db->qn('name') . ' = ' . $db->q($folder)) . ' OR ' . ($db->qn('element') . ' = ' . $db->q($folder)) . ' )')
						->where($db->qn('type') . ' = ' . $db->q('library'));
					$db->setQuery($query);
					$count = $db->loadResult();

					$installer           = new Installer;
					$result              = $installer->install($path);
					$status->libraries[] = array(
						'name'   => $folder,
						'result' => $result,
						'status' => $published
					);

					if ($published && !$count)
					{
						$query = $db->getQuery(true)
							->update($db->qn('#__extensions'))
							->set($db->qn('enabled') . ' = ' . $db->q('1'))
							->where('( ' . ($db->qn('name') . ' = ' . $db->q($folder)) . ' OR ' . ($db->qn('element') . ' = ' . $db->q($folder)) . ' )');
						$db->setQuery($query);
						$db->execute();
					}
				}
			}
		}

		// Install files
		if (isset($this->installationQueue[$type]['files']) && count($this->installationQueue[$type]['files']))
		{
			foreach ($this->installationQueue[$type]['files'] as $folder => $published)
			{
				$path = "$src/files/$folder";

				if (file_exists($path))
				{
					$query = $db->getQuery(true)
						->select('COUNT(*)')
						->from($db->qn('#__extensions'))
						->where('( ' . ($db->qn('name') . ' = ' . $db->q($folder)) . ' OR ' . ($db->qn('element') . ' = ' . $db->q($folder)) . ' )')
						->where($db->qn('type') . ' = ' . $db->q('file'));
					$db->setQuery($query);
					$count = $db->loadResult();

					$installer       = new Installer;
					$result          = $installer->install($path);
					$status->files[] = array(
						'name'   => $folder,
						'result' => $result,
						'status' => $published
					);

					if ($published && !$count)
					{
						$query = $db->getQuery(true)
							->update($db->qn('#__extensions'))
							->set($db->qn('enabled') . ' = ' . $db->q('1'))
							->where('( ' . ($db->qn('name') . ' = ' . $db->q($folder)) . ' OR ' . ($db->qn('element') . ' = ' . $db->q($folder)) . ' )')
							->where($db->qn('folder') . ' = ' . $db->q($folder));
						$db->setQuery($query);
						$db->execute();
					}
				}
			}
		}

		// Install packages
		if (isset($this->installationQueue[$type]['packages']) && count($this->installationQueue[$type]['packages']))
		{
			foreach ($this->installationQueue[$type]['packages']  as $folder => $publish)
			{
				$path = "$src/packages/$folder";

				if (file_exists($path))
				{
					$query = $db->getQuery(true)
						->select('COUNT(*)')
						->from($db->qn('#__extensions'))
						->where('( ' . ($db->qn('name') . ' = ' . $db->q($folder)) . ' OR ' . ($db->qn('element') . ' = ' . $db->q($folder)) . ' )')
						->where($db->qn('type') . ' = ' . $db->q('package'));
					$db->setQuery($query);
					$count = $db->loadResult();

					$installer          = new Installer;
					$result             = $installer->install($path);
					$status->packages[] = array (
						'name'   => $folder,
						'result' => $result
					);

					if (!$count && $publish)
					{
						$query = $db->getQuery(true)
							->update($db->qn('#__extensions'))
							->set($db->qn('enabled') . ' = ' . $db->q('1'))
							->where('( ' . ($db->qn('name') . ' = ' . $db->q($folder)) . ' OR ' . ($db->qn('element') . ' = ' . $db->q($folder)) . ' )');
						$db->setQuery($query);
						$db->execute();
					}
				}
			}
		}

		// Install Easysocial Apps
		if (isset($this->installationQueue[$type]['easysocialApps']) && count($this->installationQueue[$type]['easysocialApps']))
		{
			if (file_exists(JPATH_ADMINISTRATOR . '/components/com_easysocial/includes/foundry.php'))
			{
				require_once JPATH_ADMINISTRATOR . '/components/com_easysocial/includes/foundry.php';

				foreach ($this->installationQueue[$type]['easysocialApps'] as $folder => $easysocialApps)
				{
					if (count($easysocialApps))
					{
						foreach ($easysocialApps as $easysocialApp => $published)
						{
							$installer = Foundry::get('Installer');
							$installer->load($src . "/easysocial_apps/" . $folder . '/' . $easysocialApp);
							$esAppInstall = $installer->install();

							$status->easysocialAppInstall[] = array (
								'name'   => $easysocialApp,
								'group'  => $folder,
								'result' => $esAppInstall,
								'status' => '0'
							);
						}
					}
				}
			}
		}

		return $status;
	}

	/**
	 * Uninstalls subextensions (modules, plugins) bundled with the main extension
	 *
	 * @param   Installer  $parent  The class calling this method
	 *
	 * @return  object   The subextension uninstallation status
	 *
	 * @since  1.0.0
	 */
	protected function uninstallSubextensions($parent)
	{
		$db                 = Factory::getDbo();
		$status             = new stdClass;
		$status->modules    = array();
		$status->plugins    = array();
		$status->components = array();
		$status->packages   = array();
		$status->libraries  = array();
		$status->files      = array();

		$src = $parent->getParent()->getPath('source');

		// Modules uninstallation
		if (isset($this->uninstallQueue['modules']) && count($this->uninstallQueue['modules']))
		{
			foreach ($this->uninstallQueue['modules'] as $folder => $modules)
			{
				if (count($modules))
				{
					foreach ($modules as $module => $modulePreferences)
					{
						// Find the module ID
						$sql = $db->getQuery(true)
							->select($db->qn('extension_id'))
							->from($db->qn('#__extensions'))
							->where($db->qn('element') . ' = ' . $db->q($module))
							->where($db->qn('type') . ' = ' . $db->q('module'));
						$db->setQuery($sql);
						$id = $db->loadResult();

						// Uninstall the module
						if ($id)
						{
							$installer         = new Installer;
							$result            = $installer->uninstall('module', $id, 1);
							$status->modules[] = array (
								'name'   => $module,
								'client' => $folder,
								'result' => $result
							);
						}
					}
				}
			}
		}

		// Plugins uninstallation
		if (isset($this->uninstallQueue['plugins']) && count($this->uninstallQueue['plugins']))
		{
			foreach ($this->uninstallQueue['plugins'] as $folder => $plugins)
			{
				if (count($plugins))
				{
					foreach ($plugins as $plugin => $published)
					{
						$sql = $db->getQuery(true)
							->select($db->qn('extension_id'))
							->from($db->qn('#__extensions'))
							->where($db->qn('type') . ' = ' . $db->q('plugin'))
							->where($db->qn('element') . ' = ' . $db->q($plugin))
							->where($db->qn('folder') . ' = ' . $db->q($folder));
						$db->setQuery($sql);

						$id = $db->loadResult();

						if ($id)
						{
							$installer         = new Installer;
							$result            = $installer->uninstall('plugin', $id);
							$status->plugins[] = array (
								'name'   => $plugin,
								'group'  => $folder,
								'result' => $result
							);
						}
					}
				}
			}
		}

		// Component uninstallation
		if (isset($this->uninstallQueue['components']) && count($this->uninstallQueue['components']))
		{
			foreach ($this->uninstallQueue['components'] as $component => $published)
			{
				// Find the ID
				$sql = $db->getQuery(true)
					->select($db->qn('extension_id'))
					->from($db->qn('#__extensions'))
					->where($db->qn('element') . ' = ' . $db->q($component))
					->where($db->qn('name') . ' = ' . $db->q($component))
					->where($db->qn('type') . ' = ' . $db->q('component'));
				$db->setQuery($sql);
				$id = $db->loadResult();

				// Uninstall the module
				if ($id)
				{
					$installer            = new Installer;
					$result               = $installer->uninstall('component', $id, 1);
					$status->components[] = array (
						'name'   => $component,
						'result' => $result
					);
				}
			}
		}

		// Library uninstallation
		if (isset($this->uninstallQueue['libraries']) && count($this->uninstallQueue['libraries']))
		{
			foreach ($this->uninstallQueue['libraries'] as $library => $published)
			{
				// Find the ID
				$sql = $db->getQuery(true)
					->select($db->qn('extension_id'))
					->from($db->qn('#__extensions'))
					->where('( ' . ($db->qn('name') . ' = ' . $db->q($library)) . ' OR ' . ($db->qn('element') . ' = ' . $db->q($library)) . ' )')
					->where($db->qn('type') . ' = ' . $db->q('library'));
				$db->setQuery($sql);
				$id = $db->loadResult();

				// Uninstall the library
				if ($id)
				{
					$installer            = new Installer;
					$result               = $installer->uninstall('library', $id, 1);
					$status->libraries[]  = array (
						'name'   => $library,
						'result' => $result
					);
				}
			}
		}

		// Files uninstallation
		if (isset($this->uninstallQueue['files']) && count($this->uninstallQueue['files']))
		{
			foreach ($this->uninstallQueue['files'] as $file => $published)
			{
				// Find the ID
				$sql = $db->getQuery(true)
					->select($db->qn('extension_id'))
					->from($db->qn('#__extensions'))
					->where('( ' . ($db->qn('name') . ' = ' . $db->q($file)) . ' OR ' . ($db->qn('element') . ' = ' . $db->q($file)) . ' )')
					->where($db->qn('type') . ' = ' . $db->q('file'));
				$db->setQuery($sql);
				$id = $db->loadResult();

				// Uninstall the library
				if ($id)
				{
					$installer       = new Installer;
					$result          = $installer->uninstall('library', $id, 1);
					$status->files[] = array (
						'name'   => $file,
						'result' => $result
					);
				}
			}
		}

		// Package uninstallation
		if (isset($this->uninstallQueue['packages']) && count($this->uninstallQueue['packages']))
		{
			foreach ($this->uninstallQueue['packages'] as $package => $published)
			{
				// Find the ID
				$sql = $db->getQuery(true)
					->select($db->qn('extension_id'))
					->from($db->qn('#__extensions'))
					->where('( ' . ($db->qn('name') . ' = ' . $db->q($package)) . ' OR ' . ($db->qn('element') . ' = ' . $db->q($package)) . ' )')
					->where($db->qn('type') . ' = ' . $db->q('package'));
				$db->setQuery($sql);
				$id = $db->loadResult();

				// Uninstall the library
				if ($id)
				{
					$installer          = new Installer;
					$result             = $installer->uninstall('package', $id, 1);
					$status->packages[] = array (
						'name'   => $package,
						'result' => $result
					);
				}
			}
		}

		return $status;
	}

	/**
	 * Update subextensions (modules, plugins) package id
	 *
	 * @param   Installer  $parent  The class calling this method
	 *
	 * @return  boolean
	 *
	 * @since  1.0.0
	 */
	protected function updatePackageId($parent)
	{
		$src   = $parent->getParent()->getPath('source');
		$db    = Factory::getDbo();

		$query = $db->getQuery(true)
			->select('extension_id')
			->from($db->qn('#__extensions'))
			->where($db->qn('element') . ' = ' . $db->q($this->packageName));
		$db->setQuery($query);
		$extension_id = $db->loadResult();

		// Plugins package id update
		if (count($this->subextensionsQueue['plugins']))
		{
			foreach ($this->subextensionsQueue['plugins'] as $folder => $plugins)
			{
				if (count($plugins))
				{
					foreach ($plugins as $plugin => $published)
					{
						$query = $db->getQuery(true)
							->update($db->qn('#__extensions'))
							->set($db->qn('package_id') . ' = ' . $db->q($extension_id))
							->where($db->qn('type') . ' = ' . $db->q('plugin'))
							->where($db->qn('element') . ' = ' . $db->q($plugin))
							->where($db->qn('folder') . ' = ' . $db->q($folder));
						$db->setQuery($query);
						$db->execute();
					}
				}
			}
		}

		// Component package id update
		if (count($this->subextensionsQueue['components']))
		{
			foreach ($this->subextensionsQueue['components'] as $element => $published)
			{
				$query = $db->getQuery(true)
					->update($db->qn('#__extensions'))
					->set($db->qn('package_id') . ' = ' . $db->q($extension_id))
					->where($db->qn('type') . ' = ' . $db->q('component'))
					->where($db->qn('element') . ' = ' . $db->q($element));
				$db->setQuery($query);
				$db->execute();
			}
		}

		// Module package id update
		if (count($this->subextensionsQueue['modules']))
		{
			foreach ($this->subextensionsQueue['modules'] as $element => $published)
			{
				$query = $db->getQuery(true)
					->update($db->qn('#__extensions'))
					->set($db->qn('package_id') . ' = ' . $db->q($extension_id))
					->where($db->qn('type') . ' = ' . $db->q('module'))
					->where($db->qn('element') . ' = ' . $db->q($element));
				$db->setQuery($query);
				$db->execute();
			}
		}

		return true;
	}

	/**
	 * Removes obsolete files and folders
	 *
	 * @param   Array  $removeFilesAndFolders  Array of files and folder to me removed
	 *
	 * @return  void
	 *
	 * @since  1.0.0
	 */
	protected function removeObsoleteFilesAndFolders($removeFilesAndFolders)
	{
		// Remove files
		if (!empty($removeFilesAndFolders['files']))
		{
			foreach ($removeFilesAndFolders['files'] as $file)
			{
				$f = JPATH_ROOT . '/' . $file;

				if (File::exists($f))
				{
					File::delete($f);
				}
			}
		}

		// Remove folders
		if (!empty($removeFilesAndFolders['folders']))
		{
			foreach ($removeFilesAndFolders['folders'] as $folder)
			{
				$f = JPATH_ROOT . '/' . $folder;

				if (Folder::exists($f))
				{
					Folder::delete($f);
				}
			}
		}
	}

	/**
	 * Uninstalls obsolete subextensions (modules, plugins) bundled with the main extension
	 *
	 * @return  Object   The subextension uninstallation status
	 *
	 * @since  1.0.0
	 */
	protected function uninstallObsoleteSubextensions()
	{
		$db                 = Factory::getDbo();
		$status             = new stdClass;
		$status->modules    = array();
		$status->plugins    = array();
		$status->components = array();
		$status->packages   = array();
		$status->libraries  = array();
		$status->files      = array();

		// Modules uninstallation
		if (count($this->obsoleteExtensionsUninstallationQueue['modules']))
		{
			foreach ($this->obsoleteExtensionsUninstallationQueue['modules'] as $folder => $modules)
			{
				if (count($modules))
				{
					foreach ($modules as $module)
					{
						// Find the module ID
						$sql = $db->getQuery(true)
							->select($db->qn('extension_id'))
							->from($db->qn('#__extensions'))
							->where($db->qn('element') . ' = ' . $db->q($module))
							->where($db->qn('type') . ' = ' . $db->q('module'));
						$db->setQuery($sql);
						$id = $db->loadResult();

						// Uninstall the module
						if ($id)
						{
							$installer         = new Installer;
							$result            = $installer->uninstall('module', $id, 1);
							$status->modules[] = array(
								'name'   => $module,
								'client' => $folder,
								'result' => $result
							);
						}
					}
				}
			}
		}

		// Plugins uninstallation
		if (count($this->obsoleteExtensionsUninstallationQueue['plugins']))
		{
			foreach ($this->obsoleteExtensionsUninstallationQueue['plugins'] as $folder => $plugins)
			{
				if (count($plugins))
				{
					foreach ($plugins as $plugin)
					{
						$sql = $db->getQuery(true)
							->select($db->qn('extension_id'))
							->from($db->qn('#__extensions'))
							->where($db->qn('type') . ' = ' . $db->q('plugin'))
							->where($db->qn('element') . ' = ' . $db->q($plugin))
							->where($db->qn('folder') . ' = ' . $db->q($folder));
						$db->setQuery($sql);

						$id = $db->loadResult();

						if ($id)
						{
							$installer         = new Installer;
							$result            = $installer->uninstall('plugin', $id, 1);
							$status->plugins[] = array(
								'name'   => 'plg_' . $plugin,
								'group'  => $folder,
								'result' => $result
							);
						}
					}
				}
			}
		}

		// Component uninstallation
		if (isset($this->obsoleteExtensionsUninstallationQueue['components']) && count($this->obsoleteExtensionsUninstallationQueue['components']))
		{
			foreach ($this->obsoleteExtensionsUninstallationQueue['components'] as $component => $published)
			{
				// Find the ID
				$sql = $db->getQuery(true)
					->select($db->qn('extension_id'))
					->from($db->qn('#__extensions'))
					->where($db->qn('element') . ' = ' . $db->q($component))
					->where($db->qn('name') . ' = ' . $db->q($component))
					->where($db->qn('type') . ' = ' . $db->q('component'));
				$db->setQuery($sql);
				$id = $db->loadResult();

				// Uninstall the component
				if ($id)
				{
					$installer            = new Installer;
					$result               = $installer->uninstall('component', $id, 1);
					$status->components[] = array (
						'name'   => $component,
						'result' => $result
					);
				}
			}
		}

		// File uninstallation
		if (isset($this->obsoleteExtensionsUninstallationQueue['files']) && count($this->obsoleteExtensionsUninstallationQueue['files']))
		{
			foreach ($this->obsoleteExtensionsUninstallationQueue['files'] as $file => $published)
			{
				// Find the ID
				$sql = $db->getQuery(true)
					->select($db->qn('extension_id'))
					->from($db->qn('#__extensions'))
					->where('( ' . ($db->qn('name') . ' = ' . $db->q($file)) . ' OR ' . ($db->qn('element') . ' = ' . $db->q($file)) . ' )')
					->where($db->qn('type') . ' = ' . $db->q('file'));
				$db->setQuery($sql);
				$id = $db->loadResult();

				// Uninstall the component
				if ($id)
				{
					$installer       = new Installer;
					$result          = $installer->uninstall('file', $id, 1);
					$status->files[] = array (
						'name'   => $file,
						'result' => $result
					);
				}
			}
		}

		// Library uninstallation
		if (isset($this->obsoleteExtensionsUninstallationQueue['libraries']) && count($this->obsoleteExtensionsUninstallationQueue['libraries']))
		{
			foreach ($this->obsoleteExtensionsUninstallationQueue['libraries'] as $library => $published)
			{
				// Find the ID
				$sql = $db->getQuery(true)
					->select($db->qn('extension_id'))
					->from($db->qn('#__extensions'))
					->where('( ' . ($db->qn('name') . ' = ' . $db->q($library)) . ' OR ' . ($db->qn('element') . ' = ' . $db->q($library)) . ' )')
					->where($db->qn('type') . ' = ' . $db->q('library'));
				$db->setQuery($sql);
				$id = $db->loadResult();

				// Uninstall the component
				if ($id)
				{
					$installer            = new Installer;
					$result               = $installer->uninstall('library', $id, 1);
					$status->libraries[]  = array (
						'name'   => $library,
						'result' => $result
					);
				}
			}
		}

		// Package uninstallation
		if (isset($this->obsoleteExtensionsUninstallationQueue['packages']) && count($this->obsoleteExtensionsUninstallationQueue['packages']))
		{
			foreach ($this->obsoleteExtensionsUninstallationQueue['packages'] as $package => $published)
			{
				// Find the ID
				$sql = $db->getQuery(true)
					->select($db->qn('extension_id'))
					->from($db->qn('#__extensions'))
					->where('( ' . ($db->qn('name') . ' = ' . $db->q($package)) . ' OR ' . ($db->qn('element') . ' = ' . $db->q($package)) . ' )')
					->where($db->qn('type') . ' = ' . $db->q('package'));
				$db->setQuery($sql);
				$id = $db->loadResult();

				// Uninstall the component
				if ($id)
				{
					$installer          = new Installer;
					$result             = $installer->uninstall('package', $id, 1);
					$status->packages[] = array (
						'name'   => $package,
						'result' => $result
					);
				}
			}
		}

		return $status;
	}

	/**
	 * Run sql file
	 *
	 * @param   string  $sqlfilePath  Path of file
	 *
	 * @return  boolean
	 *
	 * @since  1.0.0
	 */
	protected function runSQL($sqlfilePath)
	{
		$db  = Factory::getDbo();
		$app = Factory::getApplication();

		// Don't modify below this line
		$buffer = file_get_contents($sqlfilePath);

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

						try
						{
							$db->execute();
						}
						catch (\RuntimeException $e)
						{
							$app->enqueueMessage($e->getMessage(), 'error');

							return false;
						}
					}
				}
			}
		}

		return true;
	}

	/**
	 * Enable modules and plugins after installing them
	 *
	 * @return  void
	 *
	 * @since  1.0.0
	 */
	protected function enableExtensions()
	{
		foreach ($this->extensionsToEnable as $ext)
		{
			$modPosition = isset($ext[5]) ? $ext[5] : '';

			// 0 - type, 1 - name, 2 - publish?, 3 - client, 4 - group, 5 - position
			$this->enableExtension($ext[0], $ext[1], $ext[2], $ext[3], $ext[4], $modPosition);
		}
	}

	/**
	 * Enable an extension
	 *
	 * @param   string   $type            The extension type.
	 * @param   string   $name            The name of the extension (the element field).
	 * @param   integer  $publish         Publish - ? 0 - no, 1 - yes
	 * @param   integer  $client          The application id (0: Joomla CMS site; 1: Joomla CMS administrator).
	 * @param   string   $group           The extension group (for plugins).
	 * @param   string   $modulePosition  Module position (for modules).
	 *
	 * @return  boolean
	 *
	 * @since  1.0.0
	 */
	protected function enableExtension($type, $name, $publish = 1, $client = 1, $group = null, $modulePosition = null)
	{
		try
		{
			$db    = Factory::getDbo();
			$query = $db->getQuery(true)
					->update('#__extensions')
					->set($db->qn('enabled') . ' = ' . $db->q('1'))
					->where('type = ' . $db->quote($type))
					->where('element = ' . $db->quote($name));
		}
		catch (\Exception $e)
		{
			return false;
		}

		switch ($type)
		{
			case 'plugin':
				// Plugins have a folder but not a client
				$query->where('folder = ' . $db->quote($group));
				break;

			case 'language':
			case 'module':
			case 'template':
				// Languages, modules and templates have a client but not a folder
				$client = ApplicationHelper::getClientInfo($client, true);
				$query->where('client_id = ' . (int) $client);
				break;

			default:
			case 'library':
			case 'package':
			case 'component':
				// Components, packages and libraries don't have a folder or client.
				// Included for completeness.
				break;
		}

		try
		{
			$db->setQuery($query);
			$db->execute();
		}
		catch (\Exception $e)
		{
			return false;
		}

		if ($type == 'module')
		{
			// 1. Publish module
			$sql = $db->getQuery(true)
				->update($db->qn('#__modules'))
				->set($db->qn('position') . ' = ' . $db->q($modulePosition))
				->set($db->qn('published') . ' = ' . $db->q($publish))
				->where($db->qn('module') . ' = ' . $db->q($name));

			$db->setQuery($sql);
			$db->execute();

			// 2. Link to all pages
			$query = $db->getQuery(true);
			$query->select('id')->from($db->qn('#__modules'))
				->where($db->qn('module') . ' = ' . $db->q($name));
			$db->setQuery($query);
			$moduleid = $db->loadResult();

			$query = $db->getQuery(true);
			$query->select('*')->from($db->qn('#__modules_menu'))
				->where($db->qn('moduleid') . ' = ' . $db->q($moduleid));
			$db->setQuery($query);
			$assignments = $db->loadObjectList();
			$isAssigned  = !empty($assignments);

			if (!$isAssigned)
			{
				$o = (object) array(
					'moduleid' => $moduleid,
					'menuid'   => 0
				);
				$db->insertObject('#__modules_menu', $o);
			}
		}

		return true;
	}

	/**
	 * Renders post installtion status
	 *
	 * @param   object  $status  Subextensions install status array
	 *
	 * @return  void
	 *
	 * @since  1.0.0
	 */
	protected function renderPostInstallation($status)
	{
		$rows = 1;

		// Render post install custom messages
		$this->postInstallRenderCustomMesagesAtStart();
		?>

		<table class="table table-striped table-hover">
			<thead>
				<tr>
					<th class="title" colspan="2"><?php echo Text::_('Extension'); ?></th>
					<th width="30%"><?php echo Text::_('Status'); ?></th>
				</tr>
			</thead>
			<tfoot>
				<tr>
					<td colspan="3"></td>
				</tr>
			</tfoot>

			<tbody>
				<tr>
					<th colspan="2"><?php echo Text::_('Components'); ?></th>
					<th></th>
				</tr>
				<tr class="row1">
					<td class="key" colspan="2">
						<?php echo $this->extensionName; ?>
					</td>
					<td><strong style="color: green"><?php echo Text::_('Installed'); ?></strong></td>
				</tr>

				<?php
				if (isset($status->packages))
				{
					if (count($status->packages))
					{
						?>
						<tr>
							<th colspan="2"><?php echo Text::_('Packages'); ?></th>
							<th></th>
						</tr>

						<?php
						foreach ($status->packages as $package)
						{
							$rows++;
							?>
							<tr class="row1">
								<td colspan="2" class="key"><?php echo $package['name'];  ?></td>
								<td>
									<strong style="color: <?php echo ($package['result']) ? "green" : "red"?>">
										<?php echo ($package['result']) ? Text::_('Installed') : Text::_('Not Installed');?>
									</strong>
								</td>
							</tr>
							<?php
						}
					}
				}
				?>

				<?php
				if (isset($status->components))
				{
					if (count($status->components))
					{
						?>
						<tr>
							<th colspan="2"><?php echo Text::_('Horizontal Extensions'); ?></th>
							<th></th>
						</tr>

						<?php
						foreach ($status->components as $component)
						{
							?>
							<tr class="row<?php echo (++ $rows % 2); ?>">
								<td colspan="2" class="key"><?php echo $component['name'];  ?></td>
								<td>
									<strong style="color: <?php echo ($component['result']) ? "green" : "red"?>">
										<?php
										echo $component['result'] ? Text::_('Installed') : Text::_('Not Installed');
										?>
									</strong>
								</td>
							</tr>
							<?php
						}
					}
				}
				?>

				<?php
				if (isset($status->libraries))
				{
					if (count($status->libraries))
					{
						?>
						<tr>
							<th colspan="2"><?php echo Text::_('Libraries'); ?></th>
							<th></th>
						</tr>

						<?php
						foreach ($status->libraries as $libraries)
						{
							$rows++;
							?>
							<tr class="row1">
								<td colspan="2" class="key"><?php echo $libraries['name'];  ?></td>
								<td>
									<strong style="color: <?php echo ($libraries['result']) ? "green" : "red"?>">
										<?php echo ($libraries['result']) ? Text::_('Installed') : Text::_('Not Installed');?>
									</strong>
								</td>
							</tr>
							<?php
						}
					}
				}
				?>

				<?php
				if (isset($status->files))
				{
					if (count($status->files))
					{
						?>
						<tr>
							<th colspan="2"><?php echo Text::_('Files'); ?></th>
							<th></th>
						</tr>

						<?php
						foreach ($status->files as $file)
						{
							$rows++;
							?>
							<tr class="row1">
								<td colspan="2" class="key"><?php echo $file['name'];  ?></td>
								<td>
									<strong style="color: <?php echo ($file['result']) ? "green" : "red"?>">
										<?php echo ($file['result']) ? Text::_('Installed') : Text::_('Not Installed');?>
									</strong>
								</td>
							</tr>
							<?php
						}
					}
				}
				?>

				<?php
				if (isset($status->modules))
				{
					if (count($status->modules))
					{
						?>
						<tr>
							<th><?php echo Text::_('Module'); ?></th>
							<th><?php echo Text::_('Client'); ?></th>
							<th></th>
						</tr>

						<?php
						foreach ($status->modules as $module)
						{
							$rows++;
							?>
							<tr class="row <?php echo ($rows % 2);?>">
								<td class="key"><?php echo $module['name'];?></td>
								<td class="key"><?php echo $module['client'];?></td>
								<td>
									<strong style="color: <?php echo ($module['result']) ? "green" : "red"?>">
										<?php echo ($module['result']) ? Text::_('Installed') : Text::_('Not Installed');?>
									</strong>
								</td>
							</tr>
							<?php
						}
					}
				}

				if (isset($status->plugins))
				{
					if (count($status->plugins))
					{
						?>
						<tr>
							<th><?php echo Text::_('Plugin'); ?></th>
							<th><?php echo Text::_('Group'); ?></th>
							<th></th>
						</tr>

						<?php
						foreach ($status->plugins as $plugin)
						{
							$rows++;
							?>
							<tr class="row<?php echo ($rows % 2);?>">
								<td class="key"><?php echo $plugin['name'];  ?></td>
								<td class="key"><?php echo $plugin['group']; ?></td>
								<td>
									<strong style="color: <?php echo ($plugin['result']) ? "green" : "red"?>">
										<?php echo ($plugin['result']) ? Text::_('Installed') : Text::_('Not installed');?>
									</strong>
								</td>
							</tr>
							<?php
						}
					}
				}
				?>

				<?php
				if (isset($status->easysocialAppInstall))
				{
					if (count($status->easysocialAppInstall))
					{
						?>
						<tr class="row1">
							<th><?php echo Text::_('EasySocial Apps'); ?></th>
							<th></th>
							<th></th>
						</tr>

						<?php
						foreach ($status->easysocialAppInstall as $easysocialAppInstall)
						{
							?>
							<tr class="row2">
								<td class="key"><?php echo $easysocialAppInstall['name'];  ?></td>
								<td class="key"><?php echo $easysocialAppInstall['group']; ?></td>
								<td>
									<strong style="color: <?php echo ($easysocialAppInstall['result'])? "green" : "red"?>">
										<?php echo ($easysocialAppInstall['result']) ? Text::_('Installed') : Text::_('Not installed'); ?>
									</strong>

									<?php
									// If installed then only show msg
									if (!empty($easysocialAppInstall['result']))
									{
										echo (
											$easysocialAppInstall['status'] ?
											"<span class=\"label label-success\">Enabled</span>" :
											"<span class=\"label label-important\">Disabled</span>"
										);
									}
									?>
								</td>
							</tr>
							<?php
						}
					}
				}
				?>
			</tbody>
		</table>
		<?php
		// Render post install custom messages
		$this->postInstallRenderCustomMesagesAtEnd();
	}

	/**
	 * Renders post installtion status
	 *
	 * @param   object  $status  Subextensions install status array
	 *
	 * @return  void
	 *
	 * @since  1.0.0
	 */
	protected function renderPostUninstallation($status)
	{
		$rows = 1;
		?>

		<h4><?php echo Text::_($this->extensionName . ' Uninstallation Status'); ?></h4>

		<table class="adminlist table table-striped table-condensed" style="font-weight:normal !important;">
			<thead>
				<tr>
					<th colspan="2"><?php echo Text::_('Extension'); ?></th>
					<th width="30%"><?php echo Text::_('Status'); ?></th>
				</tr>
			</thead>

			<tfoot>
				<tr>
					<td colspan="3"></td>
				</tr>
			</tfoot>

			<tbody>
				<tr>
					<td colspan="2"><?php echo $this->extensionName . ' ' . Text::_('Component'); ?></td>
					<td><strong style="color: green"><?php echo Text::_('Removed'); ?></strong></td>
				</tr>

				<?php
				if (isset($status->packages))
				{
					if (count($status->packages))
					{
						?>
						<tr>
							<th colspan="2"><?php echo Text::_('Packages'); ?></th>
							<th></th>
						</tr>

						<?php
						foreach ($status->packages as $package)
						{
							?>
							<tr class="row<?php echo (++ $rows % 2); ?>">
								<td colspan="2" class="key"><?php echo $package['name'];  ?></td>
								<td>
									<strong style="color: <?php echo ($package['result'])? "green" : "red"?>">
										<?php echo ($package['result']) ? Text::_('Removed') : Text::_('Not removed'); ?>
									</strong>
								</td>
							</tr>
							<?php
						}
					}
				}
				?>

				<?php
				if (isset($status->components))
				{
					if (count($status->components))
					{
						?>
						<tr>
							<th colspan="2"><?php echo Text::_('Horizontal Extensions'); ?></th>
							<th></th>
						</tr>

						<?php
						foreach ($status->components as $component)
						{
							?>
							<tr class="row<?php echo (++ $rows % 2); ?>">
								<td colspan="2" class="key"><?php echo $component['name'];  ?></td>
								<td>
									<strong style="color: <?php echo ($component['result'])? "green" : "red"?>">
										<?php echo ($component['result']) ? Text::_('Removed') : Text::_('Not removed'); ?>
									</strong>
								</td>
							</tr>
							<?php
						}
					}
				}
				?>

				<?php
				if (isset($status->libraries))
				{
					if (count($status->libraries))
					{
						?>
						<tr>
							<th colspan="2"><?php echo Text::_('Libraries'); ?></th>
							<th></th>
						</tr>

						<?php
						foreach ($status->libraries as $library)
						{
							?>
							<tr class="row<?php echo (++ $rows % 2); ?>">
								<td colspan="2" class="key"><?php echo $library['name'];  ?></td>
								<td>
									<strong style="color: <?php echo ($library['result']) ? "green" : "red"?>">
										<?php echo ($library['result']) ? Text::_('Removed') : Text::_('Not removed'); ?>
									</strong>
								</td>
							</tr>
							<?php
						}
					}
				}
				?>

				<?php
				if (isset($status->files))
				{
					if (count($status->files))
					{
						?>
						<tr>
							<th colspan="2"><?php echo Text::_('Files'); ?></th>
							<th></th>
						</tr>

						<?php
						foreach ($status->files as $file)
						{
							?>
							<tr class="row<?php echo (++ $rows % 2); ?>">
								<td colspan="2" class="key"><?php echo $file['name'];  ?></td>
								<td>
									<strong style="color: <?php echo ($file['result'])? "green" : "red"?>">
										<?php echo ($file['result']) ? Text::_('Removed') : Text::_('Not removed'); ?>
									</strong>
								</td>
							</tr>
							<?php
						}
					}
				}
				?>

				<?php
				if (count($status->modules))
				{
					?>
					<tr>
						<th><?php echo Text::_('Module'); ?></th>
						<th><?php echo Text::_('Client'); ?></th>
						<th></th>
					</tr>

					<?php
					foreach ($status->modules as $module)
					{
						?>
						<tr class="row<?php echo (++ $rows % 2); ?>">
							<td><?php echo $module['name']; ?></td>
							<td><?php echo $module['client']; ?></td>
							<td>
								<strong style="color: <?php echo ($module['result'])? "green" : "red"?>">
									<?php echo ($module['result']) ? Text::_('Removed') : Text::_('Not removed'); ?>
								</strong>
							</td>
						</tr>
						<?php
					}
				}
				?>

				<?php
				if (count($status->plugins))
				{
					?>
					<tr>
						<th><?php echo Text::_('Plugin'); ?></th>
						<th><?php echo Text::_('Group'); ?></th>
						<th></th>
					</tr>

					<?php
					foreach ($status->plugins as $plugin)
					{
						?>
						<tr class="row<?php echo (++ $rows % 2); ?>">
							<td><?php echo $plugin['name']; ?></td>
							<td><?php echo $plugin['group']; ?></td>
							<td>
								<strong style="color: <?php echo ($plugin['result'])? "green" : "red"?>">
									<?php echo ($plugin['result']) ? Text::_('Removed') : Text::_('Not removed'); ?>
								</strong>
							</td>
						</tr>
						<?php
					}
				}
				?>

			</tbody>
		</table>
		<?php
	}

	/**
	 * Post install render custom messages at start
	 *
	 * @return  void
	 *
	 * @since  1.0.0
	 */
	protected function postInstallRenderCustomMesagesAtStart()
	{
		return;
	}

	/**
	 * Post install render custom messages at end
	 *
	 * @return  void
	 *
	 * @since  1.0.0
	 */
	protected function postInstallRenderCustomMesagesAtEnd()
	{
		return;
	}
}
