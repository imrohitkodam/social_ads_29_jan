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
use Joomla\CMS\Table\Table;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Component\ComponentHelper;

/**
 * Zones Helper class
 *
 * @package  SocialAds
 * @since    3.1
 */
class SaZonesHelper
{
	/**
	 * Extra code for zone to Check if only one entry of zones while instlalling components
	 *
	 * @param   array  $special_access  access details
	 *
	 * @return  string
	 *
	 * @since  1.6
	 */
	public static function getAllowedAdTypes($special_access)
	{
		$sa_params = ComponentHelper::getParams('com_socialads');
		$affiliateAccess = $sa_params->get('user_groups_for_affiliate_ads');
		$user      = Factory::getUser();
		$db    = Factory::getDBO();
		$query    = $db->getQuery(true);
		$query->select($db->qn(array('id', 'ad_type')));
		$query->from($db->quoteName('#__ad_zone'));
		$query->where($db->quoteName('state') . " = " . 1);

		$db->setQuery($query);
		$count = $db->loadobjectlist();

		if ($count)
		{
			$publish_mod = self::getZoneModule();
			$results = array_unique($publish_mod);
			$text_img_flag = $img_flag = $text_flag = $affiliate_flag = $html5_flag = 0;

			foreach ($results as $publish_asign_zones)
			{
				if ($text_img_flag == 1 and $img_flag == 1 and $text_flag == 1 and $affiliate_flag == 1 and $html5_flag == 1)
				{
					break;
				}

				foreach ($count as $zoneids)
				{
					if ($publish_asign_zones == $zoneids->id)
					{
						$query1    = $db->getQuery(true);
						$query1->select($db->qn('ad_type'));
						$query1->from($db->quoteName('#__ad_zone'));
						$query1->where($db->quoteName('id') . " = " . $publish_asign_zones);
						$query1->where($db->quoteName('state') . " = " . 1);
						$query1->group($db->quoteName('ad_type'));

						$db->setQuery($query1);

						// Jugad code
						$rawresult = str_replace('||', ',', $db->loadResult());
						$rawresult = str_replace('|', '', $rawresult);
						$ad_type1 = explode(",", $rawresult);

						// Jugad code end

						$adtype_default = array();
						$adtype_default[] = 'text_media';
						$adtype_default[] = 'text';
						$adtype_default[] = 'media';
						$ad_type = $ad_type1[0];

						if ($ad_type)
						{
							if ($ad_type == 'text_media' && in_array('text_media', $sa_params->get('ad_type_allowed', $adtype_default)) )
							{
								if ($text_img_flag == 0)
								{
									$text_img_flag = 1;
								}
							}

							if ($ad_type == 'media' && in_array('media', $sa_params->get('ad_type_allowed', $adtype_default)) )
							{
								if ($img_flag == 0)
								{
									$img_flag = 1;
								}
							}

							if ($ad_type == 'text' && in_array('text', $sa_params->get('ad_type_allowed', $adtype_default)) )
							{
								if ($text_flag == 0)
								{
									$text_flag = 1;
								}
							}

							if ($ad_type == 'html5_zip' && in_array('html5_zip', $sa_params->get('ad_type_allowed', $adtype_default)) )
							{
								if ($html5_flag == 0)
								{
									$html5_flag = 1;
								}
							}
						}

						// ADDED for affiliate ads to show only when zone is present for it
						if (!empty($ad_type1[1]))
						{
							$ad_type_affiliate = $ad_type1[1];

							if ($ad_type_affiliate == 'affiliate')
							{
								if ($affiliate_flag == 0)
								{
									$affiliate_flag = 1;

									// $published_zone_type[]='affiliate';
								}
							}
						}
					}
				}
			}
		}

		$adtype_select = array();

		if ($text_img_flag)
		{
			$published_zone_type[] = 'text_media';
			$adtype_select[] = HTMLHelper::_('select.option', 'text_media',  Text::_('COM_SOCIALADS_AD_TYP_TXT_IMG'));
		}

		if ($img_flag)
		{
			$published_zone_type[] = 'media';
			$adtype_select[] = HTMLHelper::_('select.option', 'media',  Text::_('COM_SOCIALADS_AD_TYP_IMG'));
		}

		if ($text_flag)
		{
			$published_zone_type[] = 'text';
			$adtype_select[] = HTMLHelper::_('select.option', 'text',  Text::_('COM_SOCIALADS_AD_TYP_TXT'));
		}

		if ($html5_flag)
		{
			$published_zone_type[] = 'html5_zip';
			$adtype_select[] = HTMLHelper::_('select.option', 'html5_zip',  Text::_('COM_SOCIALADS_TITLE_ZONE_AD_HTML5'));
		}

		$affiliate_ad_create_access = 0;
		if ($affiliateAccess)
		{
			if (count(array_intersect($user->groups, $affiliateAccess)))
			{
				$affiliate_ad_create_access = 1;
			}
		}
		else if ($user->authorise('core.admin'))
		{
			$affiliate_ad_create_access = 1;
		}

		if ($affiliate_flag && $affiliate_ad_create_access)
		{
			$published_zone_type[] = 'affiliate';
			$adtype_select[] = HTMLHelper::_('select.option', 'affiliate', Text::_('COM_SOCIALADS_AD_TYP_AFFI'));
		}

		return $adtype_select;
	}

