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

use Joomla\Registry\Registry;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Log\Logger\DatabaseLogger;

/**
 * Tjnotifications Backend Base
 *
 * @package     Techjoomla.Libraries
 * @subpackage  Tjnotifications
 * @since       1.3.0
 */
class TjnotificationsBackendBase
{
	public $enableLogs;

	public $logger;

	public $tjNotificationsParams;

	/**
	 * The constructor
	 *
	 * @since  1.0
	 */
	public function __construct()
	{
		// Get component Configs
		$this->tjNotificationsParams  = ComponentHelper::getParams('com_tjnotifications');
		$this->enableLogs             = $this->tjNotificationsParams->get('enable_logs', 0);

		if ($this->enableLogs)
		{
			// Create DB Logger Object
			$config = array('db_table' => '#__tj_notification_logs');
			$this->logger = new DatabaseLogger($config);
		}
	}

	/**
	 * Method to get Tags.
	 *
	 * @param   string  $dataTemplate  A template.
	 *
	 * @return  array   $matches
	 *
	 * @since 1.0.0
	 */
	public function getTags($dataTemplate)
	{
		//  Pattern for {text};
		$pattern = "/{{([^}]*)}}/";

		preg_match_all($pattern, $dataTemplate, $matches);

		//  $matches[0]= stores tag like {doner.name} and $matches[1] stores doner.name. Explode it and make it doner->name
		return $matches;
	}

	/**
	 * Method to get Subject.
	 *
	 * @param   string  $subjectTemplate  A template body for email.
	 * @param   object  $options          It is a object contains replacement.
	 *
	 * @return  string  $subject
	 *
	 * @since 1.0.0
	 */
	public function getSubject($subjectTemplate, $options)
	{
		$matches = $this->getTags($subjectTemplate);
		$tags    = $matches[0];
		$index   = 0;

		foreach ($tags as $tag)
		{
			// Explode e.g donor.name with "." so $data[0]=donor and $data[1]=name
			$data  = explode(".", $matches[1][$index]);
			$key   = $data[0];
			$value = $data[1];

			$replaceWith     = $options->get($key)->$value;
			$subjectTemplate = str_replace($tag, $replaceWith, $subjectTemplate);

			$index++;
		}

		return $subjectTemplate;
	}

	/**
	 * Method to get Body.
	 *
	 * @param   string  $bodyTemplate  A template body for email.
	 * @param   object  $replacements  It is a object contains replacement.
	 *
	 * @return  string  $body
	 *
	 * @since 1.0.0
	 */
	public function getBody($bodyTemplate, $replacements)
	{
		$matches = $this->getTags($bodyTemplate);

		$replacementTags = $matches[0];
		$tags            = $matches[1];
		$index           = 0;

		if (isset($replacements))
		{
			foreach ($replacementTags as $ind => $replacementTag)
			{
				// Explode e.g donor.name with "." so $data[0]=donor and $data[1]=name
				$data = explode(".", $tags[$ind]);

				if (isset($data))
				{
					$key   = $data[0];
					$value = $data[1];

					if (!empty($replacements->$key->$value) || $replacements->$key->$value == 0)
					{
						$replaceWith = $replacements->$key->$value;
					}
					else
					{
						$replaceWith = "";
					}

					if (isset($replaceWith))
					{
						$bodyTemplate = str_replace($replacementTag, $replaceWith, $bodyTemplate);
						$index++;
					}
				}
			}
		}

		return $bodyTemplate;
	}

	/**
	 * Method to get short url in SMS notification.
	 *
	 * @param   string  $string  A template body for email.
	 *
	 * @return  string  $body
	 *
	 * @since 1.0.0
	 */
	public function shortenUrls($string)
	{
		if (empty($string))
		{
			return;
		}

		$enableShorteningUrl = $this->tjNotificationsParams->get('enable_url_shortening');
		$shortnerProvider    = $this->tjNotificationsParams->get('url_shortening_provider');

		if (!empty($enableShorteningUrl) && !empty($shortnerProvider))
		{
			PluginHelper::importPlugin('tjurlshortner', $shortnerProvider);

			$matches = $this->getTags($string);

			// Replacement of url in title
			$regex = "/((https?\:\/\/|ftps?\:\/\/)|(www\.))(\S+)(\w{1,5})(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?/i";
			preg_match_all($regex, $string, $matches);

			if (!empty($matches[0]))
			{
				foreach ($matches[0] as $match)
				{
					$shorturl = Factory::getApplication()->triggerEvent('getShortUrl', array($match));

					if ($shorturl[0]['url'])
					{
						$string   = str_replace($match, $shorturl[0]['url'], $string);
					}
					else
					{
						continue;
					}
				}
			}
		}

		return $string;
	}
}
