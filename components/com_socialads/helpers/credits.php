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

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Router\Route;
use Joomla\CMS\HTML\HTMLHelper;

/**
 * Ads Helper class
 *
 * @package  SocialAds
 *
 * @since    3.1
 */
class SaCreditsHelper
{
	/**
	 * Reduces the credits
	 *
	 * @param   string   $adid       Ad ID
	 * @param   integer  $caltype    call type caltype= 0 imprs; caltype =1 clks;
	 * @param   integer  $ad_charge  ad charge
	 * @param   string   $widget     widget
	 *
	 * @return  string
	 *
	 * @since  1.0
	 **/
	public function reduceCredits($adid, $caltype, $ad_charge, $widget = "")
	{
		/**
		 * $caltype integer (0 = impression, 1 = click)
		 * Return if a value other than the above two is set to the $caltype variable.
		 */
		if (!in_array($caltype, array(0, 1)))
		{
			return;
		}

		$saParams = ComponentHelper::getParams('com_socialads');

		$mainframe = Factory::getApplication();
		$db        = Factory::getDbo();
		$user      = Factory::getUser();
		$userid    = $user->id;
		$saStatsHelper = new SaStatsHelper;

		/* Load language file for plugin frontend*/
		$lang = Factory::getLanguage();
		$lang->load('com_socialads', JPATH_SITE);

		// ^ ad_creator => created_by
		$query = $db->getQuery(true);
		$query->select($db->quoteName(array('ad_payment_type', 'ad_alternative', 'ad_affiliate', 'ad_noexpiry', 'created_by')));
		$query->from($db->quoteName('#__ad_data'));
		$query->where($db->quoteName('ad_id') . ' = ' . $adid);

		$db->setQuery($query);

		list($result, $alter, $affiliate, $unltd, $creator) = $db->loadRow();

		// Get user's Ip Address
		JLoader::import('components.com_socialads.helpers.tjgeoloc', JPATH_SITE);
		$ip = TJGeoLocationHelper::getUserIP();

		$query = $db->getQuery(true);
		$query->select('COUNT(*)');
		$query->from($db->quoteName('#__ad_stats'));
		$query->where($db->quoteName('ad_id') . ' = ' . $adid);
		$query->where($db->quoteName('ip_address') . ' = ' . $db->q($ip));
		$query->where($db->quoteName('display_type') . ' = ' . $caltype);

		$sql = "time > UTC_TIMESTAMP()-INTERVAL ";

		// Include the time interval for clicks if cal is for the clicks ad...
		if ($caltype == 1)
		{
			$sql .= $saParams->get('interval_clicks') . " SECOND";
		}
		else
		{
			$sql .= $saParams->get('interval_impressions') . " SECOND";
		}

		$query->where($sql);
		$db->setQuery($query);
		$ipresult = $db->loadResult();

		if ($ipresult > 0)
		{
			return;
		}

		// $adRetriever = new adRetriever();
		if ($caltype == 0 && $result == 1)
		{
			$ad_charge = 0.00;
		}

		if ($saParams->get('payment_mode') == 'wallet_mode')
		{
			if ($ipresult < 1)
			{
				if ($creator != $userid)
				{
					// Reduce credits for impressions
					if (($result == 0 || $result == 4) && $alter == 0 && $affiliate == 0 && $unltd == 0 && $caltype == 0)
					{
						$this->spentUpdate($adid, $caltype, $ad_charge);
					}
					// Reduce credits for clicks & it is called from the redirector file
					elseif (($result == 1 || $result == 4) && $alter == 0 && $affiliate == 0 && $unltd == 0 && $caltype == 1)
					{
						$this->spentUpdate($adid, $caltype, $ad_charge);
					}

					if ($alter == 1 || $unltd == 1 || $affiliate == 1)
					{
						if ($saStatsHelper->putStats($adid, $caltype, $ad_charge, $widget))
						{
							// For Task #31607 increment ad stats in independent column against the ad
							$saStatsHelper->incrementStats($adid, $caltype);
						}
					}
					else
					{
						if ($saStatsHelper->putStats($adid, $caltype, $ad_charge, $widget))
						{
							// For Task #31607 increment ad stats in independent column against the ad
							$saStatsHelper->incrementStats($adid, $caltype);
						}
					}
				}
			}

			/*
			$query = "SELECT camp_id,ad_creator FROM #__ad_data WHERE ad_id=$adid";
			$db->setQuery($query);
			$campinfo = $db->loadobjectlist();
			$ad_creator = $campinfo['0']->ad_creator;
			*/

			$query = $db->getQuery(true);
			$query->select('SUM(earn)');
			$query->from($db->quoteName('#__ad_wallet_transc'));
			$query->where($db->quoteName('user_id') . ' = ' . $creator);

			$db->setQuery($query);
			$total_amt = $db->loadresult();

			$query = "SELECT balance
			 FROM `#__ad_wallet_transc`
			 WHERE time = (select MAX(time) FROM #__ad_wallet_transc WHERE user_id = " . $creator . ")";
			$db->setQuery($query);
			$remaining_amt = $db->loadresult();

			if ($alter == 0 && $affiliate == 0 && $unltd == 0 && (($caltype == 0 && $result == 0) || ($caltype == 1 && $result == 1)))
			{
				if ($saParams->get('threshold'))
				{
					$low_val = $total_amt * ($saParams->get('threshold') / 100 );

					if ((ceil($low_val)) == $remaining_amt)
					{
						// Self::mailLowBal($adid, $saParams->get('ad_pay_mode'));
						self::mailLowBal($adid, $saParams->get('payment_mode'));
					}

					if ($remaining_amt <= 0)
					{
						PluginHelper::importPlugin('system');
						Factory::getApplication()->triggerEvent('onAfterSaAdExpire', array($adid));

						// As amount is zero camp should be paused(state = 2)
						$query1 = $db->getQuery(true);
						$fields = array($db->quoteName('state') . ' = 2');
						$conditions = array(
								$db->quoteName('state') . ' = 1',
								$db->quoteName('created_by') . ' = ' . $creator
							);
						$query1->update($db->quoteName('#__ad_campaign'))->set($fields)->where($conditions);
						$db->setQuery($query1);
						$db->execute();

						// Send ad expiry mail
						// Self::mailExpir($adid, $saParams->get('ad_pay_mode'));
						self::mailExpir($adid, $saParams->get('payment_mode'));
					}
				}
			}
		}
		else
		{
			if ($ipresult < 1)
			{
				/*$query = "SELECT ad.ad_credits_balance, api.ad_credits_qty
				 FROM #__ad_data as ad
				 LEFT JOIN #__ad_payment_info as api ON ad.ad_id = api.ad_id
				 WHERE ad.ad_id='" . $adid . "'
				 AND api.status='C'
				 ORDER BY api.mdate DESC
				 LIMIT 1";
				 */

				// @TODO - manoj, dj chk once
				$query = $db->getQuery(true);
				$query->select($db->quoteName(array('ad.ad_credits_balance', 'api.ad_credits_qty')));
				$query->from($db->quoteName('#__ad_data', 'ad'));
				$query->join('LEFT', $db->quoteName('#__ad_payment_info', 'api') . ' ON ' . $db->quoteName('ad.ad_id') . '=' . $db->quoteName('api.id'));
				$query->join('LEFT', $db->quoteName('#__ad_orders', 'ao') . ' ON ' . $db->quoteName('ao.payment_info_id') . '=' . $db->quoteName('api.id'));
				$query->where($db->quoteName('ad.ad_id') . ' = ' . $adid);
				$query->where($db->quoteName('ao.status') . ' = ' . $db->q('C'));
				$query->order($db->quoteName('ao.mdate') . ' DESC');
				$query->setLimit('1');

				$db->setQuery($query);

				// Get the balance credits and credits brought
				$credits_data = $db->loadObjectList();

				if ($creator != $userid)
				{

					// Check for bot crawling - If bot crawler is detected then dont add stats
					$botIdentifiers = array('bot', 'slurp', 'crawler', 'spider', 'curl', 'facebook', 'fetch', 'google', 'AddThis', 'bing', 'yahoo', 'wget');
					$userAgent = strtolower($_SERVER['HTTP_USER_AGENT']);
					$botFound = false;

					foreach ($botIdentifiers as $botIdentifier)
					{
						if (strpos($userAgent, strtolower($botIdentifier)) !== false)
						{
							$botFound = true;
						}
					}

					if (!$botFound)
					{
						// Reduce credits for impressions
						if ($result == 0 && $alter == 0 && $affiliate == 0 && $unltd == 0 && $caltype == 0)
						{
							$this->subCredits($adid);
						}

						// Reduce credits for clicks & it is called from the redirector file
						elseif ($result == 1 && $alter == 0 && $affiliate == 0 && $unltd == 0 && $caltype == 1)
						{
							$this->subCredits($adid);
						}
					}

					if ($alter == 0 && $affiliate == 0 && $unltd == 0 && (($caltype == 0 && $result == 0) || ($caltype == 1 && $result == 1)))
					{
						// @TODO - chk once - changed by manoj ^v3.1
						if ($saParams->get('threshold') && isset($credits_data[0]->ad_credits_qty) && $credits_data[0]->ad_credits_qty)
						{
							$low_val = $credits_data[0]->ad_credits_qty * ($saParams->get('threshold') / 100);

							if ((ceil($low_val)) == ($credits_data[0]->ad_credits_balance - 1))
							{
								// Send a Low Balance mail
								self::mailLowBal($adid, $saParams->get('payment_mode'));
							}

							if (($credits_data[0]->ad_credits_balance - 1) == 0)
							{
								PluginHelper::importPlugin('system');
								Factory::getApplication()->triggerEvent('onAfterSaAdExpire', array($adid));

								// Send a ad expiry mail
								self::mailExpir($adid, $saParams->get('payment_mode'));
							}
						}
					}

					if (!$botFound)
					{
						// Update the stats table for the ad
						if ($saStatsHelper->putStats($adid, $caltype, $ad_charge, $widget))
						{
							// For Task #31607 increment ad stats in independent column against the ad
							$saStatsHelper->incrementStats($adid, $caltype);
						}
					}
				}
			}
		}
	}

	/**
	 * Function to reduce credits
	 *
	 * @param   integer  $adid  [description]
	 *
	 * @return  void
	 *
	 * @since  3.1
	 **/
	public function subCredits($adid)
	{
		$db  = Factory::getDbo();
		$query = $db->getQuery(true);
		$query->update($db->quoteName('#__ad_data'))
			->set($db->quoteName('ad_credits_balance') . ' = ' . $db->quoteName('ad_credits_balance') . ' - ' . 1)
			->where($db->quoteName('ad_id') . ' = ' . $adid)
			->where($db->quoteName('ad_credits_balance') . ' > ' . 0);

		$db->setQuery($query);
		$db->execute();

		return;
	}

	/**
	 * Method to send update
	 *
	 * @param   integer  $adid       [description]
	 * @param   integer  $caltype    [description]
	 * @param   integer  $ad_charge  [description]
	 *
	 * @return  void
	 *
	 * @since  3.1
	 **/
	public function spentUpdate($adid, $caltype, $ad_charge)
	{
		$db = Factory::getDbo();
		$query = $db->getQuery(true);
		$query->select($db->quoteName(array('a.camp_id', 'a.ad_zone', 's.per_imp','a.created_by', 's.per_click')));
		$query->from($db->quoteName('#__ad_data', 'a'));
		$query->join('INNER', $db->quoteName('#__ad_zone', 's') . ' ON ' . $db->quoteName('s.id') . ' = ' . $db->quoteName('a.ad_zone'));
		$query->where($db->quoteName('a.ad_id') . ' = ' . (int) $adid);
		$db->setQuery($query);
		$campZone = $db->loadobjectlist();

		foreach ($campZone as $key)
		{
			$date1 = microtime(true);
			$key->c_date = $date1;
			$date2 = date('Y-m-d');
			$key->only_date = $date2;
			$query1 = $db->getQuery(true);
			$query1 = "SELECT id FROM #__ad_wallet_transc WHERE DATE(FROM_UNIXTIME(time)) = '" . $key->only_date . "' AND type_id ="
			. $key->camp_id . " AND type = 'C'";
			$db->setQuery($query1);
			$check = $db->loadresult();

			$query2 = $db->getQuery(true);
			$query2 = "SELECT balance FROM #__ad_wallet_transc WHERE time = (SELECT MAX(time)  FROM #__ad_wallet_transc WHERE user_id="
			. $key->created_by . ") AND user_id= " . $key->created_by;
			$db->setQuery($query2);
			$bal = $db->loadresult();

			if ($check)
			{
				$query3 = $db->getQuery(true);
				$query3 = "UPDATE #__ad_wallet_transc SET time ='" . $key->c_date . "', spent = spent +"
						. $ad_charge . ",balance = " . $bal . " - " . $ad_charge . " where id=" . $check;
				$db->setQuery($query3);
				$db->execute();
			}
			else
			{
				$query4 = $db->getQuery(true);
				$query4 = "INSERT INTO #__ad_wallet_transc
						(time, user_id, spent, earn, balance, type, type_id, comment)
						VALUES ('" . $key->c_date . "'," . $key->created_by . "," . $ad_charge . ",'0'," . $bal . " - " .
						$ad_charge . ", 'C' ," . $key->camp_id . ",'DAILY_CLICK_IMP')";
				$db->setQuery($query4);
				$db->execute();
			}
		}

		return;
	}

	/**
	 * Send a Low Balance mail
	 *
	 * @param   integer  $adid  [description]
	 * @param   integer  $mode  [description]
	 *
	 * @return  void
	 *
	 * @since  3.1
	 **/
	public static function mailLowBal($adid, $mode)
	{
		global $mainframe;
		$mainframe = Factory::getApplication();
		$db = Factory::getDbo();

		if ($mode == 'pay_per_ad_mode')
		{
			$subject = Text::_('COM_SOCIALADS_SUB_LOWBAL');
			$body    = Text::_('COM_SOCIALADS_BALANCERL');
		}
		else
		{
			$subject = Text::_('COM_SOCIALADS_LOW_WALBAL_SUBJ');
			$body    = Text::_('COM_SOCIALADS_LOW_WALBAL_BODY');
		}

		$db->setQuery("SELECT a.created_by, a.ad_title, a.ad_url2, u.name, u.email
				FROM #__ad_data AS a, #__users AS u
				WHERE a.ad_id=" . $adid . " AND a.created_by=u.id");
		$result = $db->loadObject();
		$body = str_replace('[SEND_TO_NAME]', $result->name, $body);

		$ad_title = ($result->ad_title != '') ? '<strong>"' .
		$result->ad_title . '"</strong>' : '<strong>' . $adid . '</strong>';
		$subject = str_replace('[ADTITLE]', (($result->ad_title != '') ? $result->ad_title : $adid ), $subject);
		$body    = str_replace('[ADTITLE]', $ad_title, $body);

		$sitename = $mainframe->getCfg('sitename');
		$body     = str_replace('[SITENAME]', $sitename, $body);
		$body     = str_replace('[SITE]', Uri::base(), $body);
		$from = $mainframe->getCfg('mailfrom');
		$fromname = $mainframe->getCfg('fromname');
		$recipient[] = trim($result->email);
		$body = nl2br($body);
		$mode = "wallet_mode";
		$cc = null;
		$bcc = null;
		$bcc = null;
		$attachment = null;
		$replyto = null;
		$replytoname = null;

		Factory::getMailer()->sendMail($from, $fromname, $recipient, $subject, $body, $mode, $cc, $bcc, $attachment, $replyto, $replytoname);
	}

	/**
	 * Send a ad expiry mail
	 *
	 * @param   integer  $adid  [description]
	 * @param   integer  $mode  [description]
	 *
	 * @return  void
	 *
	 * @since  3.1
	 **/
	public static function mailExpir($adid, $mode)
	{
		global $mainframe;
		$mainframe = Factory::getApplication();

		if ($mode == "pay_per_ad_mode")
		{
			$itemid = SaCommonHelper::getSocialadsItemid('adform');
		}

		$db = Factory::getDbo();

		if ($mode == 'pay_per_ad_mode')
		{
			$body    = Text::_('COM_SOCIALADS_EXPIRED');
			$subject = Text::_('COM_SOCIALADS_SUB_EXPR');
		}
		else
		{
			$subject = Text::_('COM_SOCIALADS_WALEXPRI_SUBJ');
			$body    = Text::_('COM_SOCIALADS_WALEXPRI_BODY');
		}

		$query = "SELECT a.created_by, a.ad_title, a.ad_url2, u.name, u.email
				FROM #__ad_data AS a, #__users AS u
				WHERE a.ad_id=" . $adid . "
				AND a.created_by=u.id";
		$db->setQuery($query);
		$result	= $db->loadObject();

		$body = str_replace('[SEND_TO_NAME]', $result->name, $body);

		$ad_title = ($result->ad_title != '') ? '<strong>"' .
		$result->ad_title . '"</strong>' : '<strong>' . $adid . '</strong>';
		$subject = str_replace('[ADTITLE]', ($result->ad_title != '' ? $result->ad_title : $adid), $subject);
		$body    = str_replace('[ADTITLE]', $ad_title, $body);

		$sitename = $mainframe->getCfg('sitename');
		$body = str_replace('[SITENAME]', $sitename, $body);
		$body = str_replace('[SITE]', Uri::base(), $body);

		if ($mode == "pay_per_ad_mode")
		{
			$edit_ad_link  = Uri::root() . substr(
			Route::_('index.php?option=com_socialads&view=adform&ad_id=' . (int) $adid . '&Itemid=' . (int) $itemid), strlen(Uri::base(true)) + 1
			);
			$body	= str_replace('[EDITLINK]', $edit_ad_link, $body);
		}

		$from = $mainframe->getCfg('mailfrom');
		$fromname = $mainframe->getCfg('fromname');

		$recipient[] = $result->email;

		$body = nl2br($body);
		$mode = "wallet_mode";
		$cc = null;
		$bcc = null;
		$bcc = null;
		$attachment = null;
		$replyto = null;
		$replytoname = null;

		Factory::getMailer()->sendMail($from, $fromname, $recipient, $subject, $body, $mode, $cc, $bcc, $attachment, $replyto, $replytoname);
	}

	/**
	 * Check remaining amount ...if > than charge required for ad show ..return true.
	 *
	 * @param   integer  $adid      [description]
	 * @param   integer  $bidPrice  [description]
	 *
	 * @return  integer
	 *
	 * @since  3.1
	 **/
	public static function checkBalance($adid, $bidPrice)
	{
		$spent     = $remainingAmt = 0;
		$db        = Factory::getDBO();
		$date1     = date('Y-m-d');
		$status    = 0;
		$bidPrice = (float) $bidPrice;
		$query = $db->getQuery(true);

		// $query = "SELECT a.ad_creator, a.camp_id, c.daily_budget
		// INNER JOIN #__ad_campaign as c ON c.camp_id = a.camp_id
		$query = "SELECT a.created_by, a.camp_id, c.daily_budget
		 FROM #__ad_data as a
		 INNER JOIN #__ad_campaign as c ON c.id = a.camp_id
		 WHERE ad_id = " . $adid;
		$db->setQuery($query);
		$info = $db->loadobject();

		if (!empty($info))
		{
			// $ad_creator   = $info->ad_creator;
			$ad_creator  = $info->created_by;
			$camp_id     = $info->camp_id;
			$dailyBudget = $info->daily_budget;

			// FROM `#__ad_wallet_transac`
			$query1 = $db->getQuery(true);
			$query1 = "SELECT balance
			 FROM `#__ad_wallet_transc`
			 where time = (
				select MAX(time)
				from #__ad_wallet_transc
				where user_id =" . $ad_creator . "
			)";
			$db->setQuery($query1);
			$remainingAmt = $db->loadresult();

			// FROM `#__ad_wallet_transac`
			$query2 = $db->getQuery(true);
			$query2 = "SELECT spent
			 FROM `#__ad_wallet_transc`
			 where DATE(FROM_UNIXTIME(time)) ='" . $date1 . "'
			 AND type_id = " . $camp_id . "
			 AND type='C'";
			$db->setQuery($query2);
			$spent = $db->loadresult();

			if ( (($bidPrice - $remainingAmt) <= 0) && (((int)($spent + $bidPrice) - $dailyBudget) <= 0) )
			{
				$status = 1;
			}
		}

		return $status;
	}

	/**
	 * Check credits available ...if = 0 then ad is expired.
	 *
	 * @param   integer  $adid  [description]
	 *
	 * @return  integer
	 *
	 * @since  3.1
	 **/
	public static function checkCreditsAvailable($adid)
	{
		$status = 0;
		$date   = HTMLHelper::_('date', Factory::getDate(Factory::getConfig()->get('offset')), 'Y-m-d');
		$db     = Factory::getDBO();
		$query  = $db->getQuery(true);
		$query->select($db->quoteName(array('a.ad_enddate', 'a.ad_credits_balance')));
		$query->from($db->quoteName('#__ad_data', 'a'));
		$query->where($db->quoteName('a.ad_id') . " = " . $db->quote($adid));
		$query->where('(' . $db->quoteName('ad_enddate') . 'IS NULL OR (' . $db->quoteName('ad_enddate') . ' <> "0000-00-00" AND ' . 
						$db->quoteName('ad_enddate') . ' > CURDATE()) OR '. $db->quoteName('a.ad_credits_balance') . ' > 0 )');
		$db->setQuery($query);

		$result = $db->loadObject();

		if ($result)
		{
			if ($result->ad_credits_balance != 0 || $result->ad_enddate > $date)
			{
				$status = 1;
			}
		}

		return $status;
	}
}
