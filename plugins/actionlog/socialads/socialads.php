<?php
/**
 * @package     SocialAds
 * @subpackage  Plg_Actionlog_SocialAds
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (c) 2009-2018 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later.
 */

// No direct access.
defined('_JEXEC') or die();
use Joomla\CMS\Factory;

use Joomla\CMS\Table\Table;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\Component\Actionlogs\Administrator\Model\ActionlogModel;

JLoader::register('ActionlogsHelper', JPATH_ADMINISTRATOR . '/components/com_actionlogs/helpers/actionlogs.php');
JTable::addIncludePath(JPATH_ROOT . '/administrator/components/com_socialads/tables');

/**
 * SocialAds Actions Logging Plugin.
 *
 * @since  1.5.0
 */
class PlgActionlogSocialAds extends CMSPlugin
{
	/**
	 * Application object.
	 *
	 * @var    JApplicationCms
	 * @since  1.5.0
	 */
	protected $app;

	/**
	 * Database object.
	 *
	 * @var    JDatabaseDriver
	 * @since  1.5.0
	 */
	protected $db;

	/**
	 * Load plugin language file automatically so that it can be used inside component
	 *
	 * @var    boolean
	 * @since  1.5.0
	 */
	protected $autoloadLanguage = true;

	/**
	 * On saving ad data logging method
	 *
	 * Method is called after ad data is stored in the database.
	 *
	 * @param   Object   $adData  com_socialads.
	 * @param   boolean  $isNew   True if a new ad is stored.
	 *
	 * @return  void
	 *
	 * @since   3.1.13
	 */
	public function onAfterSocialAdSave($adData, $isNew)
	{
		if (!$this->params->get('logActionForCreateAd', 1))
		{
			return;
		}

		$context = Factory::getApplication()->input->get('option');
		$user    = Factory::getUser();

		if ($isNew)
		{
			$messageLanguageKey = 'PLG_ACTIONLOG_SOCIALADS_CREATE_AD';
			$action             = 'add';
			$message = array(
				'action'           => $action,
				'type'             => 'PLG_ACTIONLOG_SOCIALADS_TYPE_AD',
				'adName'           => $adData->ad_title,
				'adLink'           => 'index.php?option=com_socialads&view=form&layout=edit&ad_id=' . $adData->ad_id,
				'userid'           => $user->id,
				'actorName'        => $user->username,
				'actorAccountLink' => 'index.php?option=com_users&task=user.edit&id=' . $user->id,
			);

			$this->addLog(array($message), $messageLanguageKey, $context, $user->id);
		}

		if (isset($adData->ad_alternative) && $adData->ad_alternative == 1)
		{
			$messageLanguageKey = 'PLG_ACTIONLOG_SOCIALADS_EDIT_AD';
			$action             = 'update';
			$message = array(
				'action'           => $action,
				'type'             => 'PLG_ACTIONLOG_SOCIALADS_TYPE_AD',
				'adName'           => $adData->ad_title,
				'adLink'           => 'index.php?option=com_socialads&view=form&layout=edit&ad_id=' . $adData->ad_id,
				'userid'           => $user->id,
				'actorName'        => $user->username,
				'actorAccountLink' => 'index.php?option=com_users&task=user.edit&id=' . $user->id,
			);

			$this->addLog(array($message), $messageLanguageKey, $context, $user->id);
		}

		if ((isset($adData->ad_noexpiry) && $adData->ad_noexpiry == 1) || (isset($adData->ad_payment_type) && $adData->ad_payment_type == 1))
		{
			$messageLanguageKey = 'PLG_ACTIONLOG_SOCIALADS_EDIT_AD';
			$action             = 'update';
			$socialadsTableAd   = Table::getInstance('ad', 'SocialadsTable', array());
			$socialadsTableAd->load(array('ad_id' => $adData->ad_id));

			$message = array(
				'action'           => $action,
				'type'             => 'PLG_ACTIONLOG_SOCIALADS_TYPE_AD',
				'adName'           => $socialadsTableAd->ad_title,
				'adLink'           => 'index.php?option=com_socialads&view=form&layout=edit&ad_id=' . $socialadsTableAd->ad_id,
				'userid'           => $user->id,
				'actorName'        => $user->username,
				'actorAccountLink' => 'index.php?option=com_users&task=user.edit&id=' . $user->id,
			);

			$this->addLog(array($message), $messageLanguageKey, $context, $user->id);
		}
	}

