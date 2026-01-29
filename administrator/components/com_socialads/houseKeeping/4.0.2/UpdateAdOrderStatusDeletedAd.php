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
class TjHouseKeepingUpdateAdOrderStatusDeletedAd extends TjModelHouseKeeping
{
	public $title       = "Update the deleted Ads order status..";

	public $description = "Update the order status in #__ad_orders for deleted Ads to AD_DELETED.";
	
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
			$subQuery = $db->getQuery(true);
			$query    = $db->getQuery(true);

			// Create the base subQuery select statement.
			$subQuery->select($db->quoteName('id'))
				->from($db->quoteName('#__ad_payment_info'));

			// Create the base select statement.
			$fields = array($db->quoteName('status') . ' = "AD_DELETED"');
			$conditions = array(
				$db->quoteName('payment_info_id') . ' NOT IN (' . $subQuery . ')',
				$db->quoteName('payment_info_id') . ' != 0'

			);
			$query->update($db->quoteName('#__ad_orders'))->set($fields)->where($conditions);

			$db->setQuery($query);

			if (!$db->execute())
			{
				$this->setError($this->_db->getErrorMsg());

				return false;
			}

			$result['status']  = true;
			$result['message'] = "Migration for already deleted Ads done successfully!";

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
