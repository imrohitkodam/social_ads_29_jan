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
use Joomla\CMS\Language\Text;

use Joomla\CMS\Factory;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\Registry\Registry;

// Component Helper
jimport('techjoomla.common');

/**
 * Helper class
 *
 * @since  1.6
 */
abstract class SaCommonHelper
{
	/**
	 * function to get an ItemId
	 *
	 * @param   string  $view  eg. managead&layout=list
	 *
	 * @return  int  itemId
	 *
	 * @since 1.6
	 **/
	public static function getSocialadsItemid($view = '')
	{
		$app   = Factory::getApplication();
		$input = $app->input;

		if ($view == 'user')
		{
			$techjoomlaCommon = new TechjoomlaCommon;
			$itemId = $techjoomlaCommon->getItemId('index.php?option=com_users&view=login');

			return $itemId;
		}

		if ($view && !($app->isClient("administrator")))
		{
			$items = $app->getMenu()->getItems('link', "index.php?option=com_socialads&view=$view");

			$itemId = (isset($items[0])) ? $items[0]->id : $input->get('Itemid', 0, 'INT');
		}
		else
		{
			$db    = Factory::getDbo();
			$query = $db->getQuery(true);
			$query->select($db->quoteName('id'));
			$query->from($db->quoteName('#__menu'));
			$query->where($db->quoteName('link') . ' LIKE ' . $db->quote('%' . 'index.php?option=com_socialads&view=' . $view . '%'));
			$query->where($db->quoteName('published') . ' = ' . 1);

			$db->setQuery($query);

			$itemId = $db->loadResult();
		}

		return $itemId;
	}

	/**
	 * For payment of coformed orders
	 *
	 * @param   integer  $order_id  Order id of of order
	 *
	 * @return  void
	 *
	 * @since 1.6
	 **/
	public static function new_pay_mail($order_id)
	{
		if (empty($order_id) || $order_id <= 0 )
		{
			return;
		}

		$mainframe = Factory::getApplication();
		require_once JPATH_SITE . "/components/com_socialads/helper.php";

		// Require when we call from backend
		$SocialadsFrontendhelper = new SocialadsFrontendhelper;
		$db = Factory::getDbo();
		$query	= $db->getQuery(true);
		$query->select($db->quoteName(array('p.payee_id', 'u.username', 'u.email', 'p.status')));
		$query->from($db->quoteName('#__ad_orders', 'p'));
		$query->from($db->quoteName('#__users', 'u'));
		$query->where($db->quoteName('p.payee_id') . ' = ' . $db->quoteName('u.id'));
		$query->where($db->quoteName('p.id') . ' = ' . $order_id);

		$db->setQuery($query);
		$result	= $db->loadObject();
		$body = Text::_('COM_SOCIALADS_INVOICE_PAY_PAYMENT_BODY');
		$find = array ('[SEND_TO_NAME]', '[ORDERID]', '[SITENAME]', '[STATUS]');

			if ($result->status == 'P')
			{
				$orderstatus = Text::_('ADS_INVOICE_STATUS_PENDING');
			}
			elseif ($result->status == 'RF')
			{
				$orderstatus = Text::_('COM_SOCIALADS_AD_REFUND');
			}
			else
			{
				$orderstatus = Text::_('ADS_INVOICE_AMOUNT_CANCELLED');
			}

		$recipient = $result->email;
		$siteName = $mainframe->getCfg('sitename');
		$displayOrderid = sprintf("%05s", $order_id);
		$replace = array($result->username, $displayOrderid, $siteName, $orderstatus);
		$body = str_replace($find, $replace, $body);
		$subject = Text::sprintf("COM_SOCIALADS_STATUS_CHANGED_MAIL_SUBJECT", $displayOrderid);

		$status  = self::sendmail($recipient, $subject, $body, '', 0, "");
	}