	/**
	 * On after changing state of Ads logging method
	 *
	 * Method is called after Ads state is changed from  the database.
	 *
	 * @param   String   $context  com_socialads.
	 * @param   Array    $pks      Holds the Ads ids.
	 * @param   Integer  $value    Holds the state value(publish/ unpublish).
	 *
	 * @return  void
	 *
	 * @since   3.1.13
	 */
	public function onAfterSocialAdChangeState($context, $pks, $value)
	{
		if (!$this->params->get('logActionForChangeStateAd', 1))
		{
			return;
		}

		$context            = Factory::getApplication()->input->get('option');
		$user               = Factory::getUser();
		$messageLanguageKey = 'PLG_ACTIONLOG_SOCIALADS_CHANGE_STATE_AD';
		$action             = 'update';

		if (!empty($pks))
		{
			foreach ($pks as $pk)
			{
				$socialadsTableAd   = Table::getInstance('ad', 'SocialadsTable', array());
				$socialadsTableAd->load(array('ad_id' => $pk));
				$message = array(
					'action'           => $action,
					'type'             => 'PLG_ACTIONLOG_SOCIALADS_TYPE_AD',
					'adName'           => $socialadsTableAd->ad_title,
					'editAdLink'       => 'index.php?option=com_socialads&view=form&layout=edit&ad_id=' . $socialadsTableAd->ad_id,
					'state'            => ($value == 1) ?'PLG_ACTIONLOG_SOCIALADS_STATE_PUBLISH': 'PLG_ACTIONLOG_SOCIALADS_STATE_UNPUBLISH',
					'actorName'        => $user->username,
					'actorAccountLink' => 'index.php?option=com_users&task=user.edit&id=' . $user->id,
				);

				$this->addLog(array($message), $messageLanguageKey, $context, $user->id);
			}
		}
	}

	/**
	 * On after deleting ad data logging method
	 *
	 * Method is called after ad data is deleted from  the database.
	 *
	 * @param   string  $context  com_socialads.
	 * @param   Object  $table    Holds the ad data.
	 *
	 * @return  void
	 *
	 * @since   3.1.13
	 */
	public function onAfterSocialAdDelete($context, $table)
	{
		if (!$this->params->get('logActionForDeleteAd', 1))
		{
			return;
		}

		$context            = Factory::getApplication()->input->get('option');
		$jUser              = Factory::getUser();
		$messageLanguageKey = 'PLG_ACTIONLOG_SOCIALADS_DELETE_AD';
		$action             = 'delete';
		$userId             = $jUser->id;
		$userName           = $jUser->username;

		$message = array(
				'action'           => $action,
				'type'             => 'PLG_ACTIONLOG_SOCIALADS_TYPE_AD',
				'adtitle'          => $table->ad_title,
				'actorId'          => $userId,
				'actorName'        => $userName,
				'actorAccountLink' => 'index.php?option=com_users&task=user.edit&id=' . $userId,
			);

		$this->addLog(array($message), $messageLanguageKey, $context, $userId);
	}

	/**
	 * On after ignoring the ad data logging method
	 *
	 * Method is called after ad data is ignored from  the database.
	 *
	 * @param   Integer  $adId    Holds the ad Id.
	 * @param   Integer  $userId  Holds the user Id.
	 *
	 * @return  void
	 *
	 * @since   3.1.13
	 */
	public function onAfterSocialAdIgnore($adId, $userId)
	{
		if (!$this->params->get('logActionForUserIgnoreAd', 1))
		{
			return;
		}

		$context            = Factory::getApplication()->input->get('option');
		$jUser              = Factory::getUser($userId);
		$userId             = $jUser->id;
		$userName           = $jUser->username;
		$socialadsTableAd   = Table::getInstance('ad', 'SocialadsTable', array());
		$socialadsTableAd->load(array('ad_id' => $adId));

		$messageLanguageKey = 'PLG_ACTIONLOG_SOCIALADS_IGNORE_AD';
		$action             = 'add';
		$message = array(
				'action'           => $action,
				'type'             => 'PLG_ACTIONLOG_SOCIALADS_TYPE_AD',
				'adName'           => $socialadsTableAd->ad_title,
				'editAdLink'       => 'index.php?option=com_socialads&view=form&layout=edit&ad_id=' . $socialadsTableAd->ad_id,
				'actorId'          => $userId,
				'actorName'        => $userName,
				'actorAccountLink' => 'index.php?option=com_users&task=user.edit&id=' . $userId,
			);

		$this->addLog(array($message), $messageLanguageKey, $context, $userId);
	}

