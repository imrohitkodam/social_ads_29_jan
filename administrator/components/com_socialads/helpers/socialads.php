<?php
/**
 * @version    SVN: <svn_id>
 * @package    SocialAds
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2015 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

// No direct access
defined('_JEXEC') or die();
use Joomla\CMS\HTML\HTMLHelper;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;
use Joomla\CMS\Object\CMSObject;

/**
 * SocialAds component helper.
 *
 * @package     SocialAds
 * @subpackage  com_socialads
 * @since       1.0
 */
class SocialadsHelper
{
	/**
	 * Configure the Linkbar.
	 *
	 * @param   string  $vName  The name of the active view.
	 *
	 * @return  void
	 *
	 * @since  1.6
	 */
	public static function addSubmenu($vName = '')
	{
		$params       = ComponentHelper::getParams('com_socialads');
		$payment_mode = $params->get('payment_mode');

		JHtmlSidebar::addEntry(
			Text::_('COM_SOCIALADS_TITLE_DASHBOARD'),
			'index.php?option=com_socialads&view=dashboard',
			$vName == 'dashboard'
		);

		JHtmlSidebar::addEntry(
			Text::_('COM_SOCIALADS_TITLE_ADS'),
			'index.php?option=com_socialads&view=forms',
			$vName == 'forms'
		);

		if ($payment_mode == 'pay_per_ad_mode')
		{
			JHtmlSidebar::addEntry(
				Text::_('COM_SOCIALADS_TITLE_AD_ORDERS'),
				'index.php?option=com_socialads&view=adorders',
				$vName == 'adorders'
			);
		}

		JHtmlSidebar::addEntry(
			Text::_('COM_SOCIALADS_TITLE_COUPONS'),
			'index.php?option=com_socialads&view=coupons',
			$vName == 'coupons'
		);

		if ($payment_mode == 'wallet_mode')
		{
			JHtmlSidebar::addEntry(
				Text::_('COM_SOCIALADS_TITLE_CAMPAIGNS'),
				'index.php?option=com_socialads&view=campaigns',
				$vName == 'campaigns'
			);

			JHtmlSidebar::addEntry(
				Text::_('COM_SOCIALADS_TITLE_ORDERS'),
				'index.php?option=com_socialads&view=orders',
				$vName == 'orders'
			);

			JHtmlSidebar::addEntry(
				Text::_('COM_SOCIALADS_TITLE_WALETS'),
				'index.php?option=com_socialads&view=wallets',
				$vName == 'wallets'
				);
		}

		JHtmlSidebar::addEntry(
			Text::_('COM_SOCIALADS_TITLE_ZONES'),
			'index.php?option=com_socialads&view=zones',
			$vName == 'zones'
		);

		JHtmlSidebar::addEntry(
			Text::_('COM_SOCIALADS_TITLE_SOCIAL_TARGETING'),
			'index.php?option=com_socialads&view=importfields',
			$vName == 'importfields'
		);

		JHtmlSidebar::addEntry(
			Text::_('COM_SOCIALADS_TITLE_COUNTRIES'),
			'index.php?option=com_tjfields&view=countries&client=com_socialads',
			$vName == 'countries'
		);

		JHtmlSidebar::addEntry(
			Text::_('COM_SOCIALADS_TITLE_REGIONS'),
			'index.php?option=com_tjfields&view=regions&client=com_socialads',
			$vName == 'regions'
		);

		JHtmlSidebar::addEntry(
			Text::_('COM_SOCIALADS_TITLE_CITIES'),
			'index.php?option=com_tjfields&view=cities&client=com_socialads',
			$vName == 'cities'
		);
	}

	/**
	 * Gets a list of the actions that can be performed.
	 *
	 * @return  JObject
	 *
	 * @since  1.6
	 */
	public static function getActions()
	{
		$user = Factory::getUser();
		$result = new CMSObject;

		$assetName = 'com_socialads';

		$actions = array(
			'core.admin', 'core.manage', 'core.create', 'core.edit', 'core.edit.own', 'core.edit.state', 'core.delete'
		);

		foreach ($actions as $action)
		{
			$result->set($action, $user->authorise($action, $assetName));
		}

		return $result;
	}