	/**
	 * General send mail function
	 *
	 * @param   string   $recipient       recipient of mail
	 * @param   string   $subject         subject of mail
	 * @param   string   $body            body of mail
	 * @param   string   $bcc_string      bcc_string of mail
	 * @param   integer  $singlemail      singlemail of mail
	 * @param   string   $attachmentPath  attachmentPath of mail
	 *
	 * @return  email
	 *
	 * @since 1.6
	 **/
	public static function sendmail($recipient,$subject,$body,$bcc_string,$singlemail=1,$attachmentPath="")
	{
		global $mainframe;
		$mainframe = Factory::getApplication();
		$from = $mainframe->getCfg('mailfrom');
		$fromname = $mainframe->getCfg('fromname');
		$recipient = explode(",", $recipient);
		$mode = 1;
		$cc = null;
		$bcc = array();

		if ($singlemail == 1)
		{
			if ($bcc_string)
			{
				$bcc = explode(',', $bcc_string);
			}
			else
			{
				$bcc = array('0' => $mainframe->getCfg('mailfrom') );
			}
		}

		$attachment = null;

		if (!empty($attachmentPath))
		{
			$attachment = $attachmentPath;
		}

		$replyto = null;
		$replytoname = null;

		return	Factory::getMailer()->sendMail($from, $fromname, $recipient, $subject, $body, $mode, $cc, $bcc, $attachment, $replyto, $replytoname);
	}

	/**
	 * checks for view override
	 *
	 * @param   string  $viewname       name of view
	 * @param   string  $layout         layout name eg order
	 * @param   string  $searchTmpPath  it may be admin or site. it is side(admin/site) where to search override view
	 * @param   string  $useViewpath    it may be admin or site. it is side(admin/site) which VIEW shuld be use IF OVERRIDE IS NOT FOUND
	 *
	 * @return  string  if exit override view then return path
	 *
	 * @since  1.6
	 **/
	public static function getViewpath($viewname, $layout="", $searchTmpPath='SITE', $useViewpath='SITE')
	{
		$searchTmpPath = ($searchTmpPath == 'SITE')?JPATH_SITE:JPATH_ADMINISTRATOR;
		$useViewpath = ($useViewpath == 'SITE')?JPATH_SITE:JPATH_ADMINISTRATOR;
		$app = Factory::getApplication();

		if (!empty($layout))
		{
			$layoutname = $layout . '.php';
		}
		else
		{
			$layoutname = "default.php";
		}

		$override = $searchTmpPath . '/templates/' . $app->getTemplate() . '/html/com_socialads/' . $viewname . '/' . $layoutname;

		if (File::exists($override))
		{
			return $view = $override;
		}
		else
		{
			return $view = $useViewpath . '/components/com_socialads/views/' . $viewname . '/tmpl' . '/' . $layoutname;
		}
	}
	// End of getViewpath()

	/**
	 * Function to get ad info
	 *
	 * @param   integer  $adId     Id of a ad
	 * @param   string   $columns  ad information
	 *
	 * @return  array
	 *
	 * @since  1.6
	 */
	public static function getAdInfo($adId = 0, $columns = '*')
	{
		$db    = Factory::getDbo();
		$query = $db->getQuery(true);
		$query->select($columns);
		$query->from($db->quoteName('#__ad_data'));
		$query->where($db->quoteName('ad_id') . ' = ' . (int) $adId);

		$db->setQuery($query);

		return $db->loadObject();
	}

	/**
	 * Function to get param table names
	 *
	 * @param   string  $tablename  table name
	 *
	 * @return  Boolean
	 *
	 * @since  1.6
	 */
	public static function getTableColumns($tablename)
	{
		$db       = Factory::getDBO();
		$app      = Factory::getApplication();
		$dbprefix = $app->getCfg('dbprefix');

		// Use of $dbprefix is important here
		$query = "SHOW TABLES LIKE '" . $dbprefix . $tablename . "'";
		$db->setQuery($query);
		$isTableExist = $db->loadResult();

		$paramlist = array();

		if ($isTableExist)
		{
			$query_to_get_column = "SHOW COLUMNS FROM #__" . $tablename;
			$db->setQuery($query_to_get_column);
			$paramlist = $db->loadColumn();
		}

		return $paramlist;
	}

