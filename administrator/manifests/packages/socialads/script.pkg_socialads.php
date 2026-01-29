<?php
/**
 * @package    AdminTools
 * @copyright  Copyright (c)2010-2011 Nicholas K. Dionysopoulos
 * @license    GNU General Public License version 3, or later
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * @package     SocialAds
 * @subpackage  com_socialads
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2025 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\Installer\Installer;

use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Date\Date;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Factory;

$tjInstallerPath = JPATH_ROOT . '/administrator/manifests/packages/socialads/tjinstaller.php';

if (File::exists(__DIR__ . '/tjinstaller.php'))
{
	include_once __DIR__ . '/tjinstaller.php';
}
elseif (File::exists($tjInstallerPath))
{
	include_once $tjInstallerPath;
}

/**
 * Package socialAds installer script class
 *
 * @since  3.2.0
 */
class Pkg_SocialAdsInstallerScript extends TJInstaller
{
	protected $extensionName = 'SocialAds';

	protected $installationQueue = array (
		'postflight' => array(
			'components' => array(
				'com_tjfields' => 1,
				'com_tjprivacy' => 1,
			),
			'plugins' => array(
				'system' => array(
					'tjassetsloader' => 1,
				),
				'payment' => array(
					'2checkout' => 0,
					'alphauserpoints' => 0,
					'authorizenet' => 1,
					'bycheck' => 1,
					'byorder' => 1,
					'ccavenue' => 0,
					'jomsocialpoints' => 0,
					'linkpoint' => 1,
					'paypal' => 1,
					'paypalpro' => 0,
					'payu' => 1,
					'razorpay' => 0
				)
			),
			'libraries' => array(
				'techjoomla' => 1,
			),
			'files' => array(
				'tj_strapper' => 1
			)
		)
	);

	protected $extensionsToEnable = array (
		// 0 - type, 1 - name, 2 - publish?, 3 - client, 4 - group, 5 - position
		array ('plugin', 'tjmaxmind', 1, 1, 'system', ''),
		array ('plugin', 'layout1', 1, 1, 'socialadslayout', ''),
		array ('plugin', 'layout2', 1, 1, 'socialadslayout', ''),
		array ('plugin', 'layout3', 1, 1, 'socialadslayout', ''),
		array ('plugin', 'layout4', 1, 1, 'socialadslayout', ''),
		array ('plugin', 'layout5', 1, 1, 'socialadslayout', ''),
		array ('plugin', 'layout6', 1, 1, 'socialadslayout', ''),
		array ('plugin', 'layout7', 1, 1, 'socialadslayout', ''),
		array ('plugin', 'socialads', 1, 1, 'privacy', ''),
		array ('plugin', 'menuitem_meta_keyword_field', 1, 1, 'system', ''),
		array ('plugin', 'archivestats', 0, 1, 'system', ''),
		array ('plugin', 'statsemail', 0, 1, 'system', ''),
	);

	protected $uninstallQueue = array();

	/**
	 * Runs after fresh install
	 *
	 * @param   JInstaller  $parent  The class calling this method
	 *
	 * @return  void
	 */
	public function install($parent)
	{
		// Create folder in images for storing media files for Ads
		if (!Folder::exists(JPATH_ROOT . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'socialads'))
		{
			$data = '<html><head><title></title></head><body></body></html>';
			File::write(JPATH_ROOT . '/' . 'images' . '/' . 'socialads' . '/' . 'index.html', $data);
		}

		// Enable the extensions on fresh install
		$this->enableExtensions();
	}

	/**
	 * Method to uninstall the component
	 *
	 * @param   JInstaller  $parent  Class calling this method
	 *
	 * @return  void
	 */
	public function uninstall($parent)
	{
		// Uninstall subextensions
		$status = $this->uninstallSubextensions($parent);

		// Remove layouts folder
		$this->removeLayout();

		// Show the post-uninstallation page
		$this->renderPostUninstallation($status);
	}

