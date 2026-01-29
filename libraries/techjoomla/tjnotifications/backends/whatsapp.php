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
class TjnotificationsBackendWhatsapp extends TjnotificationsBackendBase
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
		$toList         = $recipients['recipients']['whatsapp'];
		$firstRecipient = $recipients['recipients']['whatsapp'][0];
		$language       = $recipients['language']['whatsapp'][$firstRecipient];

		// To get user's specific language template
		$model    = ListModel::getInstance('Notifications', 'TjnotificationsModel', array('ignore_request' => true));
		$template = $model->getTemplate($client, $key, $language, $backend = 'whatsapp');

		try
		{
			if (isset($toList))
			{
				// Send once you have set all of your options
				if ($template->state == 1)
				{
					// Get config
					$whatsappPlugin               = $this->tjNotificationsParams->get('tjwhatsapp_plugin');
					$message                      = parent::getBody($template->body, $replacements);
					/*$urlShorteningEnabledBackends = $this->tjNotificationsParams->get('url_shortening_enabled_backends');
					$isShortening                 = $this->tjNotificationsParams->get('enable_url_shortening');

					if (!empty($isShortening) && in_array('whatsapp', $urlShorteningEnabledBackends))
					{
						$message = parent::shortenUrls($message);
					}*/

					foreach ($toList as $address)
					{
						PluginHelper::importPlugin('tjwhatsapp', $whatsappPlugin);

						$status = Factory::getApplication()->triggerEvent('sendMessage', array($address, $message));

						// Create logEntry object
						$entry           = new LogEntry($template->title);
						$entry->key      = $key;
						$entry->client   = $client;
						$entry->backend = 'whatsapp';
						$entry->subject  = '';
						$entry->body     = $message;
						$entry->from     = '';
						$entry->title    = $template->title;
						$entry->to       = $address;
						$entry->cc       = '';
						$entry->bcc      = '';

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
						$return['message'] = Text::_('LIB_TECHJOOMLA_TJNOTIFICATION_WHATSAPP_SEND_SUCCESSFULLY');

						return $return;
					}
					else
					{
						throw new Exception(Text::_('LIB_TECHJOOMLA_TJNOTIFICATION_WHATSAPP_SEND_FAILED'));
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
			$return['message'] = $e->getMessage();

			// Check if Logs config is enabled
			if ($this->enableLogs)
			{
				$errorLogData            = array();
				$errorLogData['code']    = $e->getCode();
				$errorLogData['message'] = $e->getMessage();
				$dataLogRegistry         = new Registry($errorLogData);

				$entry->params           = $dataLogRegistry->toString();
				$entry->state            = 0;
				$this->logger->addEntry($entry);
			}

			// @throw new Exception(Text::_('LIB_TECHJOOMLA_TJNOTIFICATION_WHATSAPP_SEND_FAILED') . ' ' . $e->getMessage(), $e->getCode());
			Factory::getApplication()->enqueueMessage(
				$e->getCode() . ', ' . Text::_('LIB_TECHJOOMLA_TJNOTIFICATION_WHATSAPP_SEND_FAILED') . ' ' . $e->getMessage() . '',
				'error'
			);

			// @return $return;
		}
	}
}