	/**
	 * This will load any javascript only once
	 *
	 * @param   string  $script  script name
	 *
	 * @return  Void
	 *
	 * @since  1.6
	 */
	public static function loadScriptOnce($script)
	{
		$doc = Factory::getDocument();
		$versionObj = new SaVersion;
		$options = array("version" => $versionObj->getMediaVersion());
		$flg = 0;

		foreach ($doc->_scripts as $name => $ar)
		{
			if ($name == $script)
			{
				$flg = 1;
				break;
			}
		}

		if ($flg == 0)
		{
			HTMLHelper::script($script, $options);
		}
	}

	/**
	 * Function to check social integration is install or not
	 *
	 * @return  Integer|null
	 *
	 * @since  1.6
	 */
	public static function checkForSocialIntegration()
	{
		$params = ComponentHelper::getParams('com_socialads');

		$integration = $params->get('social_integration');

		if ($integration == 'Community Builder')
		{
			$integration = "CB";
		}
		elseif($integration == 'JomSocial')
		{
			$integration = "JS";
		}
		elseif($integration == 'EasySocial')
		{
			$integration = "ES";
		}

		switch ($integration)
		{
			case 'CB':
				$cbpath = JPATH_ROOT . '/components/com_comprofiler';

				if (Folder::exists($cbpath))
				{
					return 1;
				}
				else
				{
					return null;
				}
				break;

			case 'JS':
				$jspath = JPATH_ROOT . '/components/com_community';

				if (file_exists($jspath))
				{
					return 1;
				}
				else
				{
					return null;
				}

			break;

			case 'ES':
				$jspath = JPATH_ROOT . '/components/com_easysocial';

				if (file_exists($jspath))
				{
					return 1;
				}
				else
				{
					return null;
				}
			break;
		}
	}

