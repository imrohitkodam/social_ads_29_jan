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
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Text;
/**
 * Methods supporting a list of Socialads records.
 *
 * @since  1.6
 */
class SocialadsModelCampaigns extends ListModel
{
	/**
	 * Constructor.
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 *
	 * @see   JController
	 *
	 * @since   1.6
	 */
	public function __construct($config = array())
	{
		if (empty($config['filter_fields']))
		{
			$config['filter_fields'] = array(
				'id', 'a.id',
				'user_id', 'a.user_id',
				'ordering', 'a.ordering',
				'state', 'a.state',
				'uname', 'u.name',
				'campaign', 'a.campaign',
				'daily_budget', 'a.daily_budget',
				'state', 'a.state',
				'no_of_ads', 'no_of_ads',
				'clicks', 'clicks',
				'impressions', 'impressions',
				'ctr','ctr'
			);
		}

		parent::__construct($config);
	}

	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @param   integer  $ordering   An optional associative array of configuration settings.
	 * @param   integer  $direction  An optional associative array of configuration settings.
	 *
	 * @return  integer
	 */
	protected function populateState($ordering = null, $direction = null)
	{
		// Initialise variables.
		$app = Factory::getApplication('administrator');

		// Load the filter state.
		$search = $app->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
		$this->setState('filter.search', $search);

		$published = $app->getUserStateFromRequest($this->context . '.filter.state', 'filter_published', '', 'string');
		$this->setState('filter.state', $published);

		// Load the parameters.
		$params = ComponentHelper::getParams('com_socialads');
		$this->setState('params', $params);

		// Filter created by.
		$createdBy = $app->getUserStateFromRequest($this->context . '.filter.usernamelist', 'filter_usernamelist', '', 'string');
		$this->setState('filter.usernamelist', $createdBy);

		// List state information.
		parent::populateState('a.id', 'asc');
	}

	/**
	 * Method to get a store id based on model configuration state.
	 *
	 * This is necessary because the model is used by the component and
	 * different modules that might need different sets of data or different
	 * ordering requirements.
	 *
	 * @param   string  $id  A prefix for the store id.
	 *
	 * @return  string  A store id.
	 *
	 * @since	1.6
	 */
	protected function getStoreId($id = '')
	{
		// Compile the store id.
		$id .= ':' . $this->getState('filter.search');
		$id .= ':' . $this->getState('filter.state');

		return parent::getStoreId($id);
	}

	/**
	 * Build an SQL query to load the list data.
	 *
	 * @return	JDatabaseQuery
	 *
	 * @since	1.6
	 */
	protected function getListQuery()
	{
		// Create a new query object.
		$user = Factory::getUser();
		$db    = $this->getDbo();
		$query = $db->getQuery(true);

		// Select the required fields from the table.

		$query->select($this->getState('list.select', 'DISTINCT a.*'));
		$query->select('COUNT(d.camp_id) as no_of_ads, SUM(d.clicks) as clicks, SUM(d.impressions) as impressions, u.name as uname');
		$query->from($db->quoteName('#__ad_campaign', 'a'));

		// Join over the users for the checked out user.
		$query->select($db->quoteName('uc.name', 'editor'));
		$query->join('LEFT', $db->quoteName('#__users', 'uc') . ' ON (' . $db->quoteName('uc.id') . ' = ' . $db->quoteName('a.checked_out') . ')');
		$query->join('LEFT', $db->quoteName('#__ad_data', 'd') . ' ON (' . $db->quoteName('d.camp_id') . ' = ' . $db->quoteName('a.id') . ')');

		// Join over the created by field 'created_by'
		$query->join('LEFT', $db->quoteName('#__users', 'u') . ' ON (' . $db->quoteName('u.id') . ' = ' . $db->quoteName('a.created_by') . ')');

		if (!Factory::getUser()->authorise('core.edit.state', 'com_socialads'))
		{
			$query->where($db->quoteName('a.state') . '= 1');
		}

		$query->group($db->quoteName('a.id'));

		// Filter by published state
		$published = $this->getState('filter.state');

		if (is_numeric($published))
		{
			$query->where($db->quoteName('a.state'). ' = ' . (int) $published);
		}

		// Filter by search in title
		$search = $this->getState('filter.search');

		if (!empty($search))
		{
			$search = $db->Quote('%' . $db->escape($search, true) . '%');
			$query->where('( a.id LIKE ' . $search .
					'  OR  a.campaign LIKE ' . $search .
					'  OR  a.daily_budget LIKE ' . $search .
					' )'
					);
		}

		// Filter by username
		$filterCreator = $this->getState('filter.usernamelist');

		if (!empty($filterCreator))
		{
			$query->where($db->quoteName('a.created_by') . '= ' . $filterCreator);
		}

		// Add the list ordering clause.
		$orderCol  = $this->state->get('list.ordering');
		$orderDirn = $this->state->get('list.direction');

		if ($orderCol && $orderDirn)
		{
			$query->order($db->escape($orderCol . ' ' . $orderDirn));
		}

		return $query;
	}

