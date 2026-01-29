<?php
/**
 * @version    SVN:<SVN_ID>
 * @package    Com_Socialads
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2015 TechJoomla. All rights reserved
 * @license    GNU General Public License version 2, or later
 */
defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

use Joomla\CMS\Table\Table;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\Component\ComponentHelper;

/**
 * Methods supporting a list of Socialads records.
 *
 * @since  1.6
 */
class SocialadsModelAds extends ListModel
{
	/**
	 * Constructor.
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 *
	 * @see  JController
	 *
	 * @since  1.6
	 */
	public function __construct($config = array())
	{
		if (empty($config['filter_fields']))
		{
			$config['filter_fields'] = array(
				'id',
				'a.ad_id',
				'ordering',
				'a.ordering',
				'state',
				'a.state',
				'a.created_by',
				'ad_id',
				'created_by',
				'a.created_by',
				'ad_url1',
				'a.ad_url1',
				'ad_url2',
				'a.ad_url2',
				'ad_title',
				'a.ad_title',
				'ad_body',
				'a.ad_body',
				'ad_image',
				'a.ad_image',
				'ad_startdate',
				'a.ad_startdate',
				'ad_enddate',
				'a.ad_enddate',
				'ad_noexpiry',
				'a.ad_noexpiry',
				'ad_payment_type',
				'a.ad_payment_type',
				'ad_credits',
				'a.ad_credits',
				'ad_credits_balance',
				'a.ad_credits_balance',
				'ad_created_date',
				'a.ad_created_date',
				'ad_modified_date',
				'a.ad_modified_date',
				'ad_published',
				'a.ad_published',
				'ad_approved',
				'a.ad_approved',
				'ad_alternative',
				'a.ad_alternative',
				'ad_guest',
				'a.ad_guest',
				'ad_affiliate',
				'a.ad_affiliate',
				'ad_zone',
				'a.ad_zone',
				'layout',
				'a.layout',
				'camp_id',
				'a.camp_id',
				'bid_value',
				'a.bid_value',
				'clicks','clicks',
				'impressions','impressions',
				'campaign', 'c.campaign',
				'from', 'to'
			);
		}

		parent::__construct($config);
	}

	/**
	 * Method to auto-populate the model state.
	 *
	 * @param   integer  $ordering   An optional associative array of configuration settings.
	 * @param   integer  $direction  An optional associative array of configuration settings.
	 *
	 * @return  integer
	 *
	 * Note. Calling getState in this method will result in recursion.
	 */
	protected function populateState($ordering = null, $direction = null)
	{
		// Initialise variables.
		$app = Factory::getApplication('site');

		// Load the filter state.
		$search = $app->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
		$this->setState('filter.search', $search);
		$campaignslist = $app->getUserStateFromRequest($this->context . '.filter.campaignslist', 'filter_campaignslist');
		$this->setState('filter.campaignslist', $campaignslist);
		$zoneslist = $app->getUserStateFromRequest($this->context . '.filter.zoneslist', 'filter_zoneslist');
		$this->setState('filter.zoneslist', $zoneslist);
		$adstatus = $app->getUserStateFromRequest($this->context . '.filter.adstatus', 'filter_adstatus');
		$this->setState('filter.adstatus', $adstatus);
		$from = $app->getUserStateFromRequest($this->context . '.filter.from', 'from');
		$this->setState('filter.from', $from);
		$to = $app->getUserStateFromRequest($this->context . '.filter.to', 'to');
		$this->setState('filter.to', $to);

		// Load the parameters.
		$ad_params = ComponentHelper::getParams('com_socialads');
		$this->setState('params', $ad_params);

		// List state information.
		parent::populateState('a.ad_id', 'asc');
	}