	/**
	 * On saving coupon data logging method
	 *
	 * Method is called after coupon data is stored in the database.
	 *
	 * @param   string   $context  com_socialads.
	 * @param   Object   $table    Holds the coupon data.
	 * @param   boolean  $isNew    True if a new coupon is stored.
	 *
	 * @return  void
	 *
	 * @since   3.1.13
	 */
	public function onAfterSocialAdCouponSave($context, $table, $isNew)
	{
		if (!$this->params->get('logActionForCreateCoupon', 1))
		{
			return;
		}

		$context = Factory::getApplication()->input->get('option');
		$user    = Factory::getUser();

		if ($isNew)
		{
			$messageLanguageKey = 'PLG_ACTIONLOG_SOCIALADS_CREATE_COUPON';
			$action             = 'add';
		}
		else
		{
			$messageLanguageKey = 'PLG_ACTIONLOG_SOCIALADS_EDIT_COUPON';
			$action             = 'update';
		}

		$message = array(
			'action'           => $action,
			'type'             => 'PLG_ACTIONLOG_SOCIALADS_TYPE_COUPON',
			'couponName'       => $table->name,
			'editCouponLink'   => 'index.php?option=com_socialads&view=coupon&layout=edit&id=' . $table->id,
			'userid'           => $user->id,
			'actorName'        => $user->username,
			'actorAccountLink' => 'index.php?option=com_users&task=user.edit&id=' . $user->id,
		);

		$this->addLog(array($message), $messageLanguageKey, $context, $user->id);
	}

	/**
	 * On after changing state of coupon logging method
	 *
	 * Method is called after coupon state is changed from  the database.
	 *
	 * @param   string  $context  com_socialads.
	 * @param   Object  $pks      Holds the coupon ids.
	 * @param   Object  $value    Holds the state value(publish/ unpublish).
	 *
	 * @return  void
	 *
	 * @since   3.1.13
	 */
	public function onAfterSocialAdCouponChangeState($context, $pks, $value)
	{
		if (!$this->params->get('logActionForChangeStateCoupon', 1))
		{
			return;
		}

		$context              = Factory::getApplication()->input->get('option');
		$user                 = Factory::getUser();
		$messageLanguageKey   = 'PLG_ACTIONLOG_SOCIALADS_CHANGE_STATE_COUPON';
		$socialadsTablecoupon = Table::getInstance('coupon', 'SocialadsTable', array());

		foreach ($pks as $couponId)
		{
			$socialadsTablecoupon->load(array('id' => $couponId));
			$message = array(
				'action'           => $action,
				'type'             => 'PLG_ACTIONLOG_SOCIALADS_TYPE_COUPON',
				'couponName'       => $socialadsTablecoupon->name,
				'editCouponLink'   => 'index.php?option=com_socialads&view=coupon&layout=edit&id=' . $socialadsTablecoupon->id,
				'state'            => $value ?'PLG_ACTIONLOG_SOCIALADS_STATE_PUBLISH': 'PLG_ACTIONLOG_SOCIALADS_STATE_UNPUBLISH',
				'actorName'        => $user->username,
				'actorAccountLink' => 'index.php?option=com_users&task=user.edit&id=' . $user->id,
			);

			$this->addLog(array($message), $messageLanguageKey, $context, $user->id);
		}
	}

