<?php
/**
 * @version    SVN:<SVN_ID>
 * @package    SocialAds
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2016 TechJoomla. All rights reserved
 * @license    GNU General Public License version 2, or later
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Component\ComponentHelper;

/**
 * Create ad class
 *
 * @since  1.6
 */
class CreateAdHelper
{
	/**
	 * Checking if table "ad_fields" exists or not in buildad and manage ad view
	 *
	 * @return  boolean
	 *
	 * @since  1.0
	 **/
	public function chkadfields()
	{
		$db = Factory::getDBO();
		global $mainframe;
		$mainframe = Factory::getApplication();
		$dbname    = $mainframe->getCfg('db');
		$dbprefix  = $mainframe->getCfg('dbprefix');
		$tablename = $dbprefix . 'ad_fields';

		$query	= $db->getQuery(true);
		$query->select('COUNT(*)');
		$query->from($db->quoteName('information_schema.tables'));
		$query->where($db->quoteName('table_schema') . ' = ' . $db->q($dbname));
		$query->where($db->quoteName('table_name') . ' = ' . $db->q($tablename));

		$db->setQuery($query);
		$adfields = $db->loadresult();

		if (!$adfields)
		{
			return '';
		}
		else
		{
			return 1;
		}
	}

	/**
	 * Checking if table "ad_fields" exists or not in buildad and manage ad view
	 *
	 * @param   integer  $userid  User ID
	 *
	 * @return  boolean
	 *
	 * @since  1.0
	 **/
	public function getUserCampaign($userid)
	{
		$db    = Factory::getDBO();
		$query	= $db->getQuery(true);
		$query->select(array('camp_id', 'campaign', 'daily_budget'));
		$query->from($db->quoteName('#__ad_campaign'));
		$query->where($db->quoteName('user_id') . ' = ' . $userid);

		$db->setQuery($query);
		$camp_value = $db->loadobjectList();

		return $camp_value;
	}

	/**
	 * Function to get latest pending order
	 *
	 * @param   integer  $ad_id   Ad ID
	 * @param   integer  $userid  User ID
	 *
	 * @return  boolean
	 *
	 * @since  1.0
	 **/
	public function getLatestPendigOrder($ad_id, $userid)
	{
		$db    = Factory::getDBO();
		$query	= $db->getQuery(true);
		$query->select($db->quoteName('p.id'));
		$query->from($db->quoteName('#__ad_payment_info', 'p'));
		$query->where($db->quoteName('p.ad_id') . ' = ' . $ad_id);
		$query->where($db->quoteName('p.payee_id') . ' = ' . $userid);
		$query->where($db->quoteName('p.status') . ' = ' . $db->q('P'));
		$query->order($db->quoteName('id') . ' DESC');

		$db->setQuery($query);

		return $db->loadResult();
	}

	/**
	 * Function to send ad approval
	 *
	 * @param   object  $designAd_data  provide ad data information
	 *
	 * @return  array
	 *
	 * @since  1.0
	 **/
	public function sendForApproval($designAd_data)
	{
		$return['sa_sentApproveMail'] = '';

		if (empty($designAd_data))
		{
			return $return;
		}

		$db    = Factory::getDBO();
		$query = $db->getQuery(true);
		$query->select($db->quoteName('o.id'));
		$query->from($db->quoteName('#__ad_orders', 'o'));
		$query->join('LEFT', $db->quoteName('#__ad_payment_info', 'p') . ' ON ' . $db->quoteName('p.order_id') . '=' . $db->quoteName('o.id'));
		$query->where($db->quoteName('p.ad_id') . ' = ' . $designAd_data->ad_id);
		$query->where($db->quoteName('o.status') . ' = ' . $db->q('C'));
		$query->order($db->quoteName('o.id') . ' DESC');

		$db->setQuery($query);
		$ConfirmOrders = $db->loadResult();

		if (empty($ConfirmOrders))
		{
			// No order is confirm then allow to edit ad
			return $return;
		}

		// Get old ad details
		$query = $db->getQuery(true);
		$query->select($db->quoteName(array('a.ad_id', 'a.ad_image', 'a.ad_title', 'a.ad_body', 'a.ad_url2')));
		$query->from($db->quoteName('#__ad_data', 'a'));
		$query->where($db->quoteName('a.ad_id') . ' = ' . $designAd_data->ad_id);
		$query->where($db->quoteName('a.ad_approved') . ' = ' . 1);

		$db->setQuery($query);
		$oldAd = $db->loadObject();

		// ANY ONE IS CHANGED
		if (!empty($oldAd)
			&& ($oldAd->ad_image != $designAd_data->ad_image
			|| $oldAd->ad_title != $designAd_data->ad_title
			|| $oldAd->ad_body != $designAd_data->ad_body
			|| $oldAd->ad_url2 != $designAd_data->ad_url2))
		{
			$createAdHelper = new createAdHelper;
			$createAdHelper->adminAdApprovalEmail($designAd_data->ad_id);
			$return['ad_approved']        = 0;
			$return['sa_sentApproveMail'] = 1;

			return $return;
		}

		return $return;
	}