	/**
	 * Returns months to current month from last year
	 *
	 * @return  list of months
	 *
	 * @since   2.2
	 */
	public static function getAllmonths()
	{
		$date2 = date('Y-m-d');

		// Lets time travel, back to previous year, same month, same day!
		$date  = strtotime($date2 . ' -1 year');
		$date1 = date('Y-m-d', $date);

		// Convert dates to UNIX timestamp
		$time1 = strtotime($date1);
		$time2 = strtotime($date2);
		$tmp   = date('mY', $time2);

		// ** Line below results into fetching 13 months instead of 12
		// $months[] = array("month" => date('M', $time1), "digitmonth" => date('m', $time1),"amount" => 0);

		while ($time1 < $time2)
		{
			$time1 = strtotime(date('Y-m-d', $time1) . ' +1 month');

			if (date('mY', $time1) != $tmp && ($time1 < $time2))
			{
				$months[] = array("month" => date('M', $time1),
					"digitmonth" => date('m', $time1),
					"amount" => 0
				);
			}
		}

		$months[] = array("month" => date('M', $time2),"digitmonth" => date('m', $time2),"amount" => 0);

		return $months;
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
		Text::script('COM_SOCIALADS_PAYMENT_MIN_AMT_SHOULD_GREATER_MSG');
		Text::script('COM_SOCIALAD_PAYMENT_ENTER_NUMERICS');
		Text::script('COM_SOCIALADS_PAYMENT_MIN_AMT_TO_PAY');
		Text::script('COM_SOCIALAD_PAYMENT_ENTER_COUPON_CODE');
		Text::script('COM_SOCIALAD_PAYMENT_COUPON_CODE_IN_PERCENT');
		Text::script('COM_SOCIALADS_PAYMENT_COUPON_NOT_EXISTS');
		Text::script('COM_SOCIALADS_SUBMIT');
		Text::script('COM_SOCIALADS_PAYMENT_ENTER_CORRECT_AMT');
		Text::script('COM_SOCIALAD_PAYMENT_GATEWAY_LOADING_MSG');

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
		Text::script('COM_SOCIALADS_ENTER_COP_COD');
		Text::script('COM_SOCIALADS_COP_EXISTS');
		Text::script('COM_SOCIALADS_COP_UNPUBLISHED');
		Text::script('COM_SOCIALADS_COP_MAX_USE_EXCEEDED');
		Text::script('COM_SOCIALADS_COP_MAX_USE_PER_USER_EXCEEDED');
		Text::script('COM_SOCIALADS_COP_NOT_YET_ACTIVE');
		Text::script('COM_SOCIALADS_COP_EXPIRED');
		Text::script('SA_RENEW_RECURR');
		Text::script('SA_RENEW_NO_RECURR');
		Text::script('COM_SOCIALADS_AD_CHARGE_TOTAL_DAYS_FOR_RENEWAL');
		Text::script('TOTAL');
		Text::script('POINTS_AVAILABLE');
		Text::script('POINT');
		Text::script('COM_SOCIALADS_TOTAL_SHOULDBE_VALID_VALUE');
		Text::script('COM_SOCIALADS_COP_CODE_CANT_APPLIED');
		Text::script('COM_SOCIALADS_AD_NUMBER_OF');
		Text::script('COM_SOCIALADS_AD_SELECT_CAMPAIGN');
		Text::script('COM_SOCIALADS_AD_SELECT_ZONE');
		Text::script('COM_SOCIALADS_AD_ENTER_CAMPAIGN');
		Text::script('COM_SOCIALADS_AD_ALLOWED_BUDGET');
		Text::script('COM_SOCIALADS_MINIMUM_AMOUNT_ERROR');
		Text::script('COM_SOCIALADS_ACCEPT_TERMS');
		Text::script('COM_SOCIALADS_GEO_TARGETING_COUNTRY_REQUIRED');
		Text::script('COM_SOCIALADS_GEO_TARGETING_REGION_REQUIRED');
		Text::script('COM_SOCIALADS_GEO_TARGETING_CITY_REQUIRED');
		Text::script('COM_SOCIALADS_CHAR_LIMIT');
		Text::script('COM_SOCIALAD_CAMPAIGN_ARE_UNPUBLISHED');
		Text::script('COM_SOCIALADS_ENTERING_TITLE_ERROR');
		Text::script('COM_SOCIALADS_CHAR_REMAINING');
		Text::script('COM_SOCIALADS_DAILY_BUDGET');
		Text::script('COM_SOCIALADS_START_DATE');
		Text::script('COM_SOCIALADS_END_DATE');
		Text::script('COM_SOCIALADS_DEFAULT_MSG');
		
		// Ads
		Text::script('COM_SOCIALADS_SA_MAKE_SEL');
		Text::script('COM_SOCIALADS_DELETE_AD');
		Text::script('COM_SOCIALADS_PROMOTE_PLUGIN_AD_UPLOAD_MEDIA');

		// Campaigns
		Text::script('COM_SOCIALADS_CAMPAIGNS_DELETE_CONFIRM');
		Text::script('COM_SOCIALADS_DELETE_MESSAGE');
		Text::script('COM_SOCIALADS_AD_PRICING_OPTION');
		Text::script('COM_SOCIALADS_START_DATE_MUST_BE_LESS_FROM_END_DATE');

		// Wallet
		Text::script('COM_SOCIALADS_WALLET_COUPON_ADDED_SUCCESS');
		Text::script('COM_SOCIALADS_WALLET_COUPON_NOT_ADDED');

		// Date format
		Text::script('DATE_FORMAT_FILTER_DATETIME');
		Text::script('COM_SOCIALADS_DATE_ERROR_MSG_DASHBOARD');
	}

	/**
	 * Get currency symbol
	 *
	 * @param   string  $currency  Currency
	 *
	 * @return  currency symbol
	 *
	 * @since       1.7
	 */
	public static function getCurrencySymbol($currency = '')
	{
		$params   = ComponentHelper::getParams('com_socialads');
		$currencySymbol = $params->get('currency_symbol');

		if (empty($currencySymbol))
		{
			$currencySymbol = $params->get('currency');
		}

		return $currencySymbol;
	}