	/**
	 * On after deleting coupon data logging method
	 *
	 * Method is called after coupon data is deleted from  the database.
	 *
	 * @param   string  $context  com_socialads.
	 * @param   Object  $table    Holds the coupon data.
	 *
	 * @return  void
	 *
	 * @since   3.1.13
	 */
	public function onAfterSocialAdCouponDelete($context, $table)
	{
		if (!$this->params->get('logActionForDeleteCoupon', 1))
		{
			return;
		}

		$context            = Factory::getApplication()->input->get('option');
		$jUser              = Factory::getUser();
		$messageLanguageKey = 'PLG_ACTIONLOG_SOCIALADS_DELETE_COUPON';
		$action             = 'delete';
		$userId             = $jUser->id;
		$userName           = $jUser->username;

		$message = array(
				'action'      => $action,
				'type'        => 'PLG_ACTIONLOG_SOCIALADS_TYPE_COUPON',
				'id'          => $table->id,
				'title'       => $table->name,
				'actorId'      => $userId,
				'actorName'    => $userName,
				'actorAccountLink' => 'index.php?option=com_users&task=user.edit&id=' . $userId,
			);

		$this->addLog(array($message), $messageLanguageKey, $context, $userId);
	}

	/**
	 * On saving campaign data logging method
	 *
	 * Method is called after campaign data is stored in the database.
	 *
	 * @param   Object   $campaignData  Holds the campaign data.
	 * @param   boolean  $isNew         True if a new campaign is stored.
	 *
	 * @return  void
	 *
	 * @since   3.1.13
	 */
	public function onAfterSocialAdCampaignSave($campaignData, $isNew)
	{
		if (!$this->params->get('logActionForCreateCampaign', 1))
		{
			return;
		}

		$app               = Factory::getApplication();
		$context           = $app->input->get('option');
		$user              = Factory::getUser();
		$campaignOwnerData = Factory::getUser($campaignData->created_by);

		if ($app->isClient("administrator"))
		{
			if ($isNew)
			{
				if ($campaignData->created_by == $user->id)
				{
					$messageLanguageKey = 'PLG_ACTIONLOG_SOCIALADS_CREATE_CAMPAIGN';
				}
				else
				{
					$messageLanguageKey = 'PLG_ACTIONLOG_SOCIALADS_ADMIN_CREATE_CAMPAIGN';
				}

				$action             = 'add';
			}
			else
			{
				if ($campaignData->created_by == $user->id)
				{
					$messageLanguageKey = 'PLG_ACTIONLOG_SOCIALADS_EDIT_CAMPAIGN';
				}
				else
				{
					$messageLanguageKey = 'PLG_ACTIONLOG_SOCIALADS_ADMIN_EDIT_CAMPAIGN';
				}

				$action             = 'update';
			}
		}
		else
		{
			if ($isNew)
			{
				$messageLanguageKey = 'PLG_ACTIONLOG_SOCIALADS_CREATE_CAMPAIGN';
				$action             = 'add';
			}
			else
			{
				$messageLanguageKey = 'PLG_ACTIONLOG_SOCIALADS_EDIT_CAMPAIGN';
				$action             = 'update';
			}
		}

		$message = array(
			'action'            => $action,
			'type'              => 'PLG_ACTIONLOG_SOCIALADS_TYPE_CAMPAIGN',
			'campaignName'      => $campaignData->campaign,
			'campaignOwnerId'   => $campaignOwnerData->id,
			'campaignOwnerName' => $campaignOwnerData->username,
			'campaignOwnerLink' => 'index.php?option=com_users&view=user&layout=edit&id=' . $campaignOwnerData->id,
			'editCampaignLink'  => 'index.php?option=com_socialads&view=campaign&layout=edit&id=' . $campaignData->id,
			'userid'            => $user->id,
			'actorName'         => $user->username,
			'actorAccountLink'  => 'index.php?option=com_users&task=user.edit&id=' . $user->id,
		);

		$this->addLog(array($message), $messageLanguageKey, $context, $user->id);
	}