	/**
	 * Function for admin approval mail
	 *
	 * @param   integer  $ad_id  Ad id
	 *
	 * @return  array
	 *
	 * @since  1.0
	 **/
	public function adminAdApprovalEmail($ad_id)
	{
		$db    = Factory::getDBO();
		$query = $db->getQuery(true);
		$query->select($db->quoteName(array('a.ad_id', 'a.ad_image', 'a.ad_title', 'a.ad_body', 'a.ad_url2')));
		$query->from($db->quoteName('#__ad_data', 'a'));
		$query->where($db->quoteName('a.ad_id') . ' = ' . $ad_id);

		// . '  AND a.ad_approved=1';
		$db->setQuery($query);
		$oldAd = $db->loadObject();

		$user = Factory::getUser();
		global $mainframe;
		$mainframe    = Factory::getApplication();
		$sitelink     = Uri::root();

		$manageAdLink = "<a href='" . $sitelink . "administrator/"
						. "index.php?option=com_socialads&view=forms' targe='_blank'>" . Text::_("COM_SOCIALADS_EMAIL_THIS_LINK") . "</a>";

		// GET config details
		$frommail = $mainframe->getCfg('mailfrom');
		$fromname = $mainframe->getCfg('fromname');
		$adUserName = $user->username;
		$adTitle    = $oldAd->ad_title;
		$siteName   = $mainframe->getCfg('sitename');
		$today      = date('Y-m-d H:i:s');

		// DEFINE SEARCH
		$find       = array(
			'[SEND_TO_NAME]',
			'[ADVERTISER_NAME]',
			'[SITENAME]',
			'[SITELINK]',
			'[ADTITLE]',
			'[TIMESTAMP]'
		);

		// SEND ADMIN MAIL
		$sa_params = ComponentHelper::getParams('com_socialads');
		$emailfrom = $sa_params->get('mail_from');

		if ($emailfrom)
		{
			$recipient = $emailfrom;
		}
		else
		{
			$recipient = $frommail;
		}

		$subject        = Text::_("COM_SOCIALADS_APPRVE_MAIL_TO_ADMIN_SUBJECT");
		$adminEmailBody = Text::sprintf("COM_SOCIALADS_EMAIL_HELLO") .
		Text::sprintf('COM_SOCIALADS_APPRVE_MAIL_TO_ADMIN_CONTENT', $manageAdLink) .
		Text::sprintf("COM_SOCIALADS_EMAIL_SITENAME_TEAM");

		// NOW REPLACE TAG
		// @TODO - Notice: Undefined variable: content in helpers/createad.php
		$replace        = array(
			$fromname,
			$adUserName,
			$siteName,
			$sitelink,
			$adTitle,
			$today
		);
		$adminEmailBody = str_replace($find, $replace, $adminEmailBody);

		// $status  = $socialadshelper->sendmail($recipient,$subject,$adminEmailBody,$bcc_string='',$singlemail=0,$attachmentPath="");
		$status = SaCommonHelper::sendmail($recipient, $subject, $adminEmailBody, $bcc_string = '', $singlemail = 0, $attachmentPath = '');

		// SEND TO ADVERTISER MAIL
		$advertiserEmail     = $user->email;
		$subject             = Text::_("COM_SOCIALADS_APPRVE_MAIL_TO_ADVERTISER_SUBJECT");
		$advertiserEmailBody = Text::sprintf("COM_SOCIALADS_EMAIL_HELLO") .
		Text::sprintf('COM_SOCIALADS_APPRVE_MAIL_TO_ADVERTISR_CONTENT') .
		Text::sprintf("COM_SOCIALADS_EMAIL_SITENAME_TEAM");

		// NOW REPLACE TAG
		// @TODO - Notice: Undefined variable: content in helpers/createad.php
		$replace             = array(
			$adUserName,
			$adUserName,
			$siteName,
			$sitelink,
			$adTitle,
			$today
		);
		$advertiserEmailBody = str_replace($find, $replace, $advertiserEmailBody);

		// $status  = $socialadshelper->sendmail($advertiserEmail,$subject,$advertiserEmailBody,$bcc_string='',$singlemail=0,$attachmentPath="");
		$status = SaCommonHelper::sendmail($advertiserEmail, $subject, $advertiserEmailBody, $bcc_string = '', $singlemail = 0, $attachmentPath = '');
	}
}
