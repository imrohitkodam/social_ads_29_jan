<?php
/**
 * @version    SVN:<SVN_ID>
 * @package    SocialAds
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2015 TechJoomla. All rights reserved
 * @license    GNU General Public License version 2, or later
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;

/**
 * Methods supporting a list of Socialads records.
 *
 * @since  1.6
 */
class SocialadsModelForms extends ListModel
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
			'ordering', 'a.ordering',
			'state', 'a.state', 'created_by', 'a.created_by',
			'ad_id', 'a.ad_id',
			'created_by', 'a.created_by',
			'ad_url1', 'a.ad_url1',
			'ad_url2', 'a.ad_url2',
			'ad_title', 'a.ad_title',
			'ad_body', 'a.ad_body',
			'ad_image', 'a.ad_image',
			'ad_startdate', 'a.ad_startdate',
			'ad_enddate', 'a.ad_enddate',
			'ad_noexpiry', 'a.ad_noexpiry',
			'ad_payment_type', 'a.ad_payment_type',
			'ad_credits', 'a.ad_credits',
			'ad_credits_balance', 'a.ad_credits_balance',
			'ad_created_date', 'a.ad_created_date',
			'ad_modified_date', 'a.ad_modified_date',
			'ad_published', 'a.ad_published',
			'ad_approved', 'a.ad_approved',
			'ad_alternative', 'a.ad_alternative',
			'ad_guest', 'a.ad_guest',
			'ad_affiliate', 'a.ad_affiliate',
			'ad_zone', 'a.ad_zone',
			'layout', 'a.layout',
			'camp_id', 'a.camp_id',
			'bid_value', 'a.bid_value',
			'campaign', 'c.campaign',
			'clicks', 'a.clicks',
			'impressions', 'a.impressions',
			'status', 'ao.status',
			'type', 'a.ad_payment_type',
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
		$app = Factory::getApplication('administrator');

		// Load the filter state.
		$search = $app->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
		$this->setState('filter.search', $search);
		$published = $app->getUserStateFromRequest($this->context . '.filter.state', 'filter_published', '', 'string');
		$this->setState('filter.state', $published);

		$campaign = $app->getUserStateFromRequest($this->context . '.filter.campaignslist', 'filter_campaignslist', '', 'string');
		$this->setState('filter.campaignslist', $campaign);

		$zone = $app->getUserStateFromRequest($this->context . '.filter.zonelist', 'filter_zonelist', '', 'string');
		$this->setState('filter.zonelist', $zone);

		// Filter provider.
		$accepted_status = $app->getUserStateFromRequest($this->context . '.filter.ad_approved', 'filter_ad_approved', '', 'string');
		$this->setState('filter.ad_approved', $accepted_status);

		$from = $app->getUserStateFromRequest($this->context . '.filter.from', 'from');
		$this->setState('filter.from', $from);
		$to = $app->getUserStateFromRequest($this->context . '.filter.to', 'to');
		$this->setState('filter.to', $to);

		// Load the parameters.
		$params = ComponentHelper::getParams('com_socialads');
		$this->setState('params', $params);

		// List state information.
		parent::populateState('a.ad_id', 'desc');
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
	 * @return  JDatabaseQuery
	 *
	 * @since  1.6
	 */
	protected function getListQuery()
	{
		// Create a new query object.
		$db = $this->getDbo();
		$query = $db->getQuery(true);

		// Select the required fields from the table.
		$query->select(
			$this->getState(
				'list.select', 'DISTINCT a.*'
							)
				);
		$query->from($db->quoteName('#__ad_data', 'a'));

		// Join over the users for the checked out user
		$query->select($db->quoteName('a.ad_url2'));
		$query->select($db->quoteName('a.ad_url2'));
		$query->select($db->quoteName('c.campaign'));
		$query->select($db->quoteName('uc.name', 'editor'));

		// Join over the user field 'created_by'
		$query->select($db->quoteName('created_by.name', 'created_by'));
		$query->select($db->quoteName('z.zone_name'));
		$query->select($db->quoteName('ao.status'));
		$query->join('LEFT', $db->quoteName('#__ad_campaign', 'c') . 'ON' . $db->quoteName('a.camp_id') . '=' . $db->quoteName('c.id'));
		$query->join('LEFT', $db->quoteName('#__users', 'uc') . 'ON' . $db->quoteName('uc.id') . '=' . $db->quoteName('a.checked_out'));
		$query->join('LEFT', $db->quoteName('#__users', 'created_by') . 'ON' . $db->quoteName('created_by.id') . '=' . $db->quoteName('a.created_by'));
		$query->join('LEFT', $db->quoteName('#__ad_zone', 'z') . 'ON' . $db->quoteName('z.id') . '=' . $db->quoteName('a.ad_zone'));
		$query->join('LEFT', $db->quoteName('#__ad_payment_info', 'p') . 'ON' . $db->quoteName('p.ad_id') . '=' . $db->quoteName('a.ad_id'));
		$query->join('LEFT', $db->quoteName('#__ad_orders', 'ao') . 'ON' . $db->quoteName('p.order_id') . '=' . $db->quoteName('ao.id'));
		$query->join('LEFT', $db->quoteName('#__ad_stats', 'as') . 'ON' . $db->quoteName('as.ad_id') . '=' . $db->quoteName('a.ad_id')); // IDL
		$query->group($db->quoteName('a.ad_id'));
		$db->setQuery($query);

		// Filter by published state
		$published = $this->getState('filter.state');

		if (is_numeric($published))
		{
			$query->where($db->quoteName('a.state'). ' = ' . (int) $published);
		}
		elseif ($published === '')
		{
			$query->where('(a.state IN (0, 1))');
		}

		// Filter by campaign
		$campaign = $this->getState('filter.campaignslist');

		if ($campaign)
		{
			$query->where($db->quoteName('a.camp_id'). ' = ' . (int) $campaign);
		}

		// Filter for zone
		$zone = $this->getState('filter.zonelist');

		if ($zone)
		{
			$query->where($db->quoteName('a.ad_zone'). ' = ' . (int) $zone);
		}

		$ostatus = $this->getState('filter.ad_approved');

		if ($ostatus != '' && $ostatus != '-1')
		{
			$query->where($db->quoteName('a.ad_approved'). ' = ' . (int) $ostatus);
		}

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
		}

		// Filter by search in title
		$search = $this->getState('filter.search');

		if (!empty($search))
		{
			if (stripos($search, 'ad_id:') === 0)
			{
				$query->where($db->quoteName('a.ad_id'). ' = ' . (int) substr($search, 3));
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
		$orderCol = $this->state->get('list.ordering');
		$orderDirn = $this->state->get('list.direction');

		if ($orderCol && $orderDirn)
		{
			$query->order($db->escape($orderCol . ' ' . $orderDirn));
		}

		return $query;
	}

	/**
	 * Method to get approved ads.
	 *
	 * @return  integer
	 *
	 * @since  1.6
	 */
	public function getApproveAds()
	{
		$db = Factory::getDbo();
		global $mainframe, $option;
		$mainframe = Factory::getApplication();
		$input = Factory::getApplication()->input;
		$option = $input->get('option', '', 'STRING');
		$where = '';

		if (empty($this->_data))
		{
			$query = $this->_buildQuery();
			$query = $query . ' ' . $where;

			if ($filter_order)
			{
				$qry = "SHOW COLUMNS FROM #__ad_data";
				$db->setQuery($qry);
				$exists = $db->loadobjectlist();

				foreach ($exists as $key => $value)
				{
					$allowed_fields[] = 'a.' . $value->Field;
				}

				if (in_array($filter_order, $allowed_fields))
				{
					$query .= "ORDER BY $filter_order $filter_order_Dir";
				}
			}
			else
			{
				$query .= "ORDER BY a.`ad_id` DESC";
			}

			$this->_data = $this->_getList($query, $this->getState('limitstart'), $this->getState('limit'));
		}

		return $this->_data;
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
	 * To get the published zone name
	 *
	 * @return  Integer
	 *
	 * @since  1.6
	 */
	public function getZonelist()
	{
		$db = Factory::getDbo();
		$query = $db->getQuery(true);
		$query->select($db->quoteName(array('id', 'zone_name')));
		$query->from($db->quoteName('#__ad_zone'));
		$query->where($db->quoteName('state'). ' = ' . 1);
		$db->setQuery($query);
		$zone_list = $db->loadObjectList();

		return $zone_list;
	}

	/**
	 * Store the staus value changed in list view of ads
	 *
	 * @return  Boolean value
	 *
	 * @since  1.6
	 */
	public function store()
	{
		$app    = Factory::getApplication();
		$input  = $app->input->post;
		$id     = $input->get('ad_id', '', 'INT');
		$status = $input->get('status', '', 'STRING');

		$query = $this->_db->getQuery(true);
		$query->update($this->_db->quoteName('#__ad_data'))
			->set($this->_db->quoteName('ad_approved') . ' = ' . $status)
			->where($this->_db->quoteName('ad_id') . ' = ' . $id);
		$this->_db->setQuery($query);

		if ($this->_db->execute())
		{
			$query = $this->_db->getQuery(true);
			$query->select($this->_db->quoteName('a.created_by'))
				->select($this->_db->quoteName('a.ad_title'))
				->select($this->_db->quoteName('u.name'))
				->select($this->_db->quoteName('u.email'))
				->from($this->_db->quoteName('#__ad_data', 'a'))
				->join('INNER', $this->_db->quoteName('#__users', 'u') . ' ON ' . $this->_db->quoteName('a.created_by') . ' = ' . $this->_db->quoteName('u.id'))
				->where($this->_db->quoteName('a.ad_id') . ' = ' . $id);

			$this->_db->setQuery($query);
			$result	= $this->_db->loadObject();

			// When ad is approve by site owner
			if ($status == 1)
			{
				$body    = Text::_('COM_SOCIALADS_ADS_APPROVED_AD_MAIL');
				$subject = Text::_('COM_SOCIALADS_APPROVEDAD');
			}
			// When ad is rejected by site owner
			elseif ($status == 2)
			{
				$body    = Text::_('COM_SOCIALADS_ADS_REJECTED_MAIL');
				$subject = Text::_('COM_SOCIALADS_ADS_REJECTAD');
				$body    = str_replace('[COM_SOCIALADS_ADS_REASON]', $input->get('reason', '', 'STRING'), $body);
			}
			// When ad is set as pending by site owner
			else
			{
				return true;
			}

			$body        = str_replace('[NAME]', $result->name, $body);
			$ad_title    = ($result->ad_title != '') ? '<b>"' . $result->ad_title . '"</b>' : Text::sprintf("COM_SOCIALADS_ADID", $id);
			$body        = str_replace('[ADTITLE]', $ad_title, $body);
			$body        = str_replace('[SITE]', Uri::root(), $body);
			$body        = str_replace('[SITENAME]', $app->getCfg('sitename'), $body);
			$from        = $app->get('mailfrom');
			$fromname    = $app->getCfg('fromname');
			$recipient[] = $result->email;
			$body        = nl2br($body);
			$mode        = 1;
			$cc          = null;
			$bcc         = null;
			$bcc         = null;
			$attachment  = null;
			$replyto     = null;
			$replytoname = null;

			try
			{
				Factory::getMailer()->sendMail($from, $fromname, $recipient, $subject, $body, $mode, $cc, $bcc, $attachment, $replyto, $replytoname);
			}
			catch (Exception $e)
			{
				$app->enqueueMessage($e->getMessage(), 'error');
			}
		}

		return true;
	}

	/**
	 * Store changed zone on list view of ads.
	 *
	 * @return  Integer
	 *
	 * @since  1.6
	 */
	public function updatezone()
	{
		$data      = Factory::getApplication()->input->post;
		$id        = $data->get('ad_id', '', 'INT');
		$zone      = $data->get('zone', '', 'INT');
		$query = $this->_db->getQuery(true);
		$query->select($this->_db->quoteName('layout'));
		$query->from($this->_db->quoteName('#__ad_zone'));
		$query->where($this->_db->quoteName('id') . ' = ' . $zone);
		$this->_db->setQuery($query);
		$layout  = $this->_db->loadresult();
		$layout1 = explode('|', $layout);

		$query = $this->_db->getQuery(true);
		$query->update($this->_db->quoteName('#__ad_data'))
			->set($this->_db->quoteName('ad_zone') . ' = ' . $zone)
			->set($this->_db->quoteName('layout') . ' = ' . $this->_db->quoteName($layout1['0']))
			->where($this->_db->quoteName('ad_id') . ' = ' . $id);

		$this->_db->setQuery($query);
		$this->_db->execute();

		return true;
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
		$db = Factory::getDbo();
		$query = $this->_db->getQuery(true);
		$query->select('COUNT(adid)');
		$query->from($this->_db->quoteName('#__ad_ignore'));
		$query->where($this->_db->quoteName('adid') . ' = ' . $ad_id);
		$db->setQuery($query);
		$ignorecount = $db->loadresult();

		return $ignorecount;
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
		$db = Factory::getDbo();
		$query = $db->getQuery(true);
		$query = $this->getListQuery();
		$db->setQuery($query);
		$results = $db->loadObjectList();
		$csvData = null;
		$csvData .= Text::_('COM_SOCIAL_ADS_AD_ID') . "," .
			Text::_('COM_SOCIAL_ADS_AD_TITLE') . "," .
			Text::_('COM_SOCIAL_ADS_AD_TYPE') . "," .
			Text::_('COM_SOCIAL_ADS_OWNER') . "," .
			Text::_('COM_SOCIAL_ADS_ZONE_NAME') . "," .
			Text::_('COM_SOCIAL_ADS_CLICKS') . "," .
			Text::_('COM_SOCIAL_ADS_IMPRESSIONS') . "," .
			Text::_('COM_SOCIAL_ADS_CTR') . "," .
			Text::_('COM_SOCIAL_ADS_IGNORES') . "\n";
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
			$zone_name = $result->zone_name;

			if ($zone_name)
			{
				$csvData .= '"' . $zone_name . '"' . ',';
			}

			$clicks = $result->clicks;

			$impr = $result->impressions;

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
