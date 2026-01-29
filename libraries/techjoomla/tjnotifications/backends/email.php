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
use Joomla\CMS\Log\LogEntry;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Registry\Registry;
use Joomla\CMS\Language\Text;

BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjnotifications/models', 'NotificationsModel');
BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_tjnotifications/models', 'NotificationsModel');

jimport('techjoomla.tjnotifications.backend');

/**
 * Tjnotifications class for email backend
 *
 * @package     Techjoomla.Libraries
 * @subpackage  Tjnotifications
 * @since       1.0
 */
class TjnotificationsBackendEmail extends TjnotificationsBackendBase
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

		try
		{
			$toList = self::getRecipients($client, $key, $recipients, $options, 'to');
			$ccList = self::getRecipients($client, $key, $recipients, $options, 'cc');
			$bccList = self::getRecipients($client, $key, $recipients, $options, 'bcc');

			if (empty($toList))
			{
				throw new Exception(Text::_('LIB_TECHJOOMLA_TJNOTIFICATION_ADD_RECIPIENTS_OR_CHECK_PREFERENCES'));
			}

			// To get user id, language of first user
			$userid   = self::getuserId($toList[0]);
			$language = Factory::getUser($userid)->getParam('language', Factory::getLanguage()->getTag());

			// To get user's specific language template
			$model       = ListModel::getInstance('Notifications', 'TjnotificationsModel', array('ignore_request' => true));
			$template    = $model->getTemplate($client, $key, $language, $backend = 'email');
			$emailParams = json_decode($template->params);

			// Is email enabled for this template?
			if ((int) $template->state !== 1)
			{
				// @throw new Exception(JText::_('LIB_TECHJOOMLA_TJNOTIFICATION_EMAIL_CONFIG_OFF'));
				return $return;
			}

			// Invoke JMail Class
			$mailer = Factory::getMailer();

			if (!empty($emailParams->from_email) && !empty($emailParams->from_name))
			{
				$from = array($emailParams->from_email, $emailParams->from_name);
			}
			// Backward compatibility for TJNotifications versions v1.2.5 or lower
			elseif ($options->get('from') != null && $options->get('fromname') != null)
			{
				$from = array($options->get('from'), $options->get('fromname'));
			}
			else
			{
				$config = Factory::getConfig();
				$from   = array($config->get('mailfrom'), $config->get('fromname'));
			}

			$ccForLog         = array();
			$ccListFromParams = (!empty($emailParams->cc)) ? array_map('trim', explode(',', $emailParams->cc)) : array();

			// Set cc from template params
			if (!empty($ccListFromParams[0]))
			{
				$ccList = array_merge($ccList, $ccListFromParams);
			}
			// Backward compatibility for TJNotifications versions v1.2.5 or lower
			elseif ($options->get('cc') != null)
			{
				$ccList = array_merge($ccList, $options->get('cc'));
			}

			$mailer->addCC($ccList);
			$ccForLog = $ccList;

			$bccForLog         = array();
			$bccListFromParams = (!empty($emailParams->bcc)) ? array_map('trim', explode(',', $emailParams->bcc)) : array();

			// Set bcc from template params
			if (!empty($bccListFromParams[0]))
			{
				$bccList = array_merge($bccList, $bccListFromParams);
			}
			// Backward compatibility for TJNotifications versions v1.2.5 or lower
			elseif ($options->get('bcc') != null)
			{
				$bccList = array_merge($bccList, $options->get('bcc'));
			}

			$mailer->addBCC($bccList);
			$bccForLog = $bccList;

			if ($options->get('replyTo') != null)
			{
				$mailer->addReplyTo($options->get('replyTo'));
			}

			if (!empty($options->get('attachment')))
			{
				if (!is_array($attachments = $options->get('attachment')))
				{
					$mailer->addAttachment($options->get('attachment'), $options->get('attachmentName'));
				}
				else
				{
					// For more than one attachment to the email
					foreach ($attachments as $attachment)
					{
						$mailer->addAttachment($attachment);
					}
				}
			}

			// If you would like to send String Attachment in email
			if ($options->get('stringAttachment') != null)
			{
				$stringAttachment = array();
				$stringAttachment = $options->get('stringAttachment');
				$encoding         = isset($stringAttachment['encoding']) ? $stringAttachment['encoding'] : '';
				$type             = isset($stringAttachment['type']) ? $stringAttachment['type'] : '';

				if (isset($stringAttachment['content']) && isset($stringAttachment['name']))
				{
					$mailer->addStringAttachment(
						$stringAttachment['content'],
						$stringAttachment['name'],
						$encoding,
						$type
					);
				}
			}

			// If you would like to send as HTML, include this line; otherwise, leave it out
			if (($options->get('isNotHTML')) != 1)
			{
				$mailer->isHTML();
			}

			// Set sender array so that my name will show up neatly in your inbox
			$mailer->setSender($from);

			// Add a recipient -- this can be a single address (string) or an array of addresses
			$mailer->addRecipient($toList);

			// Set subject for email
			$emailSubject = parent::getSubject($template->subject, $options);
			$mailer->setSubject($emailSubject);

			// Set body for email
			$emailBody = parent::getBody($template->body, $replacements);
			$mailer->setBody($emailBody);

			// Create logEntry object
			$entry           = new LogEntry($template->title);
			$entry->key      = $key;
			$entry->client   = $client;
			$entry->backend  = 'email';
			$entry->subject  = $emailSubject;
			$entry->body     = $emailBody;
			$entry->from     = $from[0];
			$entry->title    = $template->title;
			$entry->to       = implode(",", $toList);
			$entry->cc       = implode(",", $ccForLog);
			$entry->bcc      = implode(",", $bccForLog);

			$status = $mailer->send();

			// Return boolean  true if successful
			if ($status === true)
			{
				$return['success'] = 1;
				$return['message'] = Text::_('LIB_TECHJOOMLA_TJNOTIFICATION_EMAIL_SEND_SUCCESSFULLY');

				// Check if Logs config is enabled
				if ($this->enableLogs)
				{
					$entry->state = 1;
					$this->logger->addEntry($entry);
				}
			}
			/*// Return boolean false if configuration is set to 0
			elseif ($status === false)
			{
				throw new Exception(Text::_('LIB_TECHJOOMLA_TJNOTIFICATION_EMAIL_CONFIG_OFF'));
			}*/
			// Return a JException object if the mail function does not exist or sending the message fails.
			elseif (is_object($status))
			{
				// Throw new Exception($status->toString(), $status->get('code'));
				throw new Exception($status->get('message'), $status->get('code'));
			}

			return $return;
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
				$errorLogData['trace']   = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
				$dataLogRegistry         = new Registry($errorLogData);

				$entry         = new LogEntry($template->title);
				$entry->params = $dataLogRegistry->toString();
				$entry->state  = 0;
				$this->logger->addEntry($entry);
			}

			// @throw new Exception(Text::_('LIB_TECHJOOMLA_TJNOTIFICATION_EMAIL_SEND_FAILED') . ' ' . $e->getMessage(), $e->getCode());
			Factory::getApplication()->enqueueMessage(
				$e->getCode() . ', ' . Text::_('LIB_TECHJOOMLA_TJNOTIFICATION_EMAIL_SEND_FAILED') . ' ' . $e->getMessage() . '',
				'error'
			);

			// @return $return;
		}
	}

	/**
	 * Method to get Recipients.
	 *
	 * @param   string  $client      A requird field same as component name.
	 * @param   string  $key         Key is unique in client.
	 * @param   array   $recipients  It's an array of user objects
	 * @param   object  $options     It is a object contains Jparameters like cc, bcc
	 * @param   string  $type        Type of recipient - to / cc / bcc
	 *
	 * @return  array Reciepients.
	 *
	 * @since 1.0
	 */
	public static function getRecipients($client, $key, $recipients, $options, $type = 'to')
	{
		$recipients        = $recipients['recipients'];
		$model             = ListModel::getInstance('Preferences', 'TjnotificationsModel', array('ignore_request' => true));
		$unsubscribedUsers = $model->getUnsubscribedUsers($client, $key);

		$rList = array();

		if (!empty($recipients['email'][$type]))
		{
			foreach ($recipients['email'][$type] as $recipient)
			{
				$userId = self::getuserId($recipient);

				if (!empty($recipient))
				{
					// $userId is not in $unsubscribed_users array.
					if ($userId)
					{
						if (in_array($userId, $unsubscribedUsers))
						{
							continue;
						}
					}

					$rList[] = $recipient;
				}
			}
		}

		// Only in case of 'to'
		if ($type == 'to' && $options->get('guestEmails') != null)
		{
			foreach ($options->get('guestEmails') as $guestEmail)
			{
				$rList[] = $guestEmail;
			}
		}

		return $rList;
	}

	/**
	 * Function to find the user id based on the emails in the mail object
	 *
	 * @param   string  $email  string of email addresses
	 *
	 * @return  integer  Integer or null
	 */
	protected static function getuserId($email)
	{
		if (!empty($email))
		{
			$db    = Factory::getDbo();
			$query = $db->getQuery(true);
			$query->select('id');
			$query->from($db->quoteName('#__users'));
			$query->where($db->quoteName('email') . " = '" . $email . "'");

			$db->setQuery($query);
			$result = $db->loadResult();

			if ($result)
			{
				return $result;
			}
			else
			{
				return null;
			}
		}
		else
		{
			return null;
		}
	}
}
