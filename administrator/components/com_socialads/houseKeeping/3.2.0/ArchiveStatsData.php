<?php
/**
 * @version    SVN: <svn_id>
 * @package    SocialAds
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2019 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\Registry\Registry;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

/**
 * Migration file for merging duplicate stats for SocialAds
 *
 * @since  3.2.0
 */
class TjHouseKeepingArchiveStatsData extends TjModelHouseKeeping
{
	public $title       = "Migrate ArchiveStatsData";

	public $description = "Migrate the duplicate entries in archive stats table.";

	/**
	 * This function migrate duplicate archive stats
	 *
	 * @return  array  $result
	 *
	 * @since   3.2.0
	 */
	public function migrate()
	{
		$limit  = 200;
		$result = array();
		$db     = Factory::getDbo();

		// Get last processed id from session
		$session         = Factory::getSession();
		$sessionKey      = 'archiveStatsLastProcessedId';
		$lastProcessedId = $session->get($sessionKey);

		// If not found in session set it to 0
		if (is_null($lastProcessedId))
		{
			$lastProcessedId = 0;
		}

		$processedId = $lastProcessedId;

		try
		{
			// Get first 50 rows from last processed id
			$query = $db->getQuery(true)
				->select('*')
				->from('#__ad_archive_stats')
				->where($db->quoteName('id') . ' > ' . $db->quote($lastProcessedId))
				->order($db->quoteName('id') . ' ASC');
			$db->setQuery($query, 0, $limit);
			$rows = $db->loadObjectList();

			// If no more rows available
			if (!count($rows))
			{
				$session->clear($sessionKey);

				$result['status']  = true;
				$result['message'] = "Migration is done successfully";

				return $result;
			}

			foreach ($rows as $row)
			{
				// Update last processed id as current row id
				$processedId = $row->id;

				// Get existing first row for given date and ad_id
				$query = $db->getQuery(true)
					->select('*')
					->from($db->quoteName('#__ad_archive_stats'))
					->where(
						array(
							$db->quoteName('ad_id') . " = " . $db->quote($row->ad_id),
							$db->quoteName('date') . " = " . $db->quote($row->date)
						)
					);
				$db->setQuery($query);
				$existingRow = $db->loadObject();

				// If new and current row same then continue
				if (!$existingRow || $existingRow->id == $row->id)
				{
					continue;
				}

				// Check which row to keep - We keep old id and remove new id
				if ($existingRow->id < $row->id)
				{
					$keepRow   = $existingRow;
					$removeRow = $row;
				}
				else
				{
					$keepRow   = $row;
					$removeRow = $existingRow;
				}

				// Remove unused variables
				unset($existingRow, $row);

				// Calculate new clicks and impressions by merging with old entry
				$newImpr = $keepRow->impression + $removeRow->impression;
				$newClks = $keepRow->click + $removeRow->click;

				// Update row with newly calculated impr and clicks
				$query = $db->getQuery(true)
					->update($db->quoteName('#__ad_archive_stats'))
					->set(
						array(
							$db->quoteName('impression') . ' = ' . $db->quote($newImpr),
							$db->quoteName('click') . ' = ' . $db->quote($newClks)
						)
					)
					->where($db->quoteName('id') . ' = ' . $db->quote($keepRow->id));
				$db->setquery($query);

				// If failed to update return error
				if (!$db->execute())
				{
					$result['err_code'] = '';
					$result['status']   = false;
					$result['message']  = 'Failed to update the row with Id : ' . $keepRow->id;

					return;
				}

				// Remove row
				$query = $db->getQuery(true)
					->delete($db->quoteName('#__ad_archive_stats'))
					->where($db->quoteName('id') . ' = ' . $db->quote($removeRow->id));
				$db->setQuery($query);

				// If failed to update return error
				if (!$db->execute())
				{
					$result['err_code'] = '';
					$result['status']   = false;
					$result['message']  = 'Failed to delete the row with Id : ' . $removeRow->id;

					return;
				}
			}

			// Update the last processed Id in session
			$session->set($sessionKey, $processedId);

			// Check if all rows finished
			if ($processedId == $lastProcessedId)
			{
				$session->clear($sessionKey);

				$result['status']  = true;
				$result['message'] = "Migration is done successfully";
			}
			else
			{
				$result['status']  = '';
				$result['message'] = "Migration is inprogress";
			}
		}
		catch (Exception $e)
		{
			$result['err_code'] = '';
			$result['status']   = false;
			$result['message']  = $e->getMessage();
		}

		return $result;
	}
}