	/**
	 * On after changing state of campaign logging method
	 *
	 * Method is called after campaign state is changed from  the database.
	 *
	 * @param   string   $context  com_socialads.
	 * @param   Array    $pks      Holds the campaign ids.
	 * @param   Integer  $value    Holds the state value(publish/ unpublish).
	 *
	 * @return  void
	 *
	 * @since   3.1.13
	 */
	public function onAfterSocialAdCampaignChangeState($context, $pks, $value)
	{
		if (!$this->params->get('logActionForChangeStateCampaign', 1))
		{
			return;
		}

		$context            = Factory::getApplication()->input->get('option');
		$user               = Factory::getUser();
		$messageLanguageKey = 'PLG_ACTIONLOG_SOCIALADS_CHANGE_STATE_CAMPAIGN';
		$action             = 'update';

		if (!empty($pks))
		{
			foreach ($pks as $pk)
			{
				$table = Table::getInstance('campaign', 'SocialadsTable', array());
				$table->load(array('id' => $pk));
				$message = array(
					'action'           => $action,
					'type'             => 'PLG_ACTIONLOG_SOCIALADS_TYPE_CAMPAIGN',
					'campaignName'     => $table->campaign,
					'editCampaignLink' => 'index.php?option=com_socialads&view=campaign&layout=edit&id=' . $table->id,
					'state'            => ($value == 1)?'PLG_ACTIONLOG_SOCIALADS_STATE_PUBLISH': 'PLG_ACTIONLOG_SOCIALADS_STATE_UNPUBLISH',
					'actorName'        => $user->username,
					'actorAccountLink' => 'index.php?option=com_users&task=user.edit&id=' . $user->id,
				);

				$this->addLog(array($message), $messageLanguageKey, $context, $user->id);
			}
		}
	}

	/**
	 * On after deleting coupon data logging method
	 *
	 * Method is called after coupon data is deleted from  the database.
	 *
	 * @param   string  $context  com_socialads.
	 * @param   Object  $table    Holds the coupon data.
	 *
	 * @return  void
	 *
	 * @since   3.1.13
	 */
	public function onAfterSocialAdCampaignDelete($context, $table)
	{
		if (!$this->params->get('logActionForDeleteCampaign', 1))
		{
			return;
		}

		$context            = Factory::getApplication()->input->get('option');
		$jUser              = Factory::getUser();
		$messageLanguageKey = 'PLG_ACTIONLOG_SOCIALADS_DELETE_CAMPAIGN';
		$action             = 'delete';
		$userId             = $jUser->id;
		$userName           = $jUser->username;

		$message = array(
				'action'           => $action,
				'type'             => 'PLG_ACTIONLOG_SOCIALADS_TYPE_CAMPAIGN',
				'id'               => $table->id,
				'title'            => $table->campaign,
				'actorId'          => $userId,
				'actorName'        => $userName,
				'actorAccountLink' => 'index.php?option=com_users&task=user.edit&id=' . $userId,
			);

		$this->addLog(array($message), $messageLanguageKey, $context, $userId);
	}

	/**
	 * On saving campaign data logging method
	 *
	 * Method is called after campaign data is stored in the database.
	 *
	 * @param   Integer  $zoneId  Zone Id
	 * @param   Boolean  $isNew   Holds the true false
	 *
	 * @return  void
	 *
	 * @since   3.1.13
	 */
	public function onAfterSocialAdZoneSave($zoneId, $isNew)
	{
		if (!$this->params->get('logActionForCreateZone', 1))
		{
			return;
		}

		$context  = Factory::getApplication()->input->get('option');
		$jUser    = Factory::getUser();
		$userId   = $jUser->id;
		$userName = $jUser->username;

		if ($isNew)
		{
			$messageLanguageKey = 'PLG_ACTIONLOG_SOCIALADS_CREATE_ZONE';
			$action             = 'add';
		}
		else
		{
			$messageLanguageKey = 'PLG_ACTIONLOG_SOCIALADS_EDIT_ZONE';
			$action             = 'update';
		}

		$socialadsTablezone = Table::getInstance('zone', 'SocialadsTable', array());
		$socialadsTablezone->load(array('id' => $zoneId));

		$message = array(
				'action'           => $action,
				'type'             => 'PLG_ACTIONLOG_SOCIALADS_TYPE_ZONE',
				'id'               => $socialadsTablezone->id,
				'zoneName'         => $socialadsTablezone->zone_name,
				'editZoneLink'     => 'index.php?option=com_socialads&view=zone&layout=edit&id=' . $socialadsTablezone->id,
				'actorId'          => $userId,
				'actorName'        => $userName,
				'actorAccountLink' => 'index.php?option=com_users&task=user.edit&id=' . $userId,
			);

		$this->addLog(array($message), $messageLanguageKey, $context, $userId);
	}

