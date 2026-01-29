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
 * @since  3.4.1
 */
class TjHouseKeepingUpdatespentData extends TjModelHouseKeeping
{
	public $title       = "Update Wallet Wrong spent amount";

	public $description = "Update incorrect entries from Wallet transaction stats table.";

	/**
	 * This function migrate duplicate arcthive stats
	 *
	 * @return  array  $result
	 *
	 * @since   3.4.1
	 */
	public function migrate()
	{
		$result = array();
		$db     = Factory::getDbo();

		try
		{
			$query = $db->getQuery(true);
			$query = "SHOW TABLES LIKE '#__ad_wallet_transc'";
			$db->setQuery($query);
			$backup_exists = $db->loadResult();

			if (!$backup_exists)
			{
				$query = "CREATE TABLE IF NOT EXISTS #__ad_wallet_transc_backup LIKE #__ad_wallet_transc;";
				$db->setQuery($query);

				if ($db->execute())
				{
					$query = $db->getQuery(true);
					$query->select('*');
					$query->from($db->quoteName('#__ad_wallet_transc_backup'));
					$db->setQuery($query);
					$results = $db->loadAssocList();

					if (empty($results))
					{
						$query = $db->getQuery(true);
						$query = "INSERT INTO  #__ad_wallet_transc_backup SELECT * FROM #__ad_wallet_transc";
						$db->setQuery($query);

						$db->execute();
					}
				}
			}

			// Get all advertiser campaigns
			$query = $db->getQuery(true);
			$query->select('DISTINCT ' . $db->quoteName('created_by'));
			$query->from($db->quoteName('#__ad_campaign'));
			$db->setQuery($query);
			$result = $db->loadColumn();

			foreach ($result as $creator)
			{
				// Get last credit balance
				$query2 = $db->getQuery(true);
				$query2 = "SELECT time, balance FROM #__ad_wallet_transc WHERE user_id= '" . $creator . "' AND type= 'O' ORDER BY id DESC";
				$db->setQuery($query2);
				$creditData = $db->loadObject();

				// Get all the wrong spent records after the last credit
				$query3 = $db->getQuery(true);
				$query3 = "SELECT * FROM #__ad_wallet_transc WHERE time >= '" . $creditData->time . "' AND user_id = '"
			. $creator . "' AND type= 'C' ORDER BY id ASC";
				$db->setQuery($query3);
				$transactionList = $db->loadObjectList();
				$balance = $creditData->balance;

				if ($balance > 0)
				{
					foreach ($transactionList as $transaction)
					{
						$updatedBalance = $balance - $transaction->spent;
						$query = $db->getQuery(true);

						// Fields to update.
						$fields = array(
							$db->quoteName('balance') . ' = ' . $db->quote($updatedBalance)
						);

						// Conditions for which records should be updated.
						$conditions = array(
							$db->quoteName('spent') . ' > 0',
							$db->quoteName('id') . ' = ' . $db->quote($transaction->id)
						);

						$query->update($db->quoteName('#__ad_wallet_transc'))->set($fields)->where($conditions);

						$db->setQuery($query);

						if ($db->execute())
						{
							$balance = $updatedBalance;
						}
					}
				}
			}

			$result['status']  = true;
			$result['message'] = "Migration is done successfully";

			return $result;
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