	/**
	 * Method to find Ignore count of perticular ad
	 *
	 * @param   integer  $ad_id  An ad_id for perticular ad
	 *
	 * @return  integer
	 *
	 * @since  5.0.2
	 */
	public function getIgnorecount($ad_id)
	{
		// Get a database connection object
		$db = Factory::getDbo();
		// Create a new query object
		$query = $this->$db->getQuery(true);
		$query->select('COUNT(adid)');
		$query->from($this->$db->quoteName('#__ad_ignore'));
		$query->where($this->$db->quoteName('adid') . ' = ' . $ad_id);
		// Set the query and execute it
		$db->setQuery($query);
		// Fetch the count result
		$ignorecount = $db->loadresult();

		return $ignorecount;
	}

	/**
	 * To get the values from table
	 *
	 * @return  items
	 *
	 * @since  1.6
	 */
	public function getItems()
	{
		$items = parent::getItems();

		return $items;
	}

	/**
	 * Export ads stats into a csv file
	 *
	 * @return  void
	 *
	 * @since  5.0.2
	 **/
	public function adCsvExport()
	{
		// Get the database object and query object
		$db = Factory::getDbo();
		$query = $db->getQuery(true);

		// Get the query for the list data
		$query = $this->getListQuery();

		// Set the query to the database object
		$db->setQuery($query);

		// Execute the query and get the result
		$results = $db->loadObjectList();

		// Initialize the CSV data
		$csvData = null;

		// Add headers for the CSV file
		$csvData .= Text::_('COM_SOCIAL_ADS_CAMPAIGN_NAME') . "," .
			Text::_('COM_SOCIAL_ADS_DAILY_BUDGET') . "," .
			Text::_('COM_SOCIAL_ADS_CLICKS') . "," .
			Text::_('COM_SOCIAL_ADS_IMPRESSIONS') . "," .
			Text::_('COM_SOCIAL_ADS_NUMBER_OF_ADS') . "," .
			Text::_('COM_SOCIAL_ADS_CTR') . "\n";

		// Define the filename for the CSV
		$filename = "SA_Ads_" . date("Y-m-d_H-i", time()) . ".csv";
		header("Content-disposition: attachment; filename=\"$filename\"");
		header("Content-type: text/csv");

		// Loop through the results and append data to the CSV
		foreach ($results as $result)
		{
			// Extract required fields
			$campName = isset($result->campaign) ? $result->campaign : 'N/A';
			$dailyBudget = isset($result->daily_budget) ? $result->daily_budget : 0;
			$clicks = $result->clicks;
			$impressions = $result->impressions;
			$ctr = ($impressions > 0) ? round(($clicks / $impressions) * 100, 2) . '%' : '0%';
			$numberOfAds = isset($result->no_of_ads) ? $result->no_of_ads : 0;

			// Append data to the CSV
			$csvData .= '"' . $campName . '","' . $dailyBudget . '","' . $clicks . '","' . $impressions . '","' . $numberOfAds . '","' . $ctr . "\"\n";
		}
	}
}
