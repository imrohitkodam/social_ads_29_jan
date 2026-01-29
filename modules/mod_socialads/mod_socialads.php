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

use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Helper\ModuleHelper;

// Proceed further only if this component folder exists
if (Folder::exists(JPATH_ROOT . '/components/com_socialads'))
{
	$lang = Factory::getLanguage();
	$lang->load('mod_socialads', JPATH_SITE);

	// SocialAds config parameters
	$sa_params = ComponentHelper::getParams('com_socialads');

	// @TODO - chk if needed
	// $allowed_type = $sa_params->get('ad_type_allowed');

	// Load js assets
	$tjStrapperPath = JPATH_SITE . '/media/techjoomla_strapper/tjstrapper.php';

	if (File::exists($tjStrapperPath))
	{
		require_once $tjStrapperPath;
		TjStrapper::loadTjAssets('com_socialads');
	}

	// Load CSS & JS resources.
	if (JVERSION > '3.0')
	{
		$laod_boostrap = $sa_params->get('boostrap_manually');

		if (!empty($laod_boostrap))
		{
			// Load bootstrap CSS and JS.
			HTMLHelper::_('bootstrap.loadcss');
			HTMLHelper::_('bootstrap.framework');
		}
	}

	// Load module helper
	require_once dirname(__FILE__) . '/helper.php';

	$saInitClassPath = JPATH_SITE . '/components/com_socialads/init.php';

	if (!class_exists('SaInit'))
	{
		JLoader::register('SaInit', $saInitClassPath);
		JLoader::load('SaInit');
	}

	// Define autoload function
	spl_autoload_register('SaInit::autoLoadHelpers');

	// Get module id, zone id
	$moduleid = $module->id;
	$zone_id  = $params->get('zone', 0);

	// Get ad types for current zone
	// $modSocialadsHelper = new modSocialadsHelper;
	require_once JPATH_SITE . '/components/com_socialads/helpers/zones.php';

	$ad_type = SaZonesHelper::getAdtype($zone_id);
	$ad_type = explode("|", $ad_type);

	$adHeightAndWidth = '';
	if ($params->get('ad_rotate_with_transition', 0) == 1)
	{
		$adHeightAndWidth = SaZonesHelper::getAdHeightAndWidth($zone_id);
	}

	// Show create ad link in output?
	if ($params->get('create', 1))
	{
		// $socialadshelper = new SocialadsAdHelper;
		$Itemid = SaCommonHelper::getSocialadsItemid('adform');

		// Print_r($Itemid);die;
	}

	// $ads = $adRetriever->getAdsForZoneExternally($params, $moduleid);
	if (($params->get('popup_ad', 0) == 1 && $params->get('popup_on_every_reload', 0) == 0)) 
	{
		$session         = Factory::getSession();
		$sessionKey      = 'popup_ad_' . $moduleid;
		$floaterAdsInSession = $session->get($sessionKey);
		if (isset($floaterAdsInSession)) 
		{
			$ads         = [];
		}
		else 
		{
			$ads = SaAdEngineHelper::getInstance()->getAdsForZone($params, $moduleid);
			$session->set($sessionKey, $moduleid);
		}
	} else {
	    $ads = SaAdEngineHelper::getInstance()->getAdsForZone($params, $moduleid);
	}

	require ModuleHelper::getLayoutPath('mod_socialads', $params->get('layout', 'default'));
}
