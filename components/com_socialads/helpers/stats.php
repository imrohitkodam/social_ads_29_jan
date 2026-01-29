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
use Joomla\CMS\Language\Text;

/**
 * Ads Helper class
 *
 * @package  SocialAds
 * @since    3.1
 */
class SaStatsHelper
{
	/**
	 * Update the stats table for the ad
	 *
	 * @param   string   $adid       Ad ID
	 * @param   integer  $type       call type caltype= 0 imprs; caltype =1 clks;
	 * @param   integer  $ad_charge  ad charge
	 * @param   string   $widget     widget
	 *
	 * @return  string
	 *
	 * @since  1.0
	 **/
	public function putStats($adid, $type, $ad_charge, $widget = "")
	{
		// Check for bot crawling - If bot crawler is detected then dont add stats
		$botIdentifiers = array('bot', 'slurp', 'crawler', 'spider', 'curl', 'facebook', 'fetch', 'google', 'AddThis', 'bing', 'yahoo', 'wget');
		$userAgent = strtolower($_SERVER['HTTP_USER_AGENT']);

		foreach ($botIdentifiers as $botIdentifier)
		{
			if (strpos($userAgent, strtolower($botIdentifier)) !== false)
			{
				return false;
			}
		}

		$db   = Factory::getDbo();
		$user = Factory::getUser();
		$insertstat               = new stdClass;
		$insertstat->id           = '';
		$insertstat->ad_id        = $adid;
		$insertstat->user_id      = $user->id;
		$insertstat->display_type = $type;
		$insertstat->spent        = $ad_charge;
		$insertstat->time        = Factory::getDate()->toSql();

		// Get user's IP Address
		JLoader::import('components.com_socialads.helpers.tjgeoloc', JPATH_SITE);
		$insertstat->ip_address = TJGeoLocationHelper::getUserIP();

		// HTTP_REFERER can be blank in sone cases and in that case we need to use HTTP_HOST
		if (!empty($_SERVER['HTTP_REFERER']))
		{
			$parse = parse_url($_SERVER['HTTP_REFERER']);

			if ($widget != "")
			{
				$insertstat->referer = $parse['host'] . "|" . $widget;
			}
			else
			{
				$insertstat->referer = $parse['host'];
			}
		}
		else
		{
			if ($widget != "")
			{
				$insertstat->referer = $_SERVER['HTTP_HOST'] . "|" . $widget;
			}
			else
			{
				$insertstat->referer = $_SERVER['HTTP_HOST'];
			}
		}

		if (!$db->insertObject('#__ad_stats', $insertstat, 'id'))
		{
			echo $db->stderr();

			return false;
		}

		return true;
	}

	/*
	 * adid = id of the Ad
	 * type= 0 imprs;type =1 clks;
	 */
	/**
	 * increment stats in the ad_data table for the ad
	 *
	 * @param   string   $adid  Ad ID
	 * @param   integer  $type  call type type= 0 imprs; type =1 clks;
	 * @param   integer  $qty   quantity
	 *
	 * @return  string
	 *
	 * @since  1.0
	 **/
	public function incrementStats($adid, $type, $qty = 1)
	{
		$db = Factory::getDbo();
		$query = $db->getQuery(true);

		// Fields to update.
		if ($type === 1)
		{
			$fields = array(
				// $db->quoteName('ad_clicks') . ' = ' . $db->quoteName('ad_clicks') . ' + ' . $qty

				$db->quoteName('clicks') . ' = ' . $db->quoteName('clicks') . ' + ' . $qty
			);
		}
		else
		{
			$fields = array(
				// $db->quoteName('ad_impressions') . ' = ' . $db->quoteName('ad_impressions') . ' + ' . $qty

				$db->quoteName('impressions') . ' = ' . $db->quoteName('impressions') . ' + ' . $qty
			);
		}

		// Conditions for which records should be updated.
		$conditions = array(
			$db->quoteName('ad_id') . ' = ' . (int) $adid
		);

		$query->update($db->quoteName('#__ad_data'))->set($fields)->where($conditions);
		$db->setQuery($query);
		$result = $db->execute();

		return;
	}
}
