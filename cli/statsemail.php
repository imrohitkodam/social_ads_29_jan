<?php
/**
 * @version    SVN:<SVN_ID>
 * @package    SocialAds
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2019 TechJoomla. All rights reserved
 * @license    GNU General Public License version 2, or later
 */

// Make sure this is being called from the command line
if (PHP_SAPI !== 'cli')
{
	die('This is a command line only application.');
}

const _JEXEC = 1;

if (file_exists(dirname(__DIR__) . '/defines.php'))
{
	require_once dirname(__DIR__) . '/defines.php';
}

if (!defined('_JDEFINES'))
{
	define('JPATH_BASE', dirname(__DIR__));
	require_once JPATH_BASE . '/includes/defines.php';
	require_once JPATH_BASE . '/includes/framework.php';
}

require_once JPATH_LIBRARIES . '/import.legacy.php';
require_once JPATH_LIBRARIES . '/cms.php';
require_once JPATH_CONFIGURATION . '/configuration.php';

ini_set('display_errors', 'On');

use Joomla\CMS\Log\Log;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Application\CliApplication;

$lang = Factory::getLanguage();
$lang->load('com_socialads', JPATH_SITE, 'en-GB', true);

/**
 * A command line cron job to archive stats.
 *
 * @since  3.2.0
 */
class StatsEmail extends CliApplication
{
	/**
	 * Entry point for CLI script
	 *
	 * @return  void
	 *
	 * @since   3.2.0
	 */
	public function execute()
	{
		$category = "stats_email";

		Log::addLogger(array('text_file' => "email_stats.php"), Log::ALL, array($category));

		$this->out(Text::_("COM_SOCIALADS_CLI_EMAIL_STATS_START"));
		Log::add(Text::_("COM_SOCIALADS_CLI_EMAIL_STATS_START"), Log::INFO, $category);

		$params = ComponentHelper::getParams('com_socialads');

		if (!$params->get('weekly_stats'))
		{
			$this->out(Text::_("COM_SOCIALADS_CLI_EMAIL_STATS_DISABLED"));
			Log::add(Text::_("COM_SOCIALADS_CLI_EMAIL_STATS_DISABLED"), Log::ERROR, $category);

			return;
		}

		if (!$params->get('site_base_url'))
		{
			$this->out(Text::_("COM_SOCIALADS_CLI_EMAIL_STATS_SITE_BASE_URL_EMPTY"));
			Log::add(Text::_("COM_SOCIALADS_CLI_EMAIL_STATS_SITE_BASE_URL_EMPTY"), Log::ERROR, $category);

			return;
		}

		require_once JPATH_SITE . "/components/com_socialads/helpers/common.php";
		$adCreators = SaCommonHelper::getAdCreators();

		if (!count($adCreators))
		{
			$this->out(Text::_("COM_SOCIALADS_CLI_EMAIL_STATS_NO_AD_CREATORS"));
			Log::add(Text::_("COM_SOCIALADS_CLI_EMAIL_STATS_NO_AD_CREATORS"), Log::INFO, $category);

			return;
		}

		$failedEmailCount = $successEmailCount = $totalEmails = 0;

		foreach ($adCreators as $userId)
		{
			$statsForPie = SaCommonHelper::statsForPieInMail($userId);

			if (!count($statsForPie))
			{
				$this->out(Text::_("COM_SOCIALADS_CLI_EMAIL_STATS_NO_PIE_STATS"));
				Log::add(Text::_("COM_SOCIALADS_CLI_EMAIL_STATS_NO_PIE_STATS"), Log::INFO, $category);

				continue;
			}

			$user = SaCommonHelper::getUserDetails($userId, 'username, name, email');
			$body = $params->get('intro_text_mail');

			$find = array (
				'{username}',
				'{name}',
				'[SEND_TO_USERNAME]',
				'[SEND_TO_NAME]'
			);

			$replace = array(
				$user->username,
				$user->name,
				$user->username,
				$user->name
			);

			$body  = str_replace($find, $replace, $body);
			$email = $user->email;

			foreach ($statsForPie as $ad)
			{
				if (($ad[0][0]->value) || ($ad[1][0]->value))
				{
					$body .= $this->statsEmailBody($ad, $email);
				}
			}

			$body   = nl2br($body);
			$status = $this->sendStatsEmailToUser($body, $email);
			$totalEmails++;

			$status ? $successEmailCount++ : $failedEmailCount++;
		}

		$this->out(Text::sprintf("COM_SOCIALADS_MAIL_SENT_STATS", $totalEmails, $successEmailCount, $failedEmailCount));
		Log::add(Text::sprintf("COM_SOCIALADS_MAIL_SENT_STATS", $totalEmails, $successEmailCount, $failedEmailCount), Log::INFO, $category);

		$this->out(Text::_("COM_SOCIALADS_CLI_EMAIL_STATS_END"));
		Log::add(Text::_("COM_SOCIALADS_CLI_EMAIL_STATS_END"), Log::INFO, $category);
	}

