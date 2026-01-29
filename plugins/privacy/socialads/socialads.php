<?php
/**
 * @package     SocilAds
 * @subpackage  Plg_Privacy_SocilAds
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2025 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die();
use Joomla\CMS\User\User;
use Joomla\CMS\Table\User as UserTable;
use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

JLoader::register('PrivacyPlugin', JPATH_ADMINISTRATOR . '/components/com_privacy/helpers/plugin.php');
JLoader::register('PrivacyRemovalStatus', JPATH_ADMINISTRATOR . '/components/com_privacy/helpers/removal/status.php');

/**
 * SocialAds Privacy Plugin.
 *
 * @since  3.1.13
 */
class PlgPrivacySocialAds extends PrivacyPlugin
{
	/**
	 * Load the language file on instantiation.
	 *
	 * @var    boolean
	 *
	 * @since  3.1.13
	 */
	protected $autoloadLanguage = true;

	/**
	 * Database object
	 *
	 * @var    JDatabaseDriver
	 * @since  3.1.13
	 */
	protected $db;

	/**
	 * Reports the privacy related capabilities for this plugin to site administrators.
	 *
	 * @return  array
	 *
	 * @since   3.1.13
	 */
	public function onPrivacyCollectAdminCapabilities()
	{
		$this->loadLanguage();

		return array(
			Text::_('PLG_PRIVACY_SOCIALADS') => array(
				Text::_('PLG_PRIVACY_SOCIALADS_PRIVACY_CAPABILITY_ADVERTISER_DETAIL'),
				Text::_('PLG_PRIVACY_SOCIALADS_PRIVACY_CAPABILITY_TARGET_USERS_DETAIL')
			)
		);
	}

	/**
	 * Processes an export request for SocilAds user data
	 *
	 * This event will collect data for the following tables:
	 *
	 * - #__ad_data
	 * - #__ad_orders
	 * - #__ad_ignore
	 * - #__ad_stats
	 * - #__ad_users
	 * - #__ad_wallet_transc
	 *
	 * @param   PrivacyTableRequest  $request  The request record being processed
	 * @param   JUser                $user     The user account associated with this request if available
	 *
	 * @return  PrivacyExportDomain[]
	 *
	 * @since   3.1.13
	 */
	public function onPrivacyExportRequest(PrivacyTableRequest $request, User $user = null)
	{
		if (!$user)
		{
			return array();
		}

		/** @var JTableUser $user */
		$userTable = User::getTable();
		$userTable->load($user->id);

		$domains = array();
		$domains[] = $this->createSocialAdsData($userTable);
		$domains[] = $this->createSocialAdsOrders($userTable);
		$domains[] = $this->createSocialAdsCampaigns($userTable);
		$domains[] = $this->createSocialAdsUsers($userTable);
		$domains[] = $this->createSocialAdsStatsData($userTable);
		$domains[] = $this->createSocialAdsIgnoredData($userTable);
		$domains[] = $this->createSocialAdsWalletTransactionData($userTable);

		return $domains;
	}

	/**
	 * Create the domain for the SocilAds user's ad/s
	 *
	 * @param   JTableUser  $user  The JTableUser object to process
	 *
	 * @return  PrivacyExportDomain
	 *
	 * @since   3.1.13
	 */
	private function createSocialAdsData(UserTable $user)
	{
		$domain = $this->createDomain("User Ads", "User's ad in SocialAds");

		$query = $this->db->getQuery(true)
			->select($this->db->quoteName(array('ad_id', 'ad_title', 'ad_url2', 'ad_startdate', 'ad_enddate')))
			->from($this->db->quoteName('#__ad_data'))
			->where($this->db->quoteName('created_by') . '=' . $user->id);

		$ads = $this->db->setQuery($query)->loadAssocList();

		if (!empty($ads))
		{
			foreach ($ads as $ad)
			{
				$domain->addItem($this->createItemFromArray($ad, $ad['ad_id']));
			}
		}

		return $domain;
	}