	/**
	 * Function to get module for a zone
	 *
	 * @return  array
	 *
	 * @since  1.6
	 */
	public static function getZoneModule()
	{
		$db = Factory::getDBO();
		$query    = $db->getQuery(true);
		$query->select($db->qn('params'));
		$query->from($db->quoteName('#__modules'));
		$query->where($db->quoteName('published') . " = " . 1);
		$query->where($db->quoteName('module') . " LIKE " . $db->q('%mod_socialads%'));
		$db->setQuery($query);
		$params = $db->loadObjectList();
		$module = array();

		foreach ($params as $params)
		{
			$params1 = str_replace('"', '', $params->params);

			$single = explode(",", $params1);

			foreach ($single as $single)
			{
				$name = explode(":", $single);

				if ($name[0] == 'zone')
				{
					$module[] = $name[1];
				}
			}
		}

		return $module;
	}

	/**
	 * To get a ad zone type
	 *
	 * @param   integer  $zone_id  zone id of a selected zone
	 *
	 * @return  array
	 *
	 * @since  1.6
	 **/
	public static function getAdtype($zone_id)
	{
		$db = Factory::getDbo();
		Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_socialads/tables');
		$zoneDetails = Table::getInstance('Zone', 'SocialadsTable', array('dbo', $db));
		$zoneDetails->getFields('ad_type');
		$zoneDetails->load(array('id' => $zone_id));

		return $zoneDetails->ad_type;
	}

	/**
	 * To get a ad zone type
	 *
	 * @param   integer  $zone_id  zone id of a selected zone
	 *
	 * @return  array
	 *
	 * @since  1.6
	 **/
	public static function getAdHeightAndWidth($zone_id)
	{
		$db = Factory::getDbo();
		Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_socialads/tables');
		$zoneDetails = Table::getInstance('Zone', 'SocialadsTable', array('dbo', $db));
		$zoneDetails->getFields('img_height', 'img_width');
		$zoneDetails->load(array('id' => $zone_id));

		return $zoneDetails;
	}

	/**
	 * To get a ad zone data
	 *
	 * @param   integer  $zone_id  zone id of a selected zone
	 *
	 * @return  array
	 *
	 * @since  1.6
	 **/
	public static function getAdZoneDetails($zone_id)
	{
		$db = Factory::getDbo();
		Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_socialads/tables');
		$zoneDetails = Table::getInstance('Zone', 'SocialadsTable', array('dbo', $db));
		$zoneDetails->load(array('id' => $zone_id));

		return $zoneDetails;
	}
}