	/**
	 * Get all jtext for javascript
	 *
	 * @return   void
	 *
	 * @since   1.0
	 */
	public static function getLanguageConstant()
	{
		// For number valiation
		Text::script('COM_SOCIALADS_ZERO_VALUE_VALI_MSG');
		Text::script('COM_SOCIALADS_NUMONLY_VALUE_VALI_MSG');

		// For date valiation
		Text::script('COM_SOCIALADS_DATE_START_ERROR_MSG');
		Text::script('COM_SOCIALADS_DATE_END_ERROR_MSG');
		Text::script('COM_SOCIALADS_MAX_USES_ERROR_MSG');
		Text::script('COM_SOCIALADS_DATE_ERROR_MSG');
		Text::script('JGLOBAL_VALIDATION_FORM_FAILED');

		// For Zone validation
		Text::script('COM_SOCIALADS_YOU_MUST_PROVIDE_A_MAX_TITLE_CHAR');
		Text::script('COM_SOCIALADS_VALIDATE_NON_ZERO_NUMERIC');
		Text::script('COM_SOCIALADS_YOU_MUST_PROVIDE_A_MAX_DESC_CHAR');
		Text::script('COM_SOCIALADS_YOU_PROVIDE_A_IMG_HEIGHT');
		Text::script('COM_SOCIALADS_YOU_PROVIDE_A_IMG_WIDTH');
		Text::script('JGLOBAL_VALIDATION_FORM_FAILED');
		Text::script('COM_SOCIALADS_ZONE_DEL_SURE_MSG');
		Text::script('COM_SOCIALADS_ZONE_DEL_NOT_ABLE_TO_DELETE');
		Text::script('COM_SOCIALADS_ZONE_MSG_ON_EDIT_ZONE');

		// For dashboard
		Text::script('COM_SOCIALADS_AMOUNT');
		Text::script('COM_SOCIALADS_PENDING_ORDERS');
		Text::script('COM_SOCIALADS_CONFIRMED_ORDERS');
		Text::script('COM_SOCIALADS_REJECTED_ORDERS');
		Text::script('COM_SOCIALADS_CANCELLED_ORDERS');
		Text::script('COM_SOCIALADS_DATE_ERROR_MSG_DASHBOARD');
		Text::script('COM_SOCIALADS_ERROR_LOADING_FEEDS');

		// Days
		Text::script('SUN');
		Text::script('MON');
		Text::script('TUE');
		Text::script('WED');
		Text::script('THU');
		Text::script('FRI');
		Text::script('SAT');

		// Months
		Text::script('JANUARY_SHORT');
		Text::script('FEBRUARY_SHORT');
		Text::script('MARCH_SHORT');
		Text::script('APRIL_SHORT');
		Text::script('MAY_SHORT');
		Text::script('JUNE_SHORT');
		Text::script('JULY_SHORT');
		Text::script('AUGUST_SHORT');
		Text::script('SEPTEMBER_SHORT');
		Text::script('OCTOBER_SHORT');
		Text::script('NOVEMBER_SHORT');
		Text::script('DECEMBER_SHORT');

		// Ads
		Text::script('COM_SOCIALADS_ADS_DELETE_CONFIRM');
		Text::script('COM_SOCIALADS_ADS_AD_ORDER_DELETE_CONFIRM');
		Text::script('COM_SOCIALADS_ADS_STATUS_PROMPT_BOX');
		Text::script('COM_SOCIALADS_ADS_STATUS_CANCEL_PROMPT_BOX');
		Text::script('COM_SOCIALADS_ADS_STATUS_REFUND_PROMPT_BOX');

		// Create ad
		Text::script('COM_SOCIALADS_ERR_MSG_FILE_BIG_JS');
		Text::script('COM_SOCIALADS_ERR_MSG_FILE_ALLOW');
		Text::script('COM_SOCIALADS_SELECT_CAMPAIGN');
		Text::script('COM_SOCIALADS_SOCIAL_ESTIMATED_REACH_HEAD');
		Text::script('COM_SOCIALADS_SOCIAL_ESTIMATED_REACH_END');
		Text::script('COM_SOCIALADS_CANCEL_AD');
		Text::script('COM_SOCIALADS_URL_VALID');
		Text::script('COM_SOCIALADS_TITLE_VALID');
		Text::script('COM_SOCIALADS_BODY_VALID');
		Text::script('COM_SOCIALADS_MEDIA_VALID');
		Text::script('COM_SOCIALADS_RATE_PER_CLICK');
		Text::script('COM_SOCIALADS_RATE_PER_IMP');
		Text::script('COM_SOCIALADS_ENTERING_TITLE_ERROR');
		Text::script('COM_SOCIALADS_CHAR_REMAINING');

		Text::script('COM_SOCIALADS_TOTAL_SHOULDBE_VALID_VALUE');
		Text::script('COM_SOCIALADS_ENTER_COP_COD');
		Text::script('COM_SOCIALADS_COP_EXISTS');
		Text::script('COM_SOCIALADS_COP_UNPUBLISHED');
		Text::script('COM_SOCIALADS_COP_MAX_USE_EXCEEDED');
		Text::script('COM_SOCIALADS_COP_MAX_USE_PER_USER_EXCEEDED');
		Text::script('COM_SOCIALADS_COP_NOT_YET_ACTIVE');
		Text::script('COM_SOCIALADS_COP_EXPIRED');
		Text::script('COM_SOCIALADS_COP_CODE_CANT_APPLIED');
		Text::script('SA_RENEW_RECURR');
		Text::script('SA_RENEW_NO_RECURR');
		Text::script('COM_SOCIALADS_AD_CHARGE_TOTAL_DAYS_FOR_RENEWAL');
		Text::script('TOTAL');
		Text::script('POINTS_AVAILABLE');
		Text::script('POINT');
		Text::script('COM_SOCIALADS_AD_NUMBER_OF');
		Text::script('COM_SOCIALADS_AD_SELECT_CAMPAIGN');
		Text::script('COM_SOCIALADS_AD_SELECT_ZONE');
		Text::script('COM_SOCIALADS_AD_ENTER_CAMPAIGN');
		Text::script('COM_SOCIALADS_AD_ALLOWED_BUDGET');
		Text::script('COM_SOCIALADS_ACCEPT_TERMS');
		Text::script('COM_SOCIALADS_GEO_TARGETING_COUNTRY_REQUIRED');
		Text::script('COM_SOCIALADS_GEO_TARGETING_REGION_REQUIRED');
		Text::script('COM_SOCIALADS_GEO_TARGETING_CITY_REQUIRED');
		Text::script('COM_SOCIALAD_CAMPAIGN_ARE_UNPUBLISHED');
		Text::script('COM_SOCIALADS_DAILY_BUDGET');
		Text::script('COM_SOCIALADS_START_DATE');
		Text::script('COM_SOCIALADS_END_DATE');
		Text::script('COM_SOCIALADS_DEFAULT_MSG');

		// Importfields
		Text::script('COM_SOCIALADS_SOCIAL_TARGETING_CONFIG_JSMESSAGE');
		Text::script('COM_SOCIALADS_SOCIAL_TARGETING_CONFIG_JSMESSAGE1');

		// Coupons
		Text::script('COM_SOCIALADS_COUPONS_DELETE_CONFORMATION');
		Text::script('COM_SOCIALADS_DUPLICATE_COUPON');
		Text::script('COM_SOCIALADS_AD_PRICING_OPTION');

		// Campaigns
		Text::script('COM_SOCIALADS_CAMPAIGNS_DELETE_CONFIRM');

		// Date format
		Text::script('DATE_FORMAT_FILTER_DATETIME');

		Text::script('COM_SOCIALADS_START_DATE_MUST_BE_LESS_FROM_END_DATE');
	
		Text::script('COM_SOCIALADS_ERR_SOMETHING_WENT_WRONG');
	}
}
