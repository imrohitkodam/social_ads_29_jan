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

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Component\ComponentHelper;

/**
 * SocialAds Init class
 *
 * @since  3.2.0
 */
class SaInit
{
	/**
	 * Autoload class
	 *
	 * @param   string  $classname  classname.
	 *
	 * @return  void
	 */
	public static function autoLoadHelpers($classname)
	{
		static $loaded = null;
		$app           = Factory::getApplication();
		$docType       = Factory::getDocument()->getType();
		$params = ComponentHelper::getParams('com_socialads');
		$currency_display_format = $params->get('currency_display_format', '', 'STRING');

		Factory::getDocument()->addScriptDeclaration('
			var sa_json_config = {};
			sa_json_config = {
				"currency_display_format" : "'. $currency_display_format .'"
			}
		');

		require_once JPATH_SITE . '/components/com_socialads/helpers/saversion.php';
		$versionClass = new SaVersion();

		$version = $versionClass->getMediaVersion();
		$options = array("version" => $version);

		// Load component helper files
		$classes = array(
			/*Root folder*/
			'socialadsFrontendhelper' => 'helper.php',

			/*Helpers*/
			'SaAdsHelper'             => 'helpers/ads.php',
			'SaCommonHelper'          => 'helpers/common.php',
			'SaCreditsHelper'         => 'helpers/credits.php',
			'SaAdEngineHelper'        => 'helpers/engine.php',
			'SaStatsHelper'           => 'helpers/stats.php',
			'SaZonesHelper'           => 'helpers/zones.php',
			'TJGeoLocationHelper'     => 'helpers/tjgeoloc.php',
			'SaIntegrationsHelper'    => 'helpers/integrations.php',
			'SaVersion'               => 'helpers/saversion.php'
		);

		if (array_key_exists($classname, $classes))
		{
			if (!class_exists($classname))
			{
				require_once JPATH_SITE . '/components/com_socialads/' . $classes[$classname];
			}
		}

		if (!defined('SOCIALADS_LOAD_BOOTSTRAP_VERSION'))
		{

			if ($app->isClient("administrator"))
			{
				$bsVersion = (JVERSION < '4.0.0') ? 'bs2' : 'bs5';
			}
			else
			{
				$bsVersion = $params->get('bootstrap_version', '', 'STRING');

				if (empty($bsVersion))
				{
					$bsVersion = (JVERSION < '4.0.0') ? 'bs3' : 'bs5';
				}
			}

			define('SOCIALADS_LOAD_BOOTSTRAP_VERSION', $bsVersion);
		}

		if (SOCIALADS_LOAD_BOOTSTRAP_VERSION == 'bs3')
		{
			HTMLHelper::stylesheet('media/techjoomla_strapper/css/bootstrap.j3.min.css', $options);
		}

		if (!defined('SA_WRAPPER_CLASS'))
		{
			if (SOCIALADS_LOAD_BOOTSTRAP_VERSION == 'bs3')
			{
				define('SA_WRAPPER_CLASS', " sa-wrapper tjBs3 ");
			}
			else
			{
				define('SA_WRAPPER_CLASS', " sa-wrapper tjBs5 ");
			}
		}

		// Load Boostrap Files
		if ($params->get('boostrap_manually') == '1')
		{
			if (SOCIALADS_LOAD_BOOTSTRAP_VERSION == 'bs3')
			{
				HTMLHelper::stylesheet('media/techjoomla_strapper/bs3/css/bootstrap.min.css', $options);
			}
			else
			{
				HTMLHelper::stylesheet('media/vendor/bootstrap/css/bootstrap.min.css', $options);
			}
		}
	}
}
