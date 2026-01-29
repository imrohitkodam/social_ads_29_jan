<?php
/**
 * @version    SVN:<SVN_ID>
 * @package    SocialAds
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2015 TechJoomla. All rights reserved
 * @license    GNU General Public License version 2, or later
 */
defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\Table;

/**
 * Methods supporting a list of Socialads records.
 *
 * @since  1.6
 *
 */
class SocialadsModelWallet extends ListModel
{
	/**
	 * Constructor.
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 *
	 * @see        JController
	 * @since      1.6
	 */
	public function __construct($config = array())
	{
		if (empty($config['filter_fields']))
		{
			$config['filter_fields'] = array(
				'option',
			);
		}

		parent::__construct($config);
	}

	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @param   STRING  $ordering   ordering
	 *
	 * @param   STRING  $direction  direction
	 *
	 * @return  void
	 *
	 * @since    1.6
	 */
	protected function populateState($ordering = null, $direction = null)
	{
		// Initialise variables.
		$app = Factory::getApplication();

		// Load the month state.
		$month = $app->getUserStateFromRequest($this->context . '.month', 'month', '', 'int');
		$this->setState('month', $month);

        // Load the year state.
		$year = $app->getUserStateFromRequest($this->context . '.year', 'year', '', 'int');
		$this->setState('year', $year);

		// Load the user state.
		$user = $app->getUserStateFromRequest($this->context . '.user', 'user', '', 'int');
		$this->setState('user', $user);

		// List state information
		$limit = $app->getUserStateFromRequest('global.list.limit', 'limit', $app->get('list_limit'));
		$this->setState('list.limit', $limit);

		$limitstart = $app->input->getInt('limitstart', 0);
		$this->setState('list.start', $limitstart);

		if ($list = $app->getUserStateFromRequest($this->context . '.list', 'list', array(), 'array'))
		{
			foreach ($list as $name => $value)
			{
				// Extra validations
				switch ($name)
				{
					case 'fullordering':
						$orderingParts = explode(' ', $value);

						if (count($orderingParts) >= 2)
						{
							// Latest part will be considered the direction
							$fullDirection = end($orderingParts);

							if (in_array(strtoupper($fullDirection), array('ASC', 'DESC', '')))
							{
								$this->setState('list.direction', $fullDirection);
							}

							unset($orderingParts[count($orderingParts) - 1]);

							// The rest will be the ordering
							$fullOrdering = implode(' ', $orderingParts);

							if (in_array($fullOrdering, $this->filter_fields))
							{
								$this->setState('list.ordering', $fullOrdering);
							}
						}
						else
						{
							$this->setState('list.ordering', $ordering);
							$this->setState('list.direction', $direction);
						}
						break;

					case 'ordering':
						if (!in_array($value, $this->filter_fields))
						{
							$value = $ordering;
						}
						break;

					case 'direction':
						if (!in_array(strtoupper($value), array('ASC','DESC','')))
						{
							$value = $direction;
						}
						break;

					case 'limit':
						$limit = $value;
						break;

					// Just to keep the default case
					default:
						$value = $value;
						break;
				}

				$this->setState('list.' . $name, $value);
			}
		}

		// Receive & set filters
		if ($filters = $app->getUserStateFromRequest($this->context . '.filter', 'filter', array(), 'array'))
		{
			foreach ($filters as $name => $value)
			{
				$this->setState('filter.' . $name, $value);
			}
		}

		$ordering = $app->input->get('filter_order');

		if (!empty($ordering))
		{
			$list             = $app->getUserState($this->context . '.list');
			$list['ordering'] = $app->input->get('filter_order');
			$app->setUserState($this->context . '.list', $list);
		}

		$orderingDirection = $app->input->get('filter_order_Dir');

		if (!empty($orderingDirection))
		{
			$list              = $app->getUserState($this->context . '.list');

			if (!in_array($orderingDirection, array('acs', 'desc')))
			{
				// Dont change - Default ordering direction is ASC as default ordering column is ordering
				$list['direction'] = 'asc';
			}
			else
			{
				$list['direction'] = $orderingDirection;
			}

			$app->setUserState($this->context . '.list', $list);
		}

		$list = $app->getUserState($this->context . '.list');

		if (empty($list['ordering']))
		{
			$list['ordering'] = 'ordering';
		}

		if (empty($list['direction']))
		{
			$list['direction'] = 'asc';
		}

		if (isset($list['ordering']))
		{
			$this->setState('list.ordering', $list['ordering']);
		}

		if (isset($list['direction']))
		{
			$this->setState('list.direction', $list['direction']);
		}
	}

