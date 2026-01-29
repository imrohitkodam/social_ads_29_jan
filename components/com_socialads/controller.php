<?php
/**
 * @version    SVN:<SVN_ID>
 * @package    Com_Socialads
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2019 TechJoomla. All rights reserved
 * @license    GNU General Public License version 2, or later
 */
// No direct access
defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Filesystem\Folder;

/**
 * main controller class
 *
 * @since  1.0
 */
class SocialadsController extends BaseController
{
	/**
	 * Method to display a view.
	 *
	 * @param   boolean  $cachable   If true, the view output will be cached
	 * @param   array    $urlparams  An array of safe url parameters and their variable types, for valid values see {@link JFilterInput::clean()}.
	 *
	 * @return  JController  This object to support chaining.
	 *
	 * @since  1.5
	 */
	public function display($cachable = false, $urlparams = false)
	{
		$view = Factory::getApplication()->input->getCmd('view', 'archivestats');
		Factory::getApplication()->input->set('view', $view);

		parent::display($cachable, $urlparams);

		return $this;
	}

	/**
	 * Single cron URL for running all the functions
	 *
	 * @return  void
	 *
	 * @since  3.1
	 */
	public function saStatisticsCron()
	{
		ini_set('max_execution_time', 900);
		$com_params = ComponentHelper::getParams('com_socialads');
		$input      = Factory::getApplication()->input;
		$pkey       = $input->get('pkey');

		if ($pkey != $com_params->get('cron_key'))
		{
			echo Text::_("COM_SOCIALADS_CRON_KEY_MSG");

			return;
		}

		$func = $input->get('func');

		if ($func)
		{
			$this->$func();
		}
		else
		{
			$funcs = array ('archiveStats','sendStatsEmail');	 /*add the function names you need to add here*/

			foreach ($funcs as $func)
			{
				echo '<br>***************************************<br>';
				$this->$func();
				echo '<br>***************************************<br>';
			}
		}
	}

