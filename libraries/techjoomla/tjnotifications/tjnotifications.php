<?php
/**
 * @package     Techjoomla.Libraries
 * @subpackage  Tjnotifications
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2020 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

defined('JPATH_PLATFORM') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\MVC\Model\ListModel;

// Load language file, defines.php, subscriptions and preferences model, backend base class
Factory::getLanguage()->load('lib_techjoomla', JPATH_SITE, null, false, true);
require_once JPATH_ADMINISTRATOR . '/components/com_tjnotifications/defines.php';
BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjnotifications/models', 'SubscriptionsModel');
BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_tjnotifications/models', 'PreferencesModel');
jimport('techjoomla.tjnotifications.backend');

/**
 * Tjnotifications
 *
 * @package     Techjoomla.Libraries
 * @subpackage  Tjnotifications
 * @since       1.0
 */
class Tjnotifications
{
	/**
	 * The constructor
	 *
	 * @since  1.0
	 */
	public function __construct()
	{
		// Check for component
		if (!ComponentHelper::getComponent('com_tjnotifications', true)->enabled)
		{
			throw new Exception('TJNotifications not installed');
		}
	}

	/**
	 * Method to send the form data.
	 *
	 * @param   string  $client        A requird field same as component name.
	 * @param   string  $key           Key is unique in client.
	 * @param   array   $recipients    It's an array of user objects
	 * @param   object  $replacements  It is a object contains replacement.
	 * @param   object  $options       It is a object contains Jparameters like cc,bcc.
	 *
	 * @return  array|boolean
	 *
	 * @since 1.0
	 */
	public static function send($client, $key, $recipients, $replacements, $options)
	{
		$return = array();

		// Get final recipients list by checking into subsciptions table
		$recipients = self::getRecipientsAndSubscribersData($client, $key, $recipients);

		try
		{
			// Recipients are grouped by backend
			$backendsList = (is_array($recipients['recipients']) && count($recipients['recipients'])) ? array_keys($recipients['recipients']) : '';

			foreach ($backendsList as $backend)
			{
				jimport('techjoomla.tjnotifications.backends.' . strtolower($backend));

				$backendClassName = 'TjnotificationsBackend' . ucfirst($backend);
				$backendClassObj  = new $backendClassName;
				$backendClassObj->send($client, $key, $recipients, $replacements, $options);
			}

			$return['success'] = 1;
			$return['message'] = Text::_('LIB_TECHJOOMLA_TJNOTIFICATION_EMAIL_SEND_SUCCESSFULLY');
		}
		catch (Exception $e)
		{
			$return['success'] = 0;
			$return['code']    = $e->getCode();
			$return['message'] = $e->getMessage();
		}

		return $return;
	}