	/**
	 * Method to create mail body
	 *
	 * @param   array   $statAd  stats for pie
	 * @param   string  $email   User email
	 *
	 * @return  string
	 *
	 * @since  3.2.0
	 */
	public function statsEmailBody($statAd, $email)
	{
		$adId     = $statAd[2];
		$clicks   = $impressions = 0;
		$ad       = SaCommonHelper::getAdInfo($adId, 'ad_title');
		$title    = (!empty($ad->ad_title)) ? '"' . $ad->ad_title . '"' : $adId;
		$ad_title = Text::_("COM_SOCIALADS_CRON_AD_TITLE") . ' <b>' . $title . '</b>';

		$db    = Factory::getDbo();
		$query = $db->getQuery(true);
		$query->select('id');
		$query->from($db->quoteName('#__menu'));
		$query->where($db->quoteName('link') . ' LIKE ' . $db->quote('%' . 'index.php?option=com_socialads&view=adform%'));
		$query->where($db->quoteName('published') . ' = ' . 1);
		$query->setLimit(1);
		$db->setQuery($query);
		$itemId = $db->loadResult();

		$params       = ComponentHelper::getParams('com_socialads');
		$siteBaseUrl  = $params->get('site_base_url');
		$edit_ad_link = $siteBaseUrl . '/index.php?option=com_socialads&view=adform&ad_id=' . (int) $adId . '&Itemid=' . $itemId;
		$url          = '';

		if (isset($statAd[1][0]->value))
		{
			$clicks = $statAd[1][0]->value;
		}

		if (isset($statAd[0][0]->value))
		{
			$impressions = $statAd[0][0]->value;
		}

		if ($clicks  || $impressions)
		{
			$cl_impr = $impressions . ',' . $clicks;
			$url = "https://chart.googleapis.com/chart?chs=300x150&cht=p3&chd=t:" . $cl_impr . "&chdl=Impressions|Clicks";
		}

		$CTR = 0.00;

		if ($clicks && $impressions)
		{
			$CTR = number_format($clicks / $impressions, 2);
		}

		$body      = Text::_('COM_SOCIALADS_PERIDIC_STATS_BODY');
		$timestamp = strtotime("-7 days");
		$find      = array(
			'[ADTITLE]',
			'[STARTDATE]',
			'[ENDDATE]',
			'[TOTAL_IMPRS]',
			'[TOTAL_CLICKS]',
			'[CLICK_TO_IMPRS]',
			'[STAT_CHART]',
			'[AD_EIDT_LINK]'
		);

		$replace = array($ad_title,strftime("%d-%m-%Y", $timestamp), date('d-m-Y'), $impressions, $clicks, $CTR, $url, $edit_ad_link);
		$body    = str_replace($find, $replace, $body);

		if (!$ad_title)
		{
			$body = str_replace('Ad Title', '', $body);
		}

		return nl2br($body);
	}

	/**
	 * Method to send stats mail to user
	 *
	 * @param   string  $body   Email body
	 * @param   string  $email  User email
	 *
	 * @return  object
	 *
	 * @since  3.2.0
	 */
	public function sendStatsEmailToUser($body, $email)
	{
		$config   = Factory::getConfig();
		$sitename = $config->get('sitename');
		$from     = $config->get('mailfrom');
		$fromname = $config->get('fromname');
		$find     = array ('[SITENAME]');
		$replace  = array($sitename);
		$body     = str_replace($find, $replace, $body);
		$subject  = str_replace($find, $replace, Text::_('COM_SOCIALADS_PERIDIC_STATS_SUBJECT'));

		$mailer   = Factory::getMailer();
		$mailer->isHTML(true);
		$mailer->Encoding = 'base64';
		$mailer->addRecipient($email);
		$mailer->setSender(array($from, $fromname));
		$mailer->setSubject($subject);
		$mailer->setBody($body);

		return $mailer->Send();
	}
}

JApplicationCli::getInstance('StatsEmail')->execute();
