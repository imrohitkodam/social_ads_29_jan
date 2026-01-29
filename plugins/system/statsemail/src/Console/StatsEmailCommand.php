<?php
/**
 * @package     Joomla.Console
 * @subpackage  satsemail
 *
 * @copyright   Copyright (C) 2005 - 2021 Clifford E Ford. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// namespace Joomla\Plugin\System\StatsEmail\Console;

\defined('JPATH_PLATFORM') or die;

use Joomla\CMS\Factory;
use Joomla\Console\Command\AbstractCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidOptionException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Application\CliApplication;
// use Joomla\Component\SocialAds\Helper\SaCommonHelper;

$lang = Factory::getLanguage();
$lang->load('plg_system_statsemail', JPATH_ADMINISTRATOR);

class StatsEmailCommand extends AbstractCommand
{
	/**
	 * The default command name
	 *
	 * @var    string
	 *
	 * @since  4.0.0
	 */
	protected static $defaultName = 'statsemail';

	/**
	 * @var InputInterface
	 * @since version
	 */
	private $cliInput;

	/**
	 * SymfonyStyle Object
	 * @var SymfonyStyle
	 * @since 4.0.0
	 */
	private $ioStyle;

	/**
	 * Instantiate the command.
	 *
	 * @since   4.0.0
	 */
	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * Configures the IO
	 *
	 * @param   InputInterface   $input   Console Input
	 * @param   OutputInterface  $output  Console Output
	 *
	 * @return void
	 *
	 * @since 4.0.0
	 *
	 */
	private function configureIO(InputInterface $input, OutputInterface $output)
	{
		$this->cliInput = $input;
		$this->ioStyle = new SymfonyStyle($input, $output);
	}

	/**
	 * Initialise the command.
	 *
	 * @return  void
	 *
	 * @since   4.0.0
	 */
	protected function configure(): void
	{

	}

	/**
	 * Internal function to execute the command.
	 *
	 * @param   InputInterface   $input   The input to inject into the command.
	 * @param   OutputInterface  $output  The output to inject into the command.
	 *
	 * @return  integer  The command exit code
	 *
	 * @since   4.0.0
	 */
	protected function doExecute(InputInterface $input, OutputInterface $output): int
	{
		$this->configureIO($input, $output);
		$category = "stats_email";

		Log::addLogger(array('text_file' => "email_stats.php"), Log::ALL, array($category));

		$this->ioStyle->success(Text::_("COM_SOCIALADS_CLI_EMAIL_STATS_START"));
		Log::add(Text::_("COM_SOCIALADS_CLI_EMAIL_STATS_START"), Log::INFO, $category);

		$params = ComponentHelper::getParams('com_socialads');

		if (!$params->get('weekly_stats'))
		{
			$this->ioStyle->success(Text::_("COM_SOCIALADS_CLI_EMAIL_STATS_DISABLED"));
			Log::add(Text::_("COM_SOCIALADS_CLI_EMAIL_STATS_DISABLED"), Log::ERROR, $category);

			return 1;
		}

		if (!$params->get('site_base_url'))
		{
			$this->ioStyle->success(Text::_("COM_SOCIALADS_CLI_EMAIL_STATS_SITE_BASE_URL_EMPTY"));
			Log::add(Text::_("COM_SOCIALADS_CLI_EMAIL_STATS_SITE_BASE_URL_EMPTY"), Log::ERROR, $category);

			return 1;
		}

		require_once JPATH_SITE . "/components/com_socialads/helpers/common.php";
		$adCreators = SaCommonHelper::getAdCreators();

		if (!count($adCreators))
		{
			$this->ioStyle->success(Text::_("COM_SOCIALADS_CLI_EMAIL_STATS_NO_AD_CREATORS"));
			Log::add(Text::_("COM_SOCIALADS_CLI_EMAIL_STATS_NO_AD_CREATORS"), Log::INFO, $category);

			return 1;
		}

		$failedEmailCount = $successEmailCount = $totalEmails = 0;

		foreach ($adCreators as $userId)
		{
			$statsForPie = SaCommonHelper::statsForPieInMail($userId);

			if (!count($statsForPie))
			{
				$this->ioStyle->success(Text::_("COM_SOCIALADS_CLI_EMAIL_STATS_NO_PIE_STATS"));
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

		$this->ioStyle->success(Text::sprintf("COM_SOCIALADS_MAIL_SENT_STATS", $totalEmails, $successEmailCount, $failedEmailCount));
		Log::add(Text::sprintf("COM_SOCIALADS_MAIL_SENT_STATS", $totalEmails, $successEmailCount, $failedEmailCount), Log::INFO, $category);

		$this->ioStyle->success(Text::_("COM_SOCIALADS_CLI_EMAIL_STATS_END"));
		Log::add(Text::_("COM_SOCIALADS_CLI_EMAIL_STATS_END"), Log::INFO, $category);

		return 1;
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