	/**
	 * Create the domain for the SocilAds user's ads ignore data
	 *
	 * @param   JTableUser  $user  The JTableUser object to process
	 *
	 * @return  PrivacyExportDomain
	 *
	 * @since   3.1.13
	 */
	private function createSocialAdsIgnoredData(UserTable $user)
	{
		$domain = $this->createDomain('User ad ignored', 'Ads ignored by user in SocialAds');

		$query = $this->db->getQuery(true)
			->select($this->db->quoteName(array('id', 'adid', 'userid', 'ad_feedback', 'idate')))
			->from($this->db->quoteName('#__ad_ignore'))
			->where($this->db->quoteName('userid') . '=' . $user->id);

		$ignoredData = $this->db->setQuery($query)->loadAssocList();

		if (!empty($ignoredData))
		{
			foreach ($ignoredData as $data)
			{
				$domain->addItem($this->createItemFromArray($data, $data['id']));
			}
		}

		return $domain;
	}

	/**
	 * Create the domain for the SocilAds user's wallet transactions
	 *
	 * @param   JTableUser  $user  The JTableUser object to process
	 *
	 * @return  PrivacyExportDomain
	 *
	 * @since   3.1.13
	 */
	private function createSocialAdsWalletTransactionData(UserTable $user)
	{
		$domain = $this->createDomain("Ad wallet transactions", "User's ad wallet transactions in SocialAds");

		$query = $this->db->getQuery(true)
			->select($this->db->quoteName(array('id', 'time', 'user_id', 'spent', 'earn', 'balance', 'type', 'comment')))
			->from($this->db->quoteName('#__ad_wallet_transc'))
			->where($this->db->quoteName('user_id') . '=' . $user->id);

		$walletTransactions = $this->db->setQuery($query)->loadAssocList();

		if (!empty($walletTransactions))
		{
			foreach ($walletTransactions as $walletTransaction)
			{
				$domain->addItem($this->createItemFromArray($walletTransaction, $walletTransaction['id']));
			}
		}

		return $domain;
	}

	/**
	 * Create the domain for users orders
	 *
	 * @param   JTableUser  $user  The JTableUser object to process
	 *
	 * @return  PrivacyExportDomain
	 *
	 * @since   3.1.13
	 */
	private function createSocialAdsOrders(UserTable $user)
	{
		$domain = $this->createDomain("User's Orders", "Orders placed by an user");

		$query = $this->db->getQuery(true);
		$query->select($this->db->qn(array('id', 'prefix_oid', 'cdate', 'mdate','payee_id', 'transaction_id', 'amount', 'comment', 'tax')));

		$query->from($this->db->quoteName('#__ad_orders'));
		$query->where($this->db->quoteName('payee_id') . '=' . $user->id);

		$orders = $this->db->setQuery($query)->loadAssocList();

		if (!empty($orders))
		{
			foreach ($orders as $order)
			{
				$domain->addItem($this->createItemFromArray($order, $order['id']));
			}
		}

		return $domain;
	}

	/**
	 * Create the domain for the campaigns owned by user
	 *
	 * @param   JTableUser  $user  The JTableUser object to process
	 *
	 * @return  PrivacyExportDomain
	 *
	 * @since   3.1.13
	 */
	private function createSocialAdsCampaigns(UserTable $user)
	{
		$domain = $this->createDomain("User's Campaigns", "Details of campaign owned by a user");

		$query = $this->db->getQuery(true);
		$query->select($this->db->qn(array('id', 'state', 'created_by', 'campaign', 'daily_budget')));
		$query->from($this->db->quoteName('#__ad_campaign'));
		$query->where($this->db->quoteName('created_by') . '=' . $user->id);

		$campaigns = $this->db->setQuery($query)->loadAssocList();

		if (!empty($campaigns))
		{
			foreach ($campaigns as $campaign)
			{
				$domain->addItem($this->createItemFromArray($campaign, $campaign['id']));
			}
		}

		return $domain;
	}

	/**
	 * Create the domain for the SocilAds users details related to an order
	 *
	 * @param   JTableUser  $user  The JTableUser object to process
	 *
	 * @return  PrivacyExportDomain
	 *
	 * @since   3.1.13
	 */
	private function createSocialAdsUsers(UserTable $user)
	{
		$domain = $this->createDomain("User's order details", "User information for ad payment");

		$query = $this->db->getQuery(true);
		$query->select($this->db->qn(array('id', 'user_id', 'orderid', 'user_email', 'firstname', 'lastname', 'vat_number', 'tax_exempt')));
		$query->select($this->db->qn(array('country_code', 'address', 'state_code', 'city', 'zipcode', 'phone', 'approved')));

		$query->from($this->db->quoteName('#__ad_users'));
		$query->where($this->db->quoteName('user_id') . '=' . $user->id);

		$advertiserDetails = $this->db->setQuery($query)->loadAssocList();

		if (!empty($advertiserDetails))
		{
			foreach ($advertiserDetails as $advertiserDetail)
			{
				$domain->addItem($this->createItemFromArray($advertiserDetail, $advertiserDetail['id']));
			}
		}

		return $domain;
	}