	/**
	 * Get recipients, subscribers, language data grouped by backend
	 * Also, validate unsubscribed data
	 *
	 *  // Sample input
	 * 	$recipients = array (
	 * 		JFactory::getUser(488),
	 * 		JFactory::getUser(500),
	 * 		"email" => array (
	 * 			"to" => array ('email1@domain1.com', 'email2@domain2.com'),
	 * 			"cc" => array ('cc.email1@domain1.com', 'cc.email2@domain2.com'),
	 * 			"bcc" => array ('bcc.email1@domain1.com', 'bcc.email2@domain2.com')
	 * 		),
	 * 		// Optional push recipients array
	 * 		"push" => array ("notification_token1", "notification_token2"),
	 * 		// Optional sms recipients array
	 * 		"sms" => array ("+919876543210", "+919876543211"),
	 * 		// Optional whatsapp recipients array
	 * 			"whatsapp" => array ("+919876543210", "+919876543211", "+919876543212")
	 * 	);
	 *
	 * // Sample output
	 * $recipients = array (
	 *	"recipients" => array (
	 * 		"email" => array (
	 * 			"to" => array (
	 * 				'email_488@domain.com', // got from subscribers table (if entry exists there) using user id 488
	 * 				'email_500@domain.com', // got from subscribers table (if entry exists there) using user id 500
	 * 				'email1@domain1.com',
	 * 				'email2@domain2.com'
	 * 			),
	 * 			"cc" => array ('cc.email1@domain1.com', 'cc.email2@domain2.com'),
	 * 			"bcc" => array ('bcc.email1@domain1.com', 'bcc.email2@domain2.com')
	 * 		),
	 * 		// Optional push recipients array
	 * 		"push" => array (
	 * 			"notification_token_488", // got from subscribers table (if entry exists there) using user id 488
	 * 			"notification_token_500", // got from subscribers table (if entry exists there) using user id 500
	 * 			"notification_token1", "notification_token2"
	 * 		),
	 * 		// Optional sms recipients array
	 * 		"sms" => array (
	 * 			"mobile_no_488", // got from subscribers table (if entry exists there) using user id 488
	 * 			"mobile_no_500", // got from subscribers table (if entry exists there) using user id 500
	 * 			"+919876543210", "+919876543211"
	 * 		),
	 * 		// Optional whatsapp recipients array
	 * 		"whatsapp" => array (
	 * 			"mobile_no_488", // got from subscribers table (if entry exists there) using user id 488
	 * 			"mobile_no_500", // got from subscribers table (if entry exists there) using user id 500
	 * 			"+919876543210", "+919876543211"
	 * 		)
	 * 	),
	 * 	// Also return language to be used for addrees
	 *  "langage" => array(
	 * 		"push" => array (
	 * 			"notification_token_488" => hi-IN
	 * 		),
	 *     	"sms" => array(
	 * 			"mobile_no_488" => hi-IN
	 * 		)
	 *    	"whatsapp" => array(
	 * 			"mobile_no_488" => hi-IN
	 * 		)
	 * 	)
	 * );
	 *
	 * @param   string  $client      A requird field same as component name.
	 * @param   string  $key         Key is unique in client.
	 * @param   array   $recipients  It's an array of user objects
	 *
	 * @return  array
	 *
	 * @since  1.3.0
	 */
	protected static function getRecipientsAndSubscribersData($client, $key, $recipients)
	{
		$finalRecipients               = array ();
		$finalRecipients['recipients'] = array ();
		$finalRecipients['language']   = array ();

		if (empty($recipients))
		{
			return $recipients;
		}

		// Lets group recipients by backend
		$backendsArray = explode(',', TJNOTIFICATIONS_CONST_BACKENDS_ARRAY);

		foreach ($backendsArray as $keyBackend => $backend)
		{
			if (empty($recipients[$backend]))
			{
				continue;
			}

			$finalRecipients['recipients'][$backend] = $recipients[$backend];
		}

		// Hardcoded for webhook
		// If configured, then webhooks will always be triggered
		if (!empty($finalRecipients['recipients']))
		{
			$finalRecipients['recipients']['webhook'] = $finalRecipients['recipients'][array_key_first($finalRecipients['recipients'])];
		}

		// To get subscribers data, we need to filter out JUser objects from list
		$jUsers         = array();
		$recipientsKeys = array_keys($recipients);

		foreach ($recipientsKeys as $rKey)
		{
			// Typecasting to string is important here
			// If current index(key) is not in list of backends, it means - it is a juser object
			$backendsArray = explode(',', TJNOTIFICATIONS_CONST_BACKENDS_ARRAY);

			if (!in_array((string) $rKey, $backendsArray) && !in_array((string) $rKey, array('email', 'sms', 'push', 'whatsapp', 'webhook')))
			{
				$jUsers[] = $recipients[$rKey];
			}
		}

		if (empty($jUsers))
		{
			return $finalRecipients;
		}

		// Hardcoded for webhook
		// If configured, then webhooks will always be triggered
		if (empty($finalRecipients['recipients']['webhook']))
		{
			$firstElement = current(array_filter($jUsers));
			$finalRecipients['recipients']['webhook'] = array ();
			$finalRecipients['language']['webhook'] = $firstElement->getParam('language', Factory::getLanguage()->getTag());
		}

		// For each such user, get subsciptions data
		foreach ($jUsers as $user)
		{
			if (empty($user) || is_array($user))
			{
				continue;
			}

			// Get user's frontend language
			$language = $user->getParam('language', Factory::getLanguage()->getTag());

			// GLOBAL - Get TJNotifications subscriptions details for current user
			$model             = ListModel::getInstance('Subscriptions', 'TjnotificationsModel', array('ignore_request' => true));
			$userSubscriptions = $model->getUserSubscriptions($user->id);

			// SPECIFIC - Get TJNotifications unsubscribed details for current user
			$model            = ListModel::getInstance('Preferences', 'TJNotificationsModel');
			$unsubscribedList = $model->getUnsubscribedListByUser($user->id, $client, $key);

			foreach ($userSubscriptions as $sub)
			{
				// User has unsubscribed to this notification for this backend, skip this one
				if (!empty($unsubscribedList[$sub->backend]) && ($user->id == $unsubscribedList[$sub->backend]->user_id))
				{
					continue;
				}

				// Push at start
				if (!empty($finalRecipients[$sub->backend]))
				{
					array_unshift($finalRecipients['recipients'][$sub->backend], $sub->address);
				}
				// Insert first element in blank array
				else
				{
					$finalRecipients['recipients'][$sub->backend][] = $sub->address;
				}

				$finalRecipients['language'][$sub->backend][$sub->address] = $language;
			}
		}

		return $finalRecipients;
	}
}