	/**
	 * Push to activity stream
	 *
	 * @param   float   $price     Amount
	 * @param   string  $curr      Currency
	 * @param   int     $decimals  No. of decimal digits to show
	 *
	 * @return formatted price-currency string
	 *
	 * @since       1.7
	 */
	public static function getFormattedPrice($price, $curr = null, $decimals = null)
	{
		$currencySymbol           = self::getCurrencySymbol();
		$params                   = ComponentHelper::getParams('com_socialads');
		$currency                 = $params->get('currency');
		$currencyDisplayFormat    = $params->get('currency_display_format');

		if(!$decimals)
		{
			$decimals = $params->get('decimals_count', 2);
		}

		// Remove currency symbol, comma, spaces from price just in case
		$price = $price ? $price : '';
		$price = str_replace($currencySymbol, '', $price);
		$price = str_replace('&nbsp;', '', $price);
		$price = str_replace(',', '', $price);

		if (is_float($price))
		{
			$price = number_format($price, $decimals);
		}
		else
		{
			$price = number_format((float) $price, $decimals);
		}

		$currencyDisplayFormatStr = '';
		$currencyDisplayFormatStr = str_replace('{AMOUNT}', "&nbsp;" . $price, $currencyDisplayFormat);
		$currencyDisplayFormatStr = str_replace('{CURRENCY_SYMBOL}', "&nbsp;" . $currencySymbol, $currencyDisplayFormatStr);
		$currencyDisplayFormatStr = str_replace('{CURRENCY}', "&nbsp;" . $currency, $currencyDisplayFormatStr);

		// $html                     = '';
		// $html                     = "<span>" . $currencyDisplayFormatStr . " </span>";

		return $currencyDisplayFormatStr;
	}

	/**
	 * Get sites/administrator default template
	 *
	 * @param   mixed  $client  0 for site and 1 for admin template
	 *
	 * @return  json
	 *
	 * @since   1.5
	 */
	public static function getSiteDefaultTemplate($client = 0)
	{
		try
		{
			$db    = Factory::getDBO();

			// Get current status for Unset previous template from being default
			// For front end => client_id=0
			$query = $db->getQuery(true)
						->select($db->quoteName('template'))
						->from($db->quoteName('#__template_styles'))
						->where($db->quoteName('client_id') . ' = ' . $client)
						->where($db->quoteName('home') . ' = ' . 1);
			$db->setQuery($query);

			return $db->loadResult();
		}
		catch (Exception $e)
		{
			return '';
		}
	}

	/**
	 * Get ad creator name
	 *
	 * @return  array
	 *
	 * @since   1.5
	 */
	public static function getAdCreators()
	{
		$db = Factory::getDBO();
		$query = $db->getQuery(true);
		$query->select('DISTINCT (created_by)');
		$query->from($db->quoteName('#__ad_data'));
		$query->where($db->quoteName('state') . " = 1");
		$query->where($db->quoteName('ad_approved') . " = 1");
		$db->setQuery($query);

		return $db->loadColumn();
	}