	/**
	 * Build an SQL query to load the list data.
	 *
	 * @return    JDatabaseQuery
	 *
	 * @since    1.6
	 */
	protected function getListQuery()
	{
		// Create a new query object.
		$user = Factory::getUser();
		$db    = $this->getDbo();
		$query = $db->getQuery(true);

        $mainframe = Factory::getApplication();
        $month = $this->getState('month');
        $year = $this->getState('year');
        $year = $this->getState('year');
        $user_id = $this->getState('user');

		$whr = '';
		$whr1 = '';

		if ($month && $year)
		{
			$whr = " AND month(cdate) =" . $month . "   AND year(cdate) =" . $year . "  ";
			$whr1 = " AND month(DATE(FROM_UNIXTIME(a.time))) =" . $month . "  AND year(DATE(FROM_UNIXTIME(a.time))) =" . $year . "  ";
		}
		elseif ($month == '' && $year)
		{
			$whr = " AND year(cdate) =" . $year . "  ";
			$whr1 = " AND year(DATE(FROM_UNIXTIME(a.time))) =" . $year . "  ";
		}

		$query = "SELECT DATE(FROM_UNIXTIME(a.time)) as time,a.spent as spent,type_id,a.earn as credits,balance,comment
		FROM #__ad_wallet_transc as a WHERE a.user_id = " . $user_id . " " . $whr1 . " ORDER BY a.time ASC";

		return $query;
	}

	/**
	 * Method to get item data
	 *
	 * @return  form data
	 *
	 * @since   2.2
	 */
	public function getItems()
	{
		$ad_stat = parent::getItems();

        $all_info = $camp_name = $coupon_code = $ad_title = array();

		$walletBalance = 0;

		if (!empty($ad_stat))
		{
			foreach ($ad_stat as $key)
			{
				// To get campaign name
				$db = Factory::getDbo();
				$query = $db->getQuery(true);
				$query->select(array('campaign'))
					->from($db->quoteName('#__ad_campaign'))
					->where($db->quoteName('id') . " = " . $db->quote($key->type_id));

				$db->setQuery($query);
				$camp_name[$key->type_id] = $db->loadObjectList();

				// To get coupon code
				$db = Factory::getDbo();
				$query = $db->getQuery(true);
				$query->select(array('coupon'))
					->from($db->quoteName('#__ad_orders'))
					->where($db->quoteName('id') . " = " . $db->quote($key->type_id));

				$db->setQuery($query);
				$coupon_code[$key->type_id] = $db->loadObjectList();

				$ad_til = explode('|', $key->comment);

				if (isset($ad_til[1]))
				{
					$query	= $db->getQuery(true);
					$query->select($db->quoteName('ad_title'));
					$query->from($db->quoteName('#__ad_data'));
					$query->where($db->quoteName('ad_id') . ' = ' . $ad_til[1]);

					$this->_db->setQuery($query);
					$ad_title[$ad_til[1]] = $this->_db->loadresult();
				}

				$walletBalance = $key->balance;
			}
		}

		$all_info['wallet_balance'] = $walletBalance;

		array_push($all_info, $ad_stat, $camp_name, $coupon_code, $ad_title);

		return $all_info;
	}

	/**
	 * Returns a reference to the a Table object, always creating it.
	 *
	 * @param   type    $type    The table type to instantiate
	 * @param   string  $prefix  A prefix for the table class name. Optional.
	 * @param   array   $config  Configuration array for model. Optional.
	 *
	 * @return   JTable    A database object
	 *
	 * @since    1.6
	 */
	public function getTable($type = 'ad_wallet_transc', $prefix = 'SocialadsTable', $config = array())
	{
		$this->addTablePath(JPATH_COMPONENT_ADMINISTRATOR . '/tables');

		return Table::getInstance($type, $prefix, $config);
	}
}