	/**
	 * On after changing state of zone logging method
	 *
	 * Method is called after zone state is changed from  the database.
	 *
	 * @param   string  $context  com_socialads.
	 * @param   Object  $pks      Holds the zone ids.
	 * @param   Object  $value    Holds the state value(publish/ unpublish).
	 *
	 * @return  void
	 *
	 * @since   3.1.13
	 */
	public function onAfterSocialAdZoneChangeState($context, $pks, $value)
	{
		if (!$this->params->get('logActionForChangeStateZone', 1))
		{
			return;
		}

		$context            = Factory::getApplication()->input->get('option');
		$user               = Factory::getUser();
		$messageLanguageKey = 'PLG_ACTIONLOG_SOCIALADS_CHANGE_STATE_ZONE';
		$socialadsTablezone = Table::getInstance('zone', 'SocialadsTable', array());

		foreach ($pks as $zoneId)
		{
			$socialadsTablezone->load(array('id' => $zoneId));
			$message = array(
				'action'           => $action,
				'type'             => 'PLG_ACTIONLOG_SOCIALADS_TYPE_ZONE',
				'zoneName'         => $socialadsTablezone->zone_name,
				'editZoneLink'     => 'index.php?option=com_socialads&view=zone&layout=edit&id=' . $socialadsTablezone->id,
				'state'            => $value ?'PLG_ACTIONLOG_SOCIALADS_STATE_PUBLISH': 'PLG_ACTIONLOG_SOCIALADS_STATE_UNPUBLISH',
				'actorName'        => $user->username,
				'actorAccountLink' => 'index.php?option=com_users&task=user.edit&id=' . $user->id,
			);

			$this->addLog(array($message), $messageLanguageKey, $context, $user->id);
		}
	}

	/**
	 * On after deleting zone data logging method
	 *
	 * Method is called after zone data is deleted from  the database.
	 *
	 * @param   string  $context  com_socialads.
	 * @param   Object  $table    Holds the zone data.
	 *
	 * @return  void
	 *
	 * @since   3.1.13
	 */
	public function onAfterSocialAdZoneDelete($context, $table)
	{
		if (!$this->params->get('logActionForDeleteZone', 1))
		{
			return;
		}

		$context            = Factory::getApplication()->input->get('option');
		$jUser              = Factory::getUser();
		$messageLanguageKey = 'PLG_ACTIONLOG_SOCIALADS_DELETE_ZONE';
		$action             = 'delete';
		$userId             = $jUser->id;
		$userName           = $jUser->username;

		$message = array(
				'action'           => $action,
				'type'             => 'PLG_ACTIONLOG_SOCIALADS_TYPE_ZONE',
				'zoneTitle'        => $table->zone_name,
				'actorId'          => $userId,
				'actorName'        => $userName,
				'actorAccountLink' => 'index.php?option=com_users&task=user.edit&id=' . $userId,
			);

		$this->addLog(array($message), $messageLanguageKey, $context, $userId);
	}

