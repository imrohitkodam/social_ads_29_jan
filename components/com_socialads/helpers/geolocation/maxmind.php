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

/**
 * Helper for TJ Geo Location class
 *
 * @package  SocialAds
 *
 * @since    3.1
 */
class TJGeoLocationHelperMaxmind extends TJGeoLocationHelper
{
	/**
	 * Returns user's geo location from his/her IP address using Maxmind Legacy database
	 *
	 * @param   string  $ip  IP address
	 *
	 * @return  array
	 *
	 * @since  1.0
	 **/
	public static function getUserLocationFromIP($ip)
	{
		JLoader::register('TJMaxmind', JPATH_LIBRARIES . '/tjmaxmind/tjmaxmind.php');

		$tjMaxmind = new TJMaxmind;

		$data = $tjMaxmind->getUserLocationFromIP($ip);

		return $data;
	}
}
