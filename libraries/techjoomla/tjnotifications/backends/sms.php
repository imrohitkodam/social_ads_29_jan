<?php
/**
 * @package     Techjoomla.Libraries
 * @subpackage  Tjnotifications
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

defined('JPATH_PLATFORM') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\LogEntry;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Registry\Registry;
use Joomla\CMS\Plugin\PluginHelper;

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
class TjnotificationsBackendSms extends TjnotificationsBackendBase
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
		$toList         = $recipients['recipients']['sms'];
		$firstRecipient = $recipients['recipients']['sms'][0];
		$language       = !empty($recipients['language']['sms'][$firstRecipient])
			? $recipients['language']['sms'][$firstRecipient] : Factory::getLanguage()->getTag();

		// To get user's specific language template
		$model    = ListModel::getInstance('Notifications', 'TjnotificationsModel', array('ignore_request' => true));
		$template = $model->getTemplate($client, $key, $language, $backend = 'sms');

		try
		{
			if (isset($toList))
			{
				// Send once you have set all of your options
				if (!empty($template->state) && $template->state == 1)
				{
					// Get config
					$smsPlugin                    = $this->tjNotificationsParams->get('tjsms_plugin');
					$message                      = parent::getBody($template->body, $replacements);
					$urlShorteningEnabledBackends = $this->tjNotificationsParams->get('url_shortening_enabled_backends');
					$isShortening                 = $this->tjNotificationsParams->get('enable_url_shortening');

					if (!empty($isShortening) && in_array('sms', $urlShorteningEnabledBackends))
					{
						$message = parent::shortenUrls($message);
					}

					foreach ($toList as $address)
					{
						// Create logEntry object
						$entry           = new LogEntry($template->title);
						$entry->key      = $key;
						$entry->client   = $client;
						$entry->backend = 'sms';
						$entry->subject  = '';
						$entry->body     = $message;
						$entry->from     = '';
						$entry->title    = $template->title;
						$entry->to       = $address;
						$entry->cc       = '';
						$entry->bcc      = '';

						PluginHelper::importPlugin('tjsms', $smsPlugin);

						$status = Factory::getApplication()->triggerEvent('send_SMS', array($address, $message, $template->provider_template_id));

						// Check if Logs config is enabled
						if ($this->enableLogs)
						{
							$entry->state = 1;
							$this->logger->addEntry($entry);
						}
					}

					if ($status)
					{
						$return['success'] = 1;
						$return['message'] = Text::_('LIB_TECHJOOMLA_TJNOTIFICATION_SMS_SEND_SUCCESSFULLY');

						return $return;
					}
					else
					{
						throw new Exception(Text::_('LIB_TECHJOOMLA_TJNOTIFICATION_SMS_SEND_FAILED'));
					}
				}
			}
			else
			{
				throw new Exception(Text::_('LIB_TECHJOOMLA_TJNOTIFICATION_ADD_RECIPIENTS_OR_CHECK_PREFERENCES'));
			}
		}
		catch (Exception $e)
		{
			$return['success'] = 0;
			$return['code']    = $e->getCode();
			$return['message'] = $e->getMessage();

			// Check if Logs config is enabled
			if ($this->enableLogs)
			{
				$errorLogData            = array();
				$errorLogData['code']    = $e->getCode();
				$errorLogData['message'] = $e->getMessage();
				$errorLogData['trace']   = $e->getTrace()[0];
				$dataLogRegistry         = new Registry($errorLogData);

				$entry         = new LogEntry($template->title);
				$entry->params = $dataLogRegistry->toString();
				$entry->state  = 0;
				$this->logger->addEntry($entry);
			}

			// @throw new Exception(Text::_('LIB_TECHJOOMLA_TJNOTIFICATION_SMS_SEND_FAILED') . ' ' . $e->getMessage(), $e->getCode());
			Factory::getApplication()->enqueueMessage(
				$e->getCode() . ', ' . Text::_('LIB_TECHJOOMLA_TJNOTIFICATION_SMS_SEND_FAILED') . ' ' . $e->getMessage() . '',
				'error'
			);

			// @return $return;
		}
	}
}