	/**
	 * Build an SQL query to load the list data.
	 *
	 * @return  JDatabaseQuery
	 *
	 * @since  1.6
	 */
	protected function getListQuery()
	{
		// Create a new query object.
		$user  = Factory::getUser();
		$db    = $this->getDbo();
		$query = $db->getQuery(true);

		// Select the required fields from the table.
		$query->select($this->getState('list.select', 'DISTINCT a.*'));
		$query->select($db->quoteName('z.zone_name'));
		$query->select($db->quoteName('ao.status'));
		$query->select($db->quoteName('c.campaign'));

		// $query->select("z.zone_name");
		$query->from($db->quoteName('#__ad_data', 'a'));
		$query->join('LEFT', $db->quoteName('#__ad_campaign', 'c') . 'ON' . $db->quoteName('a.camp_id') . '=' . $db->quoteName('c.id'));
		$query->join('LEFT', $db->quoteName('#__ad_zone', 'z') . 'ON' . $db->quoteName('a.ad_zone') . '=' . $db->quoteName('z.id'));
		$query->join('LEFT', $db->quoteName('#__ad_payment_info', 'p') . 'ON' . $db->quoteName('p.ad_id') . '=' . $db->quoteName('a.ad_id'));
		$query->join('LEFT', $db->quoteName('#__ad_orders', 'ao') . 'ON' . $db->quoteName('ao.id') . '=' . $db->quoteName('p.order_id'));
		$query->join('LEFT', $db->quoteName('#__ad_stats', 'as') . 'ON' . $db->quoteName('as.ad_id') . '=' . $db->quoteName('a.ad_id')); // IDL
		$query->where($db->quoteName('a.created_by') . '=' . $user->id);
		$query->group($db->quoteName('a.ad_id'));
		$db->setQuery($query);

		// Filter by search in title
		$campaignslist = (int) $this->getState('filter.campaignslist');

		if ($campaignslist)
		{
			$query->where($db->quoteName('a.camp_id') . '=' . $campaignslist);
		}

		// Filter by zone
		$zoneslist = (int) $this->getState('filter.zoneslist');

		if ($zoneslist)
		{
			$query->where($db->quoteName('z.id') . '=' . $zoneslist);
		}

		// Filter by from date
		$from = $this->getState('filter.from');

		if ($from)
		{
			$query->where('DATE(' . $db->qn('as.time') . ')' . ' >= ' . $db->quote($from)); // IDL
		}

		// Filter by to date
		$to = $this->getState('filter.to');

		if ($to)
		{
			$query->where('DATE(' . $db->qn('as.time') . ')' . ' <= ' . $db->quote($to)); // IDL
			// $query->where('DATE(' . $db->qn('a.ad_created_date') . ')' . ' <= ' . $db->quote($to));
		}

		// Filter by search in title
		$search = $this->getState('filter.search');

		if (!empty($search))
		{
			if (stripos($search, 'ad_id:') === 0)
			{
				$query->where('a.ad_id = ' . (int) substr($search, 3));
			}
			else
			{
				$search = $db->Quote('%' . $db->escape($search, true) . '%');

				$query->where('( a.ad_id LIKE ' . $search .
					'  OR  a.ad_title LIKE ' . $search .
					' )'
				);
			}
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
	 * get items
	 *
	 * @return  Array
	 *
	 * @since   1.6
	 */
	public function getItems()
	{
		$items   = parent::getItems();
		$adsData = array();
		$i       = 0;

		// Sorting ads according to ad status
		if ($this->getState('filter.adstatus') == "")
		{
			return $items;
		}

		if (empty($items))
		{
			return $adsData;
		}

		foreach ($items as $item)
		{
			// Get ad status
			$ad_status = SaAdEngineHelper::getInstance()->getAdStatus($item->ad_id);

			// If ad is not expired
			if ($this->getState('filter.adstatus') == 1 && $ad_status['status_ads'] == 1)
			{
				$adsData[$i] = new stdclass;
				$adsData[$i] = $item;
				$i++;
			}
			elseif ($this->getState('filter.adstatus') == 0 && $ad_status['status_ads'] == 0)
			{
				// If ad is expired
				$adsData[$i] = new stdclass;
				$adsData[$i] = $item;
				$i++;
			}
		}

		return $adsData;
	}

	/**
	 * Method to find Ignore count of perticular ad
	 *
	 * @param   integer  $ad_id  An ad_id for perticular ad
	 *
	 * @return  integer
	 *
	 * @since  1.6
	 */
	public function getIgnorecount($ad_id)
	{
		$db    = Factory::getDbo();
		$query	= $db->getQuery(true);
		$query->select('COUNT(adid)');
		$query->from($db->quoteName('#__ad_ignore'));
		$query->where($db->quoteName('adid') . ' = ' . $ad_id);

		$db->setQuery($query);
		$ignorecount = $db->loadresult();

		return $ignorecount;
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
	public function getTable($type = 'Form', $prefix = 'SocialadsTable', $config = array())
	{
		$this->addTablePath(JPATH_COMPONENT_ADMINISTRATOR . '/tables');

		return Table::getInstance($type, $prefix, $config);
	}

	/**
	 * Returns data for line chart in adsummary view.
	 *
	 * @return   Array    Data for pie chart
	 *
	 * @since    1.6
	 */
	public function getstatsforlinechart()
	{
		$input = Factory::getApplication()->input;
		$user  = Factory::getUser();
		$post  = $input->post;
		$ad_id = (int) $input->get('adid');
		$statistics = $adsData = array();
		$i = 0;

		if ($ad_id > 0)
		{
			$to_date = date('Y-m-d');
			$from_date = date('Y-m-d', strtotime(date('Y-m-d') . ' - 30 days'));

			$db = Factory::getDbo();
			$query = $db->getQuery(true);
			$query1 = $db->getQuery(true);

			// Query to get data from stats table
			$query->select(
			'DATE(time) as date, COUNT(IF(display_type="1",1, NULL)) as click , COUNT(IF(display_type="0",1, NULL)) as impression, d.ad_id, d.camp_id'
			);
			$query->from($db->quoteName('#__ad_stats', 'as'));
			$query->join('LEFT', $db->quoteName('#__ad_data', 'd') . ' ON ' . $db->quoteName('as.ad_id') . '=' . $db->quoteName('d.ad_id'));
			$query->join('LEFT', $db->quoteName('#__ad_zone', 'z') . ' ON ' . $db->quoteName('d.ad_id') . '=' . $db->quoteName('z.id'));
			$query->where($db->quoteName('d.created_by') . '=' . $user->id);
			$query->where("DATE(time) BETWEEN DATE('" . $from_date . "') AND DATE('" . $to_date . "')");

			// Filter for zone for stats
			if ($this->getState('filter.zoneslist'))
			{
				$query->where($db->quoteName('d.ad_zone') . " = " . $this->getState('filter.zoneslist'));
			}

			// Filter for campaign for stats
			$campaignslist = $this->getState('filter.campaignslist');

			if ($campaignslist)
			{
				$query->where($db->quoteName('d.camp_id') . '=' . $campaignslist);
			}

			$query->group('DATE(time)');
			$query->order('DATE(time)');
			$db->setQuery($query);
			$stats = $db->loadObjectlist();

			// Query to get data from archive stats
			$query1->select('DATE(aas.date) as date, aas.click, aas.impression, d.ad_id, d.camp_id');
			$query1->from($db->quoteName('#__ad_archive_stats', 'aas'));
			$query1->join('LEFT', $db->quoteName('#__ad_data', 'd') . 'ON' . $db->quoteName('aas.ad_id') . '=' . $db->quoteName('d.ad_id'));
			$query1->join('LEFT', $db->quoteName('#__ad_zone', 'z') . 'ON' . $db->quoteName('d.ad_id') . '=' . $db->quoteName('z.id'));
			$query1->where($db->quoteName('d.created_by') . '=' . $user->id);

			// Filter for zone for stats
			if ($this->getState('filter.zoneslist'))
			{
				$query1->where($db->quoteName('d.ad_zone') . " = " . $this->getState('filter.zoneslist'));
			}

			// Filter for campaign for stats
			$campaignslist = $this->getState('filter.campaignslist');

			if ($campaignslist)
			{
				$query1->where($db->quoteName('d.camp_id') . '=' . $campaignslist);
			}

			$query1->where("DATE(aas.date) BETWEEN DATE('" . $from_date . "') AND DATE('" . $to_date . "')");
			$query1->group('DATE(aas.date)');
			$query1->order('DATE(aas.date)');
			$db->setQuery($query1);

			$archivestats = $db->loadObjectlist();

			$statistics = array_merge($stats, $archivestats);

			// Sorting ads according to ad status
			if ($this->getState('filter.adstatus') == "")
			{
				return $statistics;
			}
			else
			{
				foreach ($statistics as $item)
				{
					// If ad is not expired
					if ($this->getState('filter.adstatus') == 1)
					{
						if (SaAdEngineHelper::getInstance()->getAdStatus($item->ad_id))
						{
							$adsData[$i] = new stdclass;
							$adsData[$i] = $item;
							$i++;
						}
					}
					elseif ($this->getState('filter.adstatus') == 0)
					{
						// If ad is expired
						if (!SaAdEngineHelper::getInstance()->getAdStatus($item->ad_id))
						{
							$adsData[$i] = new stdclass;
							$adsData[$i] = $item;
							$i++;
						}
					}
				}
			}
		}

		return $adsData;
	}

	/**
	 * Export ads stats into a csv file
	 *
	 * @return  void
	 *
	 * @since  1.6
	 **/
	public function adCsvExport()
	{
		$db = Factory::getDBO();
		$query = $this->getListQuery();

		$db->setQuery($query);
		$results = $db->loadObjectList();
		// print_r($results); die;
		$csvData = null;
		$csvData .= "Ad_Id,Ad_Title,Ad_Type,Owner,Zone_Name,Clicks,Impressions,CTR,Ignores";
		$csvData .= "\n";
		$filename = "SA_Ads_" . date("Y-m-d_H-i", time());
		header("Content-type: application/vnd.ms-excel");
		header("Content-disposition: csv" . date("Y-m") . ".csv");
		header("Content-disposition: filename=" . $filename . ".csv");

		foreach ($results as $result)
		{
			$csvData .= '"' . $result->ad_id . '"' . ',' . '"' . trim(( $result->ad_title == '' ? Text::_('IMGAD') : $result->ad_title )) . '"' . ',';
			if ($result->ad_alternative == 1)
			{
				$csvData .= '"' . Text::_('COM_SOCIALADS_ADS_AD_TYPE_ALT_AD') . '"' . ',';
			}
			elseif ($result->ad_noexpiry == 1)
			{
				$csvData .= '"' . Text::_('COM_SOCIALADS_ADS_AD_TYPE_UNLTD_AD') . '"' . ',';
			}
			elseif ($result->ad_affiliate == 1)
			{
				$csvData .= '"' . Text::_('COM_SOCIALADS_ADS_AD_TYPE_AFFI') . '"' . ',';
			}
			else
			{
				if ($result->ad_payment_type == 0)
				{
					$csvData .= '"' . Text::_('COM_SOCIALADS_ADS_AD_TYPE_IMPRS') . '"' . ',';
				}
				elseif ($result->ad_payment_type == 1)
				{
						$csvData .= '"' . Text::_('COM_SOCIALADS_ADS_AD_TYPE_CLICKS') . '"' . ',';
				}
				else
				{
					$csvData .= '"' . Text::_('COM_SOCIALADS_ADS_AD_TYPE_PERDATE') . '"' . ',';
				}
			}

			$csvData .= '"' . Factory::getUser($result->created_by)->username . '"' . ',';
			$zone_name = $result->zone_name ? $result->zone_name : '';

			if ($zone_name)
			{
				$csvData .= '"' . $zone_name . '"' . ',';
			}

			if ($_POST['from'] || $_POST['to'])
			{
				require_once JPATH_SITE . "/components/com_socialads/helpers/common.php";

				$from       = $_POST['from'] ? $_POST['from'] : null;
				$to       = $_POST['to'] ? $_POST['to'] : null;
				$impAndCount = SaCommonHelper::getImpressionAndClicks($result->ad_id, $from, $to);
				$clicks = $impAndCount['clicks'];
				$impr = $impAndCount['imp'];
			}
			else 
			{
				$clicks = $result->clicks;
				$impr = $result->impressions;
			}

			if ($impr != 0)
			{
				$ctr = (($clicks) / ($impr)) * 100;
				$ctr = number_format($ctr, 2);
			}
			else
			{
				$ctr = number_format($clicks, 2);
			}

			$csvData .= '"' . $clicks . '"' . ',' . '"' . $impr . '"' . ',' . '"' . $ctr . '"' . ',' . '"' . $this->getIgnorecount($result->ad_id) . '"' . ',';
			$csvData .= "\n";
		}

		print $csvData;
		exit();
	}
}