	/**
	 * Get sites/administrator default template
	 *
	 * @param   integer  $user_id  User ID
	 *
	 * @return  json
	 *
	 * @since   1.5
	 */
	public static function statsForPieInMail($user_id = '')
	{
		$db = Factory::getDBO();
		$statsforpie = array();
		$socialads_from_date = date('Y-m-d H:i:s', strtotime(date('Y-m-d H:i:s') . ' - 7 days'));
		$socialads_end_date = date('Y-m-d');
		$where = '';
		$groupby = '';

		$query_data	= $db->getQuery(true);
		$query_data->select($db->quoteName('ad_id'));
		$query_data->from($db->quoteName('#__ad_data'));
		$query_data->where($db->quoteName('created_by') . ' = ' . $user_id);

		$db->setQuery($query_data);
		$adids = $db->loadColumn();
		$total_no_ads = count($adids);
		$cnt = 0;

		foreach ($adids as $adid)
		{
			$where = " AND DATE(time) BETWEEN DATE('" . $socialads_from_date . "') AND DATE('" . $socialads_end_date . "') AND ad_id=" . $adid;
			$arch_where = " AND DATE(date) BETWEEN DATE('" . $socialads_from_date . "') AND DATE('" . $socialads_end_date . "')";
			$groupby = "  GROUP BY DATE(time)";
			$query = " SELECT COUNT(id) as value,DAY(time) as day,MONTH(time) as month
					FROM #__ad_stats
					WHERE display_type = 0  " . $where;
			$db->setQuery($query);

			// Impression
			$statsforpie[$cnt][0] = $db->loadObjectList();

			// Query for archive
			$query = " SELECT SUM(impression) as value,DAY(date) as day,MONTH(date) as month
						FROM #__ad_archive_stats
						WHERE  impression<>0 AND ad_id = " . $adid . $arch_where;
			$db->setQuery($query);
			$acrh_imp_statistics = $db->loadObjectList();

			if (isset($acrh_imp_statistics[0]->value) && $acrh_imp_statistics[0]->value)
			{
				$statsforpie[$cnt][0][0]->value += $acrh_imp_statistics[0]->value;
			}

			// EOC for archive*/

			$query = "SELECT COUNT(id) as value,DAY(time) as day,MONTH(time) as month
				FROM #__ad_stats
				WHERE display_type = 1 " . $where;
			$db->setQuery($query);

			// Clicks
			$statsforpie[$cnt][1] = $db->loadObjectList();

			// Query for archive
			$query = " SELECT SUM(click) as value,DAY(date) as day,MONTH(date) as month,YEAR(date) as year
						FROM #__ad_archive_stats
						WHERE  click<>0 AND ad_id = " . $adid . $arch_where;
			$db->setQuery($query);
			$acrh_clk_statistics = $db->loadObjectList();

			if (isset($acrh_clk_statistics[0]->value) && $acrh_clk_statistics[0]->value)
			{
				$statsforpie[$cnt][1][0]->value += $acrh_clk_statistics[0]->value;
			}

			// Eoc for archive*/

			$statsforpie[$cnt][2] = $adid;

			$cnt++;
		}

		return $statsforpie;
	}

	/**
	 * Get user details
	 *
	 * @param   integer  $userId   User ID
	 * @param   string   $columns  Details required
	 *
	 * @return  object
	 *
	 * @since   1.5
	 */
	public static function getUserDetails($userId, $columns)
	{
		$db    = Factory::getDBO();
		$query = $db->getQuery(true);
		$query->select($columns);
		$query->from($db->quoteName('#__users'));
		$query->where($db->quoteName('id') . " = " . (int) $userId);

		$db->setQuery($query);

		return $db->loadObject();
	}

	/**
	 * This function return plugin name from plugin params.
	 *
	 * @param   string  $plgname  Plugin name.
	 * @param   string  $type     Plugin type eg payment.
	 *
	 * @since   2.2
	 * @return   string
	 */
	public static function getPluginName($plgname, $type = 'payment')
	{
		if (empty($plgname))
		{
			return $plgname;
		}

		$plugin = PluginHelper::getPlugin($type, $plgname);

		if (empty($plugin))
		{
			return $plgname;
		}

		$params = json_decode($plugin->params);

		if (!empty($params->plugin_name))
		{
			$plgname = $params->plugin_name;
		}

		return $plgname;
	}