	/**
	 * Task for archiving stats
	 *
	 * @return  void
	 *
	 * @since  3.1
	 */
	public function archiveStats()
	{
		$category = "stats_archive";

		Log::addLogger(array('text_file' => "archive_stats.php"), Log::ALL, array($category));

		echo Text::_("COM_SOCIALADS_ARCH_STATS_START") . '<br>';
		Log::add(Text::_("COM_SOCIALADS_ARCH_STATS_START"), Log::INFO, $category);

		$input  = Factory::getApplication()->input;
		$params = ComponentHelper::getParams('com_socialads');
		$pkey   = $input->get('pkey');

		if ($pkey != $params->get('cron_key'))
		{
			echo Text::_("COM_SOCIALADS_CRON_KEY_MSG") . '<br>';
			Log::add(Text::_("COM_SOCIALADS_CRON_KEY_MSG"), Log::INFO, $category);

			return;
		}

		if (!$params->get('archivestat'))
		{
			echo Text::_("COM_SOCIALADS_CLI_ARCHIVE_STATS_DISABLED") . '<br>';
			Log::add(Text::_("COM_SOCIALADS_CLI_ARCHIVE_STATS_DISABLED"), Log::ERROR, $category);

			return;
		}

		$days = $params->get('maintain_stats', 30, 'int');

		$backDate = date('Y-m-d  h:m:s', strtotime(date('Y-m-d h:m:s') . ' - ' . $days . ' days'));

		echo Text::sprintf("COM_SOCIALADS_CRON_FROM_TO", $backDate) . '<br>';
		Log::add(Text::sprintf("COM_SOCIALADS_CRON_FROM_TO", $backDate), Log::INFO, $category);

		$db = Factory::getDBO();

		// Query to get stats total count
		$query = $db->getQuery(true);
		$query->select('COUNT(id) as total');
		$query->from($db->quoteName('#__ad_stats'));
		$query->where($db->quoteName('time') . "<'" . $backDate . "'");
		$query->orWhere($db->quoteName('time') . " IS NULL");
		$db->setQuery($query);
		$totalRows = $db->loadResult();

		if (!$totalRows)
		{
			echo Text::_("COM_SOCIALADS_CLI_ARCHIVE_STATS_EMPTY") . '<br>';
			Log::add(Text::_("COM_SOCIALADS_CLI_ARCHIVE_STATS_EMPTY"), Log::INFO, $category);

			return;
		}

		echo Text::sprintf("COM_SOCIALADS_CRON_TOTAL_ENTRY", $totalRows) . '<br>';
		Log::add(Text::sprintf("COM_SOCIALADS_CRON_TOTAL_ENTRY", $totalRows), Log::INFO, $category);

		$limit = $input->getInt('rowsLimit', 1000);
		$count = 0;

		while ($totalRows > 0)
		{
			$totalRows -= $limit;

			// Query to get stats
			$query = $db->getQuery(true);
			$query->select('id, ad_id, display_type, time');
			$query->from($db->quoteName('#__ad_stats'));
			$query->where($db->quoteName('time') . " < " . $db->quote($backDate));
			$query->orWhere($db->quoteName('time') . " IS NULL");
			$query->setLimit($limit);

			$db->setQuery($query);
			$dbStats  = $db->loadObjectList();
			$newStats = array();

			foreach ($dbStats as $stat)
			{
				$date = date('Y-m-d', strtotime($stat->time));

				// 0 = imprs; 1 = clks;
				if ($stat->display_type == '0')
				{
					if (isset($newStats[$date][$stat->ad_id]['imprs']))
					{
						$newStats[$date][$stat->ad_id]['imprs'] += 1;
					}
					else 
					{
						$newStats[$date][$stat->ad_id]['imprs'] = 1;
					}
				}
				elseif ($stat->display_type == '1')
				{
					if (isset($newStats[$date][$stat->ad_id]['clks']))
					{
						$newStats[$date][$stat->ad_id]['clks'] += 1;
					}
					else 
					{
						$newStats[$date][$stat->ad_id]['clks'] = 1;
					}
				}
			}

			foreach ($newStats as $date => $stats)
			{
				foreach ($stats as $id => $data)
				{
					$impression = (isset($data['imprs'])) ? $data['imprs'] : 0;
					$click      = (isset($data['clks'])) ? $data['clks'] : 0;

					// Query to get stats
					$query = $db->getQuery(true);
					$query->select('*');
					$query->from($db->quoteName('#__ad_archive_stats'));
					$query->where(
						array(
							$db->quoteName('ad_id') . ' = ' . (int) $id,
							$db->quoteName('date') . ' = ' . $db->quote($date)
						)
					);

					$db->setQuery($query);
					$statObj = $db->loadObject();

					if ($statObj)
					{
						$statObj->impression += $impression;
						$statObj->click      += $click;

						if (!$db->updateObject('#__ad_archive_stats', $statObj, 'id'))
						{
							echo $db->stderr() . '<br>';
							Log::add($db->stderr(), Log::ERROR, $category);

							continue;
						}
					}
					else
					{
						$stat             = new stdClass;
						$stat->ad_id      = $id;
						$stat->date       = $date;
						$stat->impression = $impression;
						$stat->click      = $click;

						if (!$db->insertObject('#__ad_archive_stats', $stat, 'id'))
						{
							echo $db->stderr() . '<br>';
							Log::add($db->stderr(), Log::ERROR, $category);

							continue;
						}
					}

					$count++;
				}
			}

			$query = $db->getQuery(true);
			$query->delete($db->quoteName('#__ad_stats'));
			$query->where($db->quoteName('time') . " < " . $db->quote($backDate));
			$query->orWhere($db->quoteName('time') . " IS NULL");
			$query->setLimit($limit);

			$db->setQuery($query);

			if (!$db->execute())
			{
				echo $db->getErrorMsg() . '<br>';
				Log::add($db->getErrorMsg(), Log::ERROR, $category);

				continue;
			}
		}

		echo Text::sprintf("COM_SOCIALADS_CLI_ARCHIVE_STATS_ADDED", $count) . '<br>';
		Log::add(Text::sprintf("COM_SOCIALADS_CLI_ARCHIVE_STATS_ADDED", $count), Log::INFO, $category);

		echo Text::sprintf("COM_SOCIALADS_CRON_REDUCE_TO", $count) . '<br>';
		Log::add(Text::sprintf("COM_SOCIALADS_CRON_REDUCE_TO", $count), Log::INFO, $category);

		echo Text::_("COM_SOCIALADS_CLI_ARCHIVE_STATS_END") . '<br>';
		Log::add(Text::_("COM_SOCIALADS_CLI_ARCHIVE_STATS_END"), Log::INFO, $category);
	}