	/**
	 * Runs after install, update or discover_update
	 *
	 * @param   string      $type    install, update or discover_update
	 * @param   JInstaller  $parent  The class calling this method
	 *
	 * @return  void
	 */
	public function postflight($type, $parent)
	{
		// Copy tjinstaller file into packages folder
		$this->copyInstaller($parent);

		// Install subextensions
		$status = $this->installSubextensions($parent);

		// Code to add ad layout in the Joomla layout folder
		$this->addLayout($parent);

		// Show the post-installation page
		$this->renderPostInstallation($status);
		?>
		<script type="text/javascript" src="<?php echo Uri::root() . 'components/com_socialads/js/jquery-1.11.min.js' ?>"></script>
		<?php
	}

	/**
	 * Post install render custom messages at start
	 *
	 * @return  Void
	 */
	protected function postInstallRenderCustomMesagesAtStart()
	{
		$settings    = Uri::base() . "index.php?option=com_config&view=component&component=com_socialads";
		$bsSetupLink = Uri::base() . "index.php?option=com_socialads&view=setup&layout=setup";
		?>

		<div class="alert alert-danger">
			If you are updating SocialAds from version less than 3.1 to the latest version.
			You need to do the SocialAds settings again from the SocialAds component option For example - Payment Mode
			<a href="<?php echo $settings; ?>" target="_blank" class="btn btn-primary "> SocialAds Settings</a>
		</div>
		<!-- <div class="alert alert-danger">
			To make SocialAds design compatible with Bootstrap 2 or 3 version according to your template refer
			<a href="<?php //echo $bsSetupLink; ?>"
				target="_blank"
				class="btn btn-primary "> Setup Instructions</a>
		</div>-->
		<div class="alert alert-info">
			<?php
			$urlToCleanImages = Route::_(Uri::root() . 'index.php?option=com_socialads&tmpl=component&task=removeimagesCall');?>
			<input type="button" class="btn btn-danger" value="Click here" onclick="window.open('<?php echo $urlToCleanImages; ?>')">
			<b> to Clean unused images.</b>
		</div>
		<?php
	}

	/**
	 * Method to add layout
	 *
	 * @param   Installer  $parent  The class calling this method
	 *
	 * @return  void
	 */
	protected function addLayout($parent)
	{
		$src                 = $parent->getParent()->getPath('source');
		$bs2LayoutPathAd     = $src . "/layouts/ad";
		$bs2LayoutPathAdhtml = $src . "/layouts/adhtml";
		$bs2LayoutPathBs2    = $src . "/layouts/bs2";
		$bs5LayoutPathBs5    = $src . "/layouts/bs5";

		if (Folder::exists(JPATH_SITE . '/layouts/ad'))
		{
			Folder::delete(JPATH_SITE . '/layouts/ad');
		}

		if (Folder::exists(JPATH_SITE . '/layouts/adhtml'))
		{
			Folder::delete(JPATH_SITE . '/layouts/adhtml');
		}

		if (Folder::exists(JPATH_SITE . '/layouts/bs2'))
		{
			Folder::delete(JPATH_SITE . '/layouts/bs2');
		}

		if (Folder::exists(JPATH_SITE . '/layouts/bs5'))
		{
			Folder::delete(JPATH_SITE . '/layouts/bs5');
		}

		// Copy
		Folder::copy($bs2LayoutPathAd, JPATH_SITE . '/layouts/ad');
		Folder::copy($bs2LayoutPathAdhtml, JPATH_SITE . '/layouts/adhtml');
		Folder::copy($bs2LayoutPathBs2, JPATH_SITE . '/layouts/bs2');
		Folder::copy($bs5LayoutPathBs5, JPATH_SITE . '/layouts/bs5');
	}

	/**
	 * Method to remove layout
	 *
	 * @return  void
	 */
	protected function removeLayout()
	{
		// Remove layout folders
		$removeFilesAndFolders = array(
			'folders' => array(
				'layouts/ad',
				'layouts/bs2',
				'layouts/bs5',
				'layouts/adhtml',
			)
		);

		$this->removeObsoleteFilesAndFolders($removeFilesAndFolders);
	}

	/**
	 * Method to copy installer file
	 *
	 * @param   JInstaller  $parent  Class calling this method
	 *
	 * @return  void
	 */
	protected function copyInstaller($parent)
	{
		$src  = $parent->getParent()->getPath('source') . '/tjinstaller.php';
		$dest = JPATH_ROOT . '/administrator/manifests/packages/socialads/tjinstaller.php';

		File::copy($src, $dest);
	}
}