	/**
	 * On after order status changed data logging method
	 *
	 * Method is called after order status changed data is save in the database.
	 *
	 * @param   Array    $post     Order data
	 * @param   Integer  $orderId  Order Id
	 *
	 * @return  void
	 *
	 * @since   3.1.13
	 */
	public function onAfterSocialAdOrderStatusChange($post, $orderId)
	{
		if (!$this->params->get('logActionForOrder', 1))
		{
			return;
		}

		$context            = Factory::getApplication()->input->get('option');
		$jUser              = Factory::getUser();

		$userId             = $jUser->id;
		$userName           = $jUser->username;

		$params = ComponentHelper::getParams('com_socialads');

		$db = Factory::getDbo();

		if ($params->get('payment_mode') == 'wallet_mode')
		{
			$query = $db->getQuery(true);
			$query-> select('o.*');
			$query->from($db->quoteName('#__ad_orders', 'o'));
			$query->where($db->quoteName('o.id') . '=' . (int) $orderId);
			$db->setQuery($query);
			$details = $db->loadObject();

			if ($details->status == 'C')
			{
				$messageLanguageKey = 'PLG_ACTIONLOG_SOCIALADS_ADD_MONEY_IN_WALLET';
				$action             = 'add';
				$message = array(
					'action'           => $action,
					'type'             => 'PLG_ACTIONLOG_SOCIALADS_TYPE_ORDER_WALLET',
					'actorId'          => $details->payee_id,
					'actorName'        => Factory::getUser($details->payee_id)->username,
					'actorAccountLink' => 'index.php?option=com_users&task=user.edit&id=' . $details->payee_id,
				);
			}
			else
			{
				$messageLanguageKey = 'PLG_ACTIONLOG_SOCIALADS_ORDER_STATUS_CHANGED_OF_WALLET_ORDERS';
				$action             = 'update';
				$message = array(
					'action'           => $action,
					'type'             => 'PLG_ACTIONLOG_SOCIALADS_TYPE_ORDER',
					'orderId'          => $details->prefix_oid ? $details->prefix_oid : $details->id,
					'actorId'          => $userId,
					'actorName'        => $userName,
					'actorAccountLink' => 'index.php?option=com_users&task=user.edit&id=' . $userId,
				);
			}

			$this->addLog(array($message), $messageLanguageKey, $context, $userId);
		}
		elseif ($params->get('payment_mode') == 'pay_per_ad_mode')
		{
			$query = $db->getQuery(true);
			$query-> select('o.*');
			$query-> select($db->quoteName(array('p.ad_id')));
			$query->from($db->quoteName('#__ad_orders', 'o'));
			$query->join('LEFT', $db->quoteName('#__ad_payment_info', 'p') .
			' ON (' . $db->quoteName('o.id') . ' = ' . $db->quoteName('p.order_id') . ')');
			$query->where($db->quoteName('o.id') . '=' . (int) $orderId);
			$db->setQuery($query);
			$details = $db->loadObject();

			$socialadsTableAd   = Table::getInstance('ad', 'SocialadsTable', array());
			$socialadsTableAd->load(array('ad_id' => $details->ad_id));

			if ($details->payee_id == $userId)
			{
				$jUser              = Factory::getUser($details->payee_id);
				$userId             = $jUser->id;
				$userName           = $jUser->username;
				$messageLanguageKey = 'PLG_ACTIONLOG_SOCIALADS_SELF_ORDER_STATUS_CHANGED';
				$action             = 'update';
			}
			else
			{
				$messageLanguageKey = 'PLG_ACTIONLOG_SOCIALADS_ORDER_STATUS_CHANGED';
				$action             = 'add';
			}

			$message = array(
				'action'           => $action,
				'type'             => 'PLG_ACTIONLOG_SOCIALADS_TYPE_ORDER',
				'adName'           => $socialadsTableAd->ad_title,
				'adLink'           => 'index.php?option=com_socialads&view=form&layout=edit&ad_id=' . $details->ad_id,
				'orderPrefix'      => $details->prefix_oid,
				'actorId'          => $userId,
				'actorName'        => $userName,
				'actorAccountLink' => 'index.php?option=com_users&task=user.edit&id=' . $userId,
			);

			$this->addLog(array($message), $messageLanguageKey, $context, $userId);
		}
	}

	/**
	 * Proxy for ActionlogsModelUserlog addLog method
	 *
	 * This method adds a record to #__action_logs contains (message_language_key, message, date, context, user)
	 *
	 * @param   array   $messages            The contents of the messages to be logged
	 * @param   string  $messageLanguageKey  The language key of the message
	 * @param   string  $context             The context of the content passed to the plugin
	 * @param   int     $userId              ID of user perform the action, usually ID of current logged in user
	 *
	 * @return  void
	 *
	 * @since   1.5.0
	 */
	protected function addLog($messages, $messageLanguageKey, $context, $userId = null)
	{
		if (JVERSION >= '4.4.0')
		{
			$model = Factory::getApplication()->bootComponent('com_actionlogs')
            ->getMVCFactory()->createModel('Actionlog', 'Administrator', ['ignore_request' => true]);
		}
		else if (JVERSION >= '4.0')
		{
			$model = new ActionlogModel;
		}
		else
		{
			JLoader::register('ActionlogsModelActionlog', JPATH_ADMINISTRATOR . '/components/com_actionlogs/models/actionlog.php');
			$model = BaseDatabaseModel::getInstance('Actionlog', 'ActionlogsModel');
		}

		$model->addLog($messages, $messageLanguageKey, $context, $userId);
	}
}