	/**
	 * This function return impression and clicks based on time.
	 *
	 * @param   int  $adId  Ad Id.
	 * @param   int  $from  from time.
	 * @param   int  $to    to time.
	 *
	 * @since   2.2
	 * @return   array
	 */
	public static function getImpressionAndClicks($adId, $from = null, $to = null)
	{
		$data         = [];
		if ($from || $to)
		{
			$db3   = Factory::getDBO();
			$query = $db3->getQuery(true);
			$query->select('COUNT(*)');
			$query->from($db3->quoteName('#__ad_stats'));
			$query->where($db3->qn('ad_id') . ' = ' . (int) $adId);
			$query->where($db3->qn('display_type') . ' = ' . 0);
			$query->where($db3->qn('time') . ' IS NOT NULL');

			if ($from)
			{
				$query->where('Date('.$db3->quoteName('time').')' . ' >= '.  $db3->quote( $from ));
			}

			if ($to)
			{
				$query->where('Date('.$db3->quoteName('time').')' . ' <= '.  $db3->quote( $to ));
			}

			$db3->setQuery($query);

			$impr = $db3->loadresult();

			$db2   = Factory::getDBO();
			$query = $db2->getQuery(true);
			$query->select('COUNT(*)');
			$query->from($db2->quoteName('#__ad_stats'));
			$query->where($db2->qn('ad_id') . ' = ' . (int) $adId);
			$query->where($db2->qn('display_type') . ' = ' . (int) 1);
			$query->where($db2->qn('time') . ' IS NOT NULL');

			if ($from)
			{
				$query->where('Date('.$db2->quoteName('time').')' . ' >= '.  $db2->quote( $from ));
			}

			if ($to)
			{
				$query->where('Date('.$db2->quoteName('time').')' . ' <= '.  $db2->quote( $to ));
			}

			$db2->setQuery($query);

			$clicks = $db2->loadresult();

			$data = [
				'imp' => $impr,
				'clicks' => $clicks
			];

		}
		else
		{
			$data = [
				'imp' => 0,
				'clicks' => 0
			];
		}


		return $data;
	}

	/**
	 *
	 * @param   float   $Adorder     Amount
	 *
	 * @return formatted start Date of perticular Ad order
	 *
	 * @since       1.7
	 */
	public static function getStartDateofAd($adOrder)
	{
		$db = Factory::getDbo();

		$query = $db->getQuery(true);
		$query->select('SUM(p.ad_credits_qty)');
		$query->from('`#__ad_payment_info` AS p');
		$query->join('LEFT', $db->quoteName('#__ad_orders', 'ao') . 'ON' . $db->quoteName('p.order_id') . '=' . $db->quoteName('ao.id'));
		$query->where($db->quoteName('p.order_id') . ' < ' . (int) $adOrder->order_id);
		$query->where($db->quoteName('p.ad_id') . ' = ' . (int) $adOrder->ad_id);
		$query->where($db->quoteName('ao.status') . ' = ' . $db->quote('C'));

		$db->setQuery($query);
		$noOfDays = $db->loadresult();

		if ($noOfDays)
		{
			$startDate = strtotime(date("Y-m-d", strtotime($adOrder->ad_startdate)) . " + " . $noOfDays . " day");
			$startDate   = date("Y-m-d", $startDate);
		}
		else 
		{
			$startDate   = $adOrder->ad_startdate;
		}

		return $startDate;
	}

	/**
	 *
	 * @param   float   $Adorder     Amount
	 *
	 * @return formatted End date of perticular Ad order
	 *
	 * @since       1.7
	 */
	public static function getEndDateofAd($adOrder)
	{
		if ($adOrder->ad_enddate && $adOrder->status == 'C')
		{
			$db = Factory::getDbo();

			$query = $db->getQuery(true);
			$query->select('SUM(p.ad_credits_qty)');
			$query->from('`#__ad_payment_info` AS p');
			$query->join('LEFT', $db->quoteName('#__ad_orders', 'ao') . 'ON' . $db->quoteName('p.order_id') . '=' . $db->quoteName('ao.id'));
			$query->where($db->quoteName('p.order_id') . ' > ' . (int) $adOrder->order_id);
			$query->where($db->quoteName('p.ad_id') . ' = ' . (int) $adOrder->ad_id);
			$query->where($db->quoteName('ao.status') . ' = ' . $db->quote('C'));

			$db->setQuery($query);
			$noOfDays = $db->loadresult();

			if ($noOfDays)
			{
				$endDate   = strtotime(date("Y-m-d", strtotime($adOrder->ad_enddate)) . " - " . $noOfDays . " day");
				$endDate   = date("Y-m-d", $endDate);
			}
			else
			{
				$endDate   = $adOrder->ad_enddate;
			}

			return $endDate;
		}

		return '-';
	}
}
