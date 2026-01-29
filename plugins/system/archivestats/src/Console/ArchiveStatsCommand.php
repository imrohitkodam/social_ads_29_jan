<?php
/**
 * @package     Joomla.Console
 * @subpackage  archivestats
 *
 * @copyright   Copyright (C) 2005 - 2021 Clifford E Ford. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

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


$lang = Factory::getLanguage();
$lang->load('plg_system_archivestats', JPATH_ADMINISTRATOR);

class ArchiveStatsCommand extends AbstractCommand
{
	/**
	 * The default command name
	 *
	 * @var    string
	 *
	 * @since  4.0.0
	 */
	protected static $defaultName = 'archivestats';

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

		$category = "stats_archive";

		Log::addLogger(array('text_file' => "archive_stats.php"), Log::ALL, array($category));

		$this->ioStyle->success(Text::_("COM_SOCIALADS_ARCH_STATS_START"));
		Log::add(Text::_("COM_SOCIALADS_ARCH_STATS_START"), Log::INFO, $category);

		$params = ComponentHelper::getParams('com_socialads');

		if (!$params->get('archivestat'))
		{
			$this->ioStyle->success(Text::_("COM_SOCIALADS_CLI_ARCHIVE_STATS_DISABLED"));
			Log::add(Text::_("COM_SOCIALADS_CLI_ARCHIVE_STATS_DISABLED"), Log::ERROR, $category);

			return 1;
		}

		$days = $params->get('maintain_stats', 30, 'int');

		$backDate = date('Y-m-d  h:m:s', strtotime(date('Y-m-d h:m:s') . ' - ' . $days . ' days'));

		$this->ioStyle->success(Text::sprintf("COM_SOCIALADS_CRON_FROM_TO", $backDate));
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
			$this->ioStyle->success(Text::_("COM_SOCIALADS_CLI_ARCHIVE_STATS_EMPTY"));
			Log::add(Text::_("COM_SOCIALADS_CLI_ARCHIVE_STATS_EMPTY"), Log::INFO, $category);

			return 1;
		}

		$this->ioStyle->success(Text::sprintf("COM_SOCIALADS_CRON_TOTAL_ENTRY", $totalRows));
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
							$this->ioStyle->success($db->stderr());
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
							$this->ioStyle->success($db->stderr());
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
				$this->ioStyle->success($db->getErrorMsg());
				Log::add($db->getErrorMsg(), Log::ERROR, $category);

				continue;
			}
		}

		$this->ioStyle->success(Text::sprintf("COM_SOCIALADS_CLI_ARCHIVE_STATS_ADDED", $count));
		Log::add(Text::sprintf("COM_SOCIALADS_CLI_ARCHIVE_STATS_ADDED", $count), Log::INFO, $category);

		$this->ioStyle->success(Text::sprintf("COM_SOCIALADS_CRON_REDUCE_TO", $count));
		Log::add(Text::sprintf("COM_SOCIALADS_CRON_REDUCE_TO", $count), Log::INFO, $category);

		$this->ioStyle->success(Text::_("COM_SOCIALADS_CLI_ARCHIVE_STATS_END"));
		Log::add(Text::_("COM_SOCIALADS_CLI_ARCHIVE_STATS_END"), Log::INFO, $category);

		return 1;
	}
}
