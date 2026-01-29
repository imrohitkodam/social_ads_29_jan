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
class ArchiveStats extends CliApplication
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
		$category = "stats_archive";

		Log::addLogger(array('text_file' => "archive_stats.php"), Log::ALL, array($category));

		$this->out(Text::_("COM_SOCIALADS_ARCH_STATS_START"));
		Log::add(Text::_("COM_SOCIALADS_ARCH_STATS_START"), Log::INFO, $category);

		$params = ComponentHelper::getParams('com_socialads');

		if (!$params->get('archivestat'))
		{
			$this->out(Text::_("COM_SOCIALADS_CLI_ARCHIVE_STATS_DISABLED"));
			Log::add(Text::_("COM_SOCIALADS_CLI_ARCHIVE_STATS_DISABLED"), Log::ERROR, $category);

			return;
		}

		$days = $params->get('maintain_stats', 30, 'int');

		$backDate = date('Y-m-d  h:m:s', strtotime(date('Y-m-d h:m:s') . ' - ' . $days . ' days'));

		$this->out(Text::sprintf("COM_SOCIALADS_CRON_FROM_TO", $backDate));
		Log::add(Text::sprintf("COM_SOCIALADS_CRON_FROM_TO", $backDate), Log::INFO, $category);

		$db    = Factory::getDBO();
		$query = $db->getQuery(true);
		$query->select('COUNT(id) as total');
		$query->from($db->quoteName('#__ad_stats'));
		$query->where($db->quoteName('time') . "<'" . $backDate . "'");
		$query->orWhere($db->quoteName('time') . " IS NULL");
		$db->setQuery($query);
		$totalRows = $db->loadResult();

		if (!$totalRows)
		{
			$this->out(Text::_("COM_SOCIALADS_CLI_ARCHIVE_STATS_EMPTY"));
			Log::add(Text::_("COM_SOCIALADS_CLI_ARCHIVE_STATS_EMPTY"), Log::INFO, $category);

			return;
		}

		$this->out(Text::sprintf("COM_SOCIALADS_CRON_TOTAL_ENTRY", $totalRows));
		Log::add(Text::sprintf("COM_SOCIALADS_CRON_TOTAL_ENTRY", $totalRows), Log::INFO, $category);

		$limit = 100;
		$count = 0;

		while ($totalRows > 0)
		{
			$totalRows -= $limit;

			$query = $db->getQuery(true);
			$query->select('id, ad_id, display_type, time');
			$query->from($db->quoteName('#__ad_stats'));
			$query->where($db->quoteName('time') . " < " . $db->quote($backDate));
			$query->orWhere($db->quoteName('time') . " IS NULL");
			$query->setLimit($limit);

			$db->setQuery($query);
			$dbStats = $db->loadObjectList();

			$newStats = array();

			foreach ($dbStats as $stat)
			{
				$date = date('Y-m-d', strtotime($stat->time));

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
					$click = (isset($data['clks'])) ? $data['clks'] : 0;

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
							$this->out($db->stderr());
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
							$this->out($db->stderr());
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
				$this->out($db->getErrorMsg());
				Log::add($db->getErrorMsg(), Log::ERROR, $category);

				continue;
			}
		}

		$this->out(Text::sprintf("COM_SOCIALADS_CLI_ARCHIVE_STATS_ADDED", $count));
		Log::add(Text::sprintf("COM_SOCIALADS_CLI_ARCHIVE_STATS_ADDED", $count), Log::INFO, $category);

		$this->out(Text::sprintf("COM_SOCIALADS_CRON_REDUCE_TO", $count));
		Log::add(Text::sprintf("COM_SOCIALADS_CRON_REDUCE_TO", $count), Log::INFO, $category);

		$this->out(Text::_("COM_SOCIALADS_CLI_ARCHIVE_STATS_END"));
		Log::add(Text::_("COM_SOCIALADS_CLI_ARCHIVE_STATS_END"), Log::INFO, $category);
	}
}

JApplicationCli::getInstance('ArchiveStats')->execute();
