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
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Language\Text;

/**
 * Methods supporting a list of Socialads records.
 *
 * @since  1.6
 */
class SocialadsModelorders extends ListModel
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
				'id', 'a.id',
				'cdate', 'a.cdate',
				'mdate', 'a.mdate',
				'transaction_id', 'a.transaction_id',
				'subscr_id', 'a.subscr_id',
				'payee_id', 'a.payee_id',
				'amount', 'a.amount',
				'status', 'a.status',
				'extras', 'a.extras',
				'processor', 'a.processor',
				'ip_address', 'a.ip_address',
				'comment', 'a.comment',
				'ad_original_amt', 'a.ad_original_amt',
				'ad_coupon', 'a.ad_coupon',
				'ad_tax', 'a.ad_tax',
				'ad_tax_details', 'a.ad_tax_details',
				'username', 'u.username',
			);
		}

		parent::__construct($config);
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
	 * @since  1.6
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
		$query1 = $db->getQuery(true);

		// Select the required fields from the table.
		$query->select('DISTINCT(order_id)');
		$query->from($db->quoteName('#__ad_payment_info'));
		$query->where($db->quoteName('comment') . " = " .  $db->quote('AUTO_GENERATED'));
		$db->setQuery($query);
		$payment_order_ids = $db->loadAssocList();
		$payment_order_id = "";

		foreach ($payment_order_ids as $value)
		{
			$payment_order_id .= implode(" ", $value);
			$payment_order_id .= " ";
		}

		$payment_order_id = explode(" ", trim($payment_order_id));
		$payment_order_id = implode(", ", $payment_order_id);

		$query1->select(
			$this->getState(
				'list.select', 'DISTINCT a.*, u.username , u.email'
			)
		);

		$query1->from('`#__ad_orders` AS a');
		$query1->join('LEFT', $db->quoteName('#__users', 'u') . 'ON' . $db->quoteName('a.payee_id') . '=' . $db->quoteName('u.id'));
		$query1->where($db->quoteName('a.comment') . " != " . $db->quote('AUTO_GENERATED'));

		if (!empty($payment_order_id))
		{
			$query1->where($db->quoteName('a.id') . " NOT IN ($payment_order_id)");
		}

		// Filter by search in title
		$search = $this->getState('filter_search');

		$ostatus = $this->getState('filter.status');

		if (!empty($ostatus))
		{
			$query1->where($db->quoteName('a.status') . ' = ' . $db->quote($ostatus));
		}

		if (!empty($search))
		{
			if (stripos($search, 'prefix_oid:') === 0)
			{
				$query1->where($db->quoteName('a.prefix_oid') . ' = ' . (int) substr($search, 3));
			}
			else
			{
				$query1->where($db->quoteName('a.prefix_oid') . ' LIKE ' . $db->quote($search . '%'));
			}
		}

		// Add the list ordering clause.
		$orderCol = $this->state->get('list.ordering');
		$orderDirn = $this->state->get('list.direction');

		if ($orderCol && $orderDirn)
		{
			$query1->order($db->escape($orderCol . ' ' . $orderDirn));
		}

		// Filter by store.
		$filter_gateway = $this->state->get("filter.gatewaylist");

		if ($filter_gateway)
		{
			$query1->where($db->quoteName('a.processor') . " = '" . $db->escape($filter_gateway) . "'");
		}

		return $query1;
	}

	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @param   string  $ordering   An optional ordering field.
	 * @param   string  $direction  An optional direction (asc|desc).
	 *
	 * @return  void
	 *
	 * @since   1.6
	 */
	protected function populateState($ordering = null, $direction = null)
	{
		// Initialise variables.
		$app = Factory::getApplication('administrator');

		// List state information.
		$limit = $app->getUserStateFromRequest('global.list.limit', 'limit', $app->getCfg('list_limit'));
		$this->setState('list.limit', $limit);

		$search = $app->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
		$this->setState('filter_search', $search);

		$limitstart = Factory::getApplication()->input->getInt('limitstart', 0);

		if ($limit == 0)
		{
			$this->setState('list.start', 0);
		}
		else
		{
			$this->setState('list.start', $limitstart);
		}

		// Filter for gateway options.
		$gateway = $app->getUserStateFromRequest($this->context . '.filter.gatewaylist', 'filter_gatewaylist', '', 'string');
		$this->setState('filter.gatewaylist', $gateway);

		// Filter provider.
		$accepted_status = $app->getUserStateFromRequest($this->context . '.filter.status', 'filter_status', '', 'string');
		$this->setState('filter.status', $accepted_status);

		// Load the parameters.
		$params = ComponentHelper::getParams('com_socialads');
		$this->setState('params', $params);

		// List state information.
		parent::populateState('a.id', 'DESC');
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
	 * Store the staus value changed in list view of orders
	 *
	 * @return  Integer value
	 *
	 * @since  1.6
	 */
	public function store()
	{
		$data         = Factory::getApplication()->input;
		$id           = $data->post->get('id');
		$status       = $data->post->get('status');
		$validstatus  = array_keys($this->getValidOrderStatus($status));
		$returnResult = 1;

		// $socialadshelper = new SaCommonHelper;
		$paymentHelper = new SocialadsPaymentHelper;

		if (!in_array($status, $validstatus))
		{
			return false;
		}

		if ($status == 'RF')
		{
			$query = $this->_db->getQuery(true);
			$query->update($this->_db->quoteName('#__ad_orders'))
				->set($this->_db->quoteName('status') . ' = ' . $this->_db->quote('RF'))
				->where($this->_db->quoteName('id') . ' = ' . $id);
			$this->_db->setQuery($query);

			if (!$this->_db->execute())
			{
				return 2;
			}

			$socialadshelper = SaCommonHelper::new_pay_mail($id);

			// $socialadshelper->new_pay_mail($id);

			$returnResult = 3;
		}
		elseif ($status == 'E')
		{
			$query = $this->_db->getQuery(true);
			$query->update($this->_db->quoteName('#__ad_orders'))
				->set($this->_db->quoteName('status') . ' = ' . $this->_db->quote('E'))
				->where($this->_db->quoteName('id') . ' = ' . $id);
			$this->_db->setQuery($query);

			if (!$this->_db->execute())
			{
				return 2;
			}

			// C$socialadshelper->new_pay_mail($id);

			$returnResult = 3;
		}
		elseif ($status == 'C')
		{
			$query = $this->_db->getQuery(true);
			$query->select('*')
				->from($this->_db->quoteName('#__ad_orders'))
				->where($this->_db->quoteName('id') . ' = ' . $id);

			$this->_db->setQuery($query);
			$result = $this->_db->loadObject();
			$query = $this->_db->getQuery(true);
			$query->update($this->_db->quoteName('#__ad_orders'))
				->set($this->_db->quoteName('status') . ' = ' . $this->_db->quote('C'))
				->where($this->_db->quoteName('id') . ' = ' . $id);
			$this->_db->setQuery($query);

			if (!$this->_db->execute())
			{
				return 2;
			}

			// Entry for transaction table
			$query = $this->_db->getQuery(true);
			$query->select($this->_db->quoteName('ad_id'))
				->from($this->_db->quoteName('#__ad_payment_info'))
				->where($this->_db->quoteName('id') . ' = ' . $id);

			$this->_db->setQuery($query);
			$ad = $this->_db->loadresult();

			JLoader::import('payment', JPATH_SITE . '/components/com_socialads/models');
			$socialadsModelpayment = new socialadsModelpayment;

			if (empty($ad))
			{
				// Add wallet
				$comment = 'COM_SOCIALADS_WALLET_ADS_PAYMENT';
				$transc = $socialadsModelpayment->add_transc($result->original_amount, $id, $comment);
				$sendmail = $socialadsModelpayment->SendOrderMAil($id, $data->post->get('processor'), $payPerAd = 0);
			}
			else
			{
				// Pay per ad
				$sendmail = $socialadsModelpayment->SendOrderMAil($id, $data->post->get('processor'), $payPerAd = 1);
			}

			require_once JPATH_SITE . '/components/com_socialads/helper.php';
		}
		else
		{
			$query = $this->_db->getQuery(true);
			$query->update($this->_db->quoteName('#__ad_orders'))
				->set($this->_db->quoteName('status') . ' = ' . $this->_db->quote('P'))
				->where($this->_db->quoteName('id') . ' = ' . $id);
			$this->_db->setQuery($query);

			if (!$this->_db->execute())
			{
				return 2;
			}
		}

		// Trigger on after order complete trigger for SocialAds
		PluginHelper::importPlugin('socialads');
		Factory::getApplication()->triggerEvent('onAfterSocialAdOrderStatusChange', array($data, $id));

		return $returnResult;
	}

	/**
	 * returns the valid status array for changed status
	 *
	 * @param   string  $status  Order status.
	 *
	 * @return  array
	 *
	 * @since  3.2.2
	 */
	public function getValidOrderStatus($status)
	{
		$allStatuses = array(
			"P"  => Text::_('COM_SOCIALADS_AD_PENDING'),
			"C"  => Text::_('COM_SOCIALADS_AD_CONFIRM'),
			"RF" => Text::_('COM_SOCIALADS_AD_REFUND'),
			"E"  => Text::_('COM_SOCIALADS_AD_CANCEL')
		);

		$unsetOrderStatus = array(
				"P"   => array (0 => "RF"),
				"C"   => array (0 => "P",1 => "E"),
				"E"   => array (0 => "P",  1 => "C", 2 => "RF"),
				"RF"  => array (0 => "P",  1 => "C", 2 => "E")
		);

		foreach ($unsetOrderStatus as $key => $orderStatuses)
		{
			if ($key === $status)
			{
				foreach ($orderStatuses as $orderStatus)
				{
					// Unset the indexes
					unset($allStatuses[$orderStatus]);
				}
			}
		}

		return $allStatuses;
	}
}
