<?php
/**
 * @version    SVN:<SVN_ID>
 * @package    SocialAds
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2015 TechJoomla. All rights reserved
 * @license    GNU General Public License version 2, or later
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;


// Load all required classes
$saInitClassPath = JPATH_SITE . '/components/com_socialads/init.php';

if (!class_exists('SaInit'))
{
	JLoader::register('SaInit', $saInitClassPath);
	JLoader::load('SaInit');
}

// Define autoload function
spl_autoload_register('SaInit::autoLoadHelpers');

/**
 * SocialAds frontend helper
 *
 * @since  3.1
 */
class SocialadsFrontendhelper
{
	/**
	 * This function return array of js files which is loaded from tjassesloader plugin.
	 *
	 * @param   array  &$jsFilesArray                  Js file's array.
	 * @param   array  &$firstThingsScriptDeclaration  Javascript to be declared first.
	 *
	 * @return  array
	 */
	public function getSocialadsJsFiles(&$jsFilesArray, &$firstThingsScriptDeclaration)
	{
		$sa_params = ComponentHelper::getParams('com_socialads');
		$app    = Factory::getApplication();
		$input  = $app->input;
		$option = $input->get('option', '');
		$view   = $input->get('view', '');
		$layout = $input->get('layout', '');
		$debugMode = Factory::getConfig()->get('debug');
		$geo_targeting = $sa_params->get('geo_targeting');
		$dbFileExists = file_exists(JPATH_PLUGINS . '/system/tjmaxmind/db/GeoLite2-City.mmdb');

		// Frontend Js files
		if (!$app->isClient('administrator'))
		{
			if ($option == "com_socialads")
			{
				// Load the view specific js
				switch ($view)
				{
					case "adform":

						$jsFilesArray[] = 'media/com_sa/vendors/fuelux/fuelux2.3loader.min.js';

						if ($geo_targeting && $dbFileExists)
						{
							if ($sa_params->get('jquery_ui') == 1)
							{
								$jsFilesArray[] = 'media/techjoomla_strapper/js/akeebajqui.js';
							}
						}

						if ($debugMode)
						{
							$jsFilesArray[] = 'media/com_sa/js/createad.js';
							$jsFilesArray[] = 'media/com_sa/js/sa.js';

							if ($geo_targeting && $dbFileExists)
							{
								$jsFilesArray[] = 'media/com_sa/js/geo.js';
							}
						}
						else
						{
							$jsFilesArray[] = 'media/com_sa/js/createad.min.js';
							$jsFilesArray[] = 'media/com_sa/js/sa.min.js';

							if ($geo_targeting && $dbFileExists)
							{
								$jsFilesArray[] = 'media/com_sa/js/geo.min.js';
							}
						}
					break;

					default:
					break;
				}
			}
		}
		else
		{
			if ($option == "com_socialads")
			{
				// Load the view specific js
				switch ($view)
				{
					case "form":
						$jsFilesArray[] = 'media/com_sa/vendors/fuelux/fuelux2.3loader.min.js';

						if ($geo_targeting && $dbFileExists)
						{
							if ($sa_params->get('jquery_ui') == 1)
							{
								$jsFilesArray[] = 'media/techjoomla_strapper/js/akeebajqui.js';
							}
						}

						if ($debugMode)
						{
							$jsFilesArray[] = 'media/com_sa/js/createad.js';
							$jsFilesArray[] = 'media/com_sa/js/sa.js';

							if ($geo_targeting && $dbFileExists)
							{
								$jsFilesArray[] = 'media/com_sa/js/geo.js';
							}
						}
						else
						{
							$jsFilesArray[] = 'media/com_sa/js/createad.min.js';
							$jsFilesArray[] = 'media/com_sa/js/sa.min.js';

							if ($geo_targeting && $dbFileExists)
							{
								$jsFilesArray[] = 'media/com_sa/js/geo.min.js';
							}
						}
					break;

					default:
					break;
				}
			}
		}

		$reqURI = Uri::root();

		// If host have wwww, but Config doesn't.
		if (isset($_SERVER['HTTP_HOST']))
		{
			if ((substr_count($_SERVER['HTTP_HOST'], "www.") != 0) && (substr_count($reqURI, "www.") == 0))
			{
				$reqURI = str_replace("://", "://www.", $reqURI);
			}
			elseif ((substr_count($_SERVER['HTTP_HOST'], "www.") == 0) && (substr_count($reqURI, "www.") != 0))
			{
				// Host do not have 'www' but Config does
				$reqURI = str_replace("www.", "", $reqURI);
			}
		}

		// Defind first thing script declaration.
		$loadFirstDeclarations          = "var root_url = '" . $reqURI . "';";
		$firstThingsScriptDeclaration[] = $loadFirstDeclarations;

		return $jsFilesArray;
	}
}
