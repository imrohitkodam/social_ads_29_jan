<?php
/**
 * @package     Techjoomla.Libraries
 * @subpackage  Tjnotifications
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2022 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

use Joomla\CMS\Factory;
use Joomla\CMS\Http\Http;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\LogEntry;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Registry\Registry;

BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjnotifications/models', 'NotificationsModel');
BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_tjnotifications/models', 'NotificationsModel');

jimport('techjoomla.tjnotifications.backend');

/**
 * Tjnotifications
 *
 * @package     Techjoomla.Libraries
 * @subpackage  Tjnotifications
 * @since       1.0
 */
class TjnotificationsBackendWebhook extends TjnotificationsBackendBase
{
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
	public function send($client, $key, $recipients, $replacements, $options)
	{
		$return = array();

		// To get language of user
		// $toList         = $recipients['recipients']['webhook'];
		// $firstRecipient = $recipients['recipients']['webhook'][0];
		// $language       = $recipients['language']['webhook'][$firstRecipient];
		$language = (isset($recipients) 
						&& isset($recipients['language'])
						&& isset($recipients['language']['webhook']) 
						&& isset($recipients['language']['webhook'][0])
					) ? $recipients['language']['webhook'][0] : '';

		// To get user's specific language template
		$model = ListModel::getInstance('Notifications', 'TjnotificationsModel', array('ignore_request' => true));

		$template = $model->getTemplate($client, $key, $language, $backend = 'webhook');

		if (!is_object($template))
		{
			return $return;
		}

		// Is webhook enabled for this template?
		if (is_object($template) && (int) $template->state !== 1)
		{
			// @throw new Exception(JText::_('LIB_TECHJOOMLA_TJNOTIFICATION_EMAIL_CONFIG_OFF'));
			return $return;
		}

		// if (isset($toList))
		{
			$message = parent::getBody($template->body, $replacements);

			$webhookUrls = array ();

			if (!empty($template->webhook_url))
			{
				$webhookUrls = json_decode($template->webhook_url, true);
				$webhookUrls = array_unique(array_column($webhookUrls, 'url'));
			}

			// If set to use global URls
			if ($template->use_global_webhook_url)
			{
				// Global Webhook URls
				$globalWebhookUrls = $this->tjNotificationsParams->get('webhook_url');
				$globalWebhookUrls = array_unique(array_column((array) $globalWebhookUrls, 'url'));

				// Merge Global & Template Webhooks
				$webhookUrls = array_unique(array_merge($globalWebhookUrls, $webhookUrls));
			}

			// foreach ($toList as $address)
			{
				if (!empty($webhookUrls))
				{
					foreach ($webhookUrls as $wHookUrl)
					{
						$http     = new Http;
						$headers  = array('Content-Type' => 'application/json');
						$response = $http->post($wHookUrl, $message, $headers);

						// Check if Logs config is enabled
						if ($this->enableLogs)
						{
							// Create logEntry object
							$entry              = new LogEntry($template->title);
							$entry->key         = $key;
							$entry->client      = $client;
							$entry->backend     = 'webhook';
							$entry->subject     = '';
							$entry->body        = $message;
							$entry->webhook_url = $wHookUrl;
							$entry->from        = '';
							$entry->title       = $template->title;
							$entry->to          = ''; // $address;
							$entry->cc          = '';
							$entry->bcc         = '';
							$entry->state       = 1;
							$this->logger->addEntry($entry);
						}
					}
				}
			}

			$return['success'] = 1;
		}
		// else
		// {
		// 	throw new Exception(Text::_('LIB_TECHJOOMLA_TJNOTIFICATION_ADD_RECIPIENTS_OR_CHECK_PREFERENCES'));
		// }

		return $return;
	}
}