	/**
	 * Create the domain for the users stats data
	 *
	 * @param   JTableUser  $user  The JTableUser object to process
	 *
	 * @return  PrivacyExportDomain
	 *
	 * @since   3.1.13
	 */
	private function createSocialAdsStatsData(UserTable $user)
	{
		$domain = $this->createDomain('Ad clicks and impressions data', 'Ad clicks or impressions by the user');

		$query = $this->db->getQuery(true);
		$query->select($this->db->qn(array('id', 'user_id', 'ad_id', 'time', 'ip_address', 'spent', 'referer')));
		$query->from($this->db->quoteName('#__ad_stats'));
		$query->where($this->db->quoteName('user_id') . '=' . $user->id);

		$statsData = $this->db->setQuery($query)->loadAssocList();

		if (!empty($statsData))
		{
			foreach ($statsData as $stat)
			{
				$domain->addItem($this->createItemFromArray($stat, $stat['id']));
			}
		}

		return $domain;
	}

	/**
	 * Removes the data associated with a remove information request
	 *
	 * This event will pseudoanonymise the user account
	 *
	 * @param   PrivacyTableRequest  $request  The request record being processed
	 * @param   JUser                $user     The user account associated with this request if available
	 *
	 * @return  void
	 *
	 * @since   3.1.13
	 */
	public function onPrivacyRemoveData(PrivacyTableRequest $request, User $user = null)
	{
		// This plugin only processes data for registered user accounts
		if (!$user)
		{
			return;
		}

		// If there was an error loading the user do nothing here
		if ($user->guest)
		{
			return;
		}

		$db = Factory::getDbo();
		$query = $db->getQuery(true);

		$fields = array($db->quoteName('ip_address') . ' = ' . '"127.0.0.1"');
		$conditions = array($db->quoteName('user_id') . ' = ' . $db->quote($user->id));

		$query->update($db->quoteName('#__ad_stats'))->set($fields)->where($conditions);
		$db->setQuery($query);
		$db->execute();

		$com_params = ComponentHelper::getParams('com_socialads');

		if ($com_params->get('payment_mode') == 'wallet_mode')
		{
			// Remove campaign against the user
			$query = $db->getQuery(true);
			$conditions = array($db->quoteName('created_by') . '=' . (int) $user->id);
			$query->delete($db->quoteName('#__ad_campaign'));
			$query->where($conditions);
			$db->setQuery($query);
			$db->execute();
		}
	}

	/**
	 * Performs validation to determine if the data associated with a remove information request can be processed
	 *
	 * This event will not allow a super user account to be removed
	 *
	 * @param   PrivacyTableRequest  $request  The request record being processed
	 * @param   JUser                $user     The user account associated with this request if available
	 *
	 * @return  PrivacyRemovalStatus
	 *
	 * @since   3.1.13
	 */
	public function onPrivacyCanRemoveData(PrivacyTableRequest $request, User $user = null)
	{
		$status = new PrivacyRemovalStatus;

		if (!$user->id)
		{
			return $status;
		}

		// Check if user is store owner
		$db = Factory::getDbo();
		$query = $db->getQuery(true);
		$query->select($db->quoteName('ad_id'));
		$query->from($db->quoteName('#__ad_data'));
		$query->where($db->quoteName('created_by') . '=' . (int) $user->id);
		$db->setQuery($query);
		$ads = $db->loadColumn();

		if (!empty($ads))
		{
			$status->canRemove = false;
			$ads = 'ID: ' . implode(', ', $ads);
			$status->reason    = Text::sprintf('PLG_PRIVACY_SOCIALADS_ERROR_WITH_ADS', $ads);

			return $status;
		}

		$com_params = ComponentHelper::getParams('com_socialads');

		if ($com_params->get('payment_mode') == 'wallet_mode')
		{
			require_once JPATH_SITE . '/components/com_socialads/helpers/wallet.php';
			$init_balance = SaWalletHelper::getBalance($user->id);

			if ($init_balance == 1.00)
			{
				$status->canRemove = false;
				$status->reason    = Text::_('PLG_PRIVACY_SOCIALADS_ERROR_WITH_ORDERS');

				return $status;
			}
		}

		return;
	}
}