	/**
	 * Weekly cron mail
	 *
	 * @return  void
	 *
	 * @since  3.1
	 */
	public function sendStatsEmail()
	{
		$category = "stats_email";

		Log::addLogger(array('text_file' => "email_stats.php"), Log::ALL, array($category));

		echo Text::_("COM_SOCIALADS_CLI_EMAIL_STATS_START") . '<br>';
		Log::add(Text::_("COM_SOCIALADS_CLI_EMAIL_STATS_START"), Log::INFO, $category);

		$input  = Factory::getApplication()->input;
		$params = ComponentHelper::getParams('com_socialads');
		$pkey   = $input->get('pkey');

		if ($pkey != $params->get('cron_key'))
		{
			echo Text::_("COM_SOCIALADS_CRON_KEY_MSG") . '<br>';

			return;
		}

		if (!$params->get('weekly_stats'))
		{
			echo Text::_("COM_SOCIALADS_CLI_EMAIL_STATS_DISABLED") . '<br>';
			Log::add(Text::_("COM_SOCIALADS_CLI_EMAIL_STATS_DISABLED"), Log::ERROR, $category);

			return;
		}

		if (!$params->get('site_base_url'))
		{
			echo Text::_("COM_SOCIALADS_CLI_EMAIL_STATS_SITE_BASE_URL_EMPTY") . '<br>';
			Log::add(Text::_("COM_SOCIALADS_CLI_EMAIL_STATS_SITE_BASE_URL_EMPTY"), Log::ERROR, $category);

			return;
		}

		require_once JPATH_SITE . "/components/com_socialads/helpers/common.php";
		$adCreators = SaCommonHelper::getAdCreators();

		if (!count($adCreators))
		{
			echo Text::_("COM_SOCIALADS_CLI_EMAIL_STATS_NO_AD_CREATORS") . '<br>';
			Log::add(Text::_("COM_SOCIALADS_CLI_EMAIL_STATS_NO_AD_CREATORS"), Log::INFO, $category);

			return;
		}

		$failedEmailCount = $successEmailCount = $totalEmails = 0;

		foreach ($adCreators as $userId)
		{
			$statsForPie = SaCommonHelper::statsForPieInMail($userId);

			if (!count($statsForPie))
			{
				echo Text::_("COM_SOCIALADS_CLI_EMAIL_STATS_NO_PIE_STATS") . '<br>';
				Log::add(Text::_("COM_SOCIALADS_CLI_EMAIL_STATS_NO_PIE_STATS"), Log::INFO, $category);

				continue;
			}

			$user = SaCommonHelper::getUserDetails($userId, 'username, name, email');

			if (empty($user))
			{
				continue;
			}

			$body = $params->get('intro_text_mail');

			// Replace the intro text from component option
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

		echo Text::sprintf("COM_SOCIALADS_MAIL_SENT_STATS", $totalEmails, $successEmailCount, $failedEmailCount) . '<br>';
		Log::add(Text::sprintf("COM_SOCIALADS_MAIL_SENT_STATS", $totalEmails, $successEmailCount, $failedEmailCount), Log::INFO, $category);

		echo Text::_("COM_SOCIALADS_CLI_EMAIL_STATS_END") . '<br>';
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
	 * @since  3.1
	 */
	public function statsEmailBody($statAd, $email)
	{
		$adId     = $statAd[2];
		$clicks   = $impressions = 0;
		$ad       = SaCommonHelper::getAdInfo($adId, 'ad_title');
		$title    = (!empty($ad->ad_title)) ? '"' . $ad->ad_title . '"' : $adId;
		$ad_title = Text::_("COM_SOCIALADS_CRON_AD_TITLE") . ' <b>' . $title . '</b>';
		$db       = Factory::getDbo();
		$query    = $db->getQuery(true);
		$query->select('id');
		$query->from($db->quoteName('#__menu'));
		$query->where($db->quoteName('link') . ' LIKE ' . $db->quote('%' . 'index.php?option=com_socialads&view=adform%'));
		$query->where($db->quoteName('published') . ' = ' . 1);
		$query->setLimit(1);

		$db->setQuery($query);

		$itemId       = $db->loadResult();
		$params       = ComponentHelper::getParams('com_socialads');
		$siteBaseUrl  = $params->get('site_base_url');
		$edit_ad_link = $siteBaseUrl . '/index.php?option=com_socialads&view=adform&ad_id=' . (int) $adId . '&Itemid=' . $itemId;

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
			$chco    = '7777CC|76A4FB';
			$chdl    = 'clicks|Impressions';
			$url     = "https://chart.googleapis.com/chart?chs=300x150&cht=p3&chd=t:" . $cl_impr . "&chdl=Impressions|Clicks";
		}

		$CTR = 0.00;

		if ($clicks && $impressions)
		{
			$CTR = number_format(($clicks / $impressions) * 100, 2);
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
	 * @since  3.1
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

	/**
	 * Method to call remove unused image
	 *
	 * @return  void
	 *
	 * @since  3.1
	 */
	public function removeimagesCall()
	{
		$this->removeImages(0);
	}

	/**
	 * Method to remove unused images
	 *
	 * @param   integer  $called_from  Default variable
	 *
	 * @return  boolean
	 *
	 * @since  3.1
	 */
	public function removeImages($called_from = 0)
	{
		$input = Factory::getApplication()->input;

		if ($called_from != 0)
		{
			$pkey = $input->get('pkey');

			if ($pkey != $com_params->get('cron_key'))
			{
				echo Text::_("COM_SOCIALADS_CRON_KEY_MSG");

				return;
			}
		}

		$images_del    = array();
		$images        = array();
		$current_files = array();
		$results       = array();
		$database      = Factory::getDBO();
		$match         = $database->escape('images/socialads/');
		$query         = "SELECT REPLACE(ad_image, '{$match}','') as ad_image  FROM #__ad_data WHERE ad_image<>''";
		$database->setQuery($query);
		$images_del = $database->loadColumn();

		// We can skip the "frames" folder SAFELY here, it is used for gif resizing
		$current_files = Folder::files(JPATH_SITE . '/images/socialads', '', 0, 0, array('frames','index.html'));
		$no_files_del  = 0;

		if (count($current_files) > count($images_del))
		{
			$results = array_diff($current_files, $images_del);

			if ($results)
			{
				?>
				<div class="alert alert-info">
					<?php echo Text::_("COM_SOCIALADS_UNUSED_IMAGE_LIST");?>
				</div>
				<?php
				foreach ($results as $img_to_del)
				{
					if ($img_to_del)
					{
						if (!File::delete(JPATH_SITE . '/images/socialads/' . $img_to_del))
						{
							if ($called_from == 0)
							{
								echo "[" . $img_to_del . "] " . Text::_("COM_SOCIALADS_FILE_DEL_FAIL");
								echo "<br>";
							}
						}
						else
						{
							if ($called_from == 0)
							{
								echo "<br>";
								echo "[" . $img_to_del . "] " . Text::_("COM_SOCIALADS_FILE_DEL_SUCCESSFULLY");
								$no_files_del++;
							}
						}
					}
				}
			}
			else
			{
				if ($called_from == 0)
				{
					?>
					<div class="alert alert-info">
					<?php
						echo "<br>";
						echo Text::_("COM_SOCIALADS_NO_FILE_DEL");  ?>
					</div >
					<?php
				}
			}
		}
		else
		{
			if ($called_from == 0)
			{
				?>
				<div class="alert alert-info">
					<?php
					echo "<br>";
					echo Text::_("COM_SOCIALADS_NO_FILE_DEL"); ?>
				</div>
			<?php
			}
		}

		if ($called_from == 0)
		{
			if ($no_files_del)
			{
				?>
				<div class="alert alert-success">
				<?php
				echo "<br>";
				echo Text::_("COM_SOCIALADS_NUMBER_OF_FILE_DEL") . ":" . $no_files_del; ?>
				</div>
				<?php
			}
		}

		return;
	}
}
