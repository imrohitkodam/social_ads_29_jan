<?php
/**
 * @version    SVN:<SVN_ID>
 * @package    Com_Socialads
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2015 TechJoomla. All rights reserved
 * @license    GNU General Public License version 2, or later
 */
defined('_JEXEC') or die;

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
class SocialadsModelAdorders extends ListModel
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
				'id', 'o.id',
				'd.ad_id',
				'd.ad_title',
				'p.ad_credits_qty',
				'status', 'o.status',
				'processor', 'o.processor',
				'ad_payment_type', 'd.ad_payment_type',
				'u.username',
				'amount', 'o.amount',
				'cdate', 'o.cdate'
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

		// Load the parameters.
		$params = ComponentHelper::getParams('com_socialads');
		$this->setState('params', $params);

		// Filter for gateway options.
		$gateway = $app->getUserStateFromRequest($this->context . '.filter.gatewaylist', 'filter_gatewaylist', '', 'string');
		$this->setState('filter.gatewaylist', $gateway);

		// Filter provider.
		$accepted_status = $app->getUserStateFromRequest($this->context . '.filter.status', 'filter_status', '', 'string');
		$this->setState('filter.status', $accepted_status);

		// List state information.
		parent::populateState('o.id', 'desc');
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
	 * @return  string   A store id.
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

		// Select the required fields from the table.
		$query->select(
				$this->getState(
						'list.select', 'DISTINCT p.*'
				)
		);
		$query->from($db->quoteName('#__ad_payment_info', 'p'));
		$query->select("o.id, o.amount, o.status, o.processor, o.comment, o.coupon, o.payee_id, o.prefix_oid");
		$query->join('LEFT', $db->quoteName('#__ad_orders', 'o') . 'ON' . $db->quoteName('o.id') . '=' . $db->quoteName('p.order_id'));

		$query->select("d.ad_id, d.ad_title, d.ad_payment_type, d.ad_startdate, d.ad_enddate");
		$query->join('LEFT', $db->quoteName('#__ad_data', 'd') . 'ON' . $db->quoteName('d.ad_id') . '=' . $db->quoteName('p.ad_id'));
		$query->select("u.username,u.id as user_id, u.email");
		$query->join('LEFT', $db->quoteName('#__users', 'u') . 'ON' . $db->quoteName('u.id') . '=' . $db->quoteName('o.payee_id'));
		$query->where($db->quoteName('o.comment') . "!='AUTO_GENERATED'");

		// Filter by search in title
		$search = $this->getState('filter.search');
		$ostatus = $this->getState('filter.status');

		if (!empty($ostatus))
		{
			$query->where($db->quotename('o.status') . '=' . $db->quote($ostatus));
		}

		if (!empty($search))
		{
			if (stripos($search, 'prefix_oid:') === 0)
			{
				$query->where('o.prefix_oid = ' . (int) substr($search, 3));
			}
			else
			{
				$search = $db->Quote('%' . $db->escape($search, true) . '%');

				$query->where('( o.prefix_oid LIKE ' . $search .
					'  OR  p.ad_id LIKE ' . $search .
					'  OR  d.ad_title LIKE ' . $search .
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

		$filter_gateway = $this->state->get("filter.gatewaylist");

		if ($filter_gateway)
		{
			$query->where("o.processor = '" . $db->escape($filter_gateway) . "'");
		}

		return $query;
	}

	/**
	 * To reduce ad credits if the order is cancled
	 *
	 * @param   string  $id  Order id.
	 *
	 * @return  items
	 *
	 * @since  1.6
	 */
	public function reduceAdCredits($id)
	{
		$db = Factory::getDBO();
		$query = $db->getQuery(true);
		$query->select($db->quoteName(array('p.ad_id', 'p.ad_credits_qty')));
		$query->from($db->quoteName('#__ad_payment_info', 'p'));
		$query->join('LEFT', $db->quoteName('#__ad_orders', 'o') . 'ON' . $db->quoteName('o.id') . '=' . $db->quoteName('p.order_id'));
		$query->where($db->qn('o.id') . ' = ' . $id);
		$query->where($db->qn('o.status') . ' = ' . $db->q('C'));
		$this->_db->setQuery($query);
		$result = $this->_db->loadObject();

		if ($result->ad_credits_qty > 0)
		{
			$query = $db->getQuery(true);
			$query->update($db->quoteName('#__ad_data'))
				->set($db->quoteName('ad_credits') . ' = ' . $db->quoteName('ad_credits') . ' - ' . $result->ad_credits_qty)
				->set($db->quoteName('ad_credits_balance') . ' = ' . $db->quoteName('ad_credits_balance') . ' - ' . $result->ad_credits_qty)
				->where($db->quoteName('ad_id') . ' = ' . $result->ad_id);

			$db->setQuery($query);
			$sql = $db->execute();
		}

		return;
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
	 * @return  Boolean|Integer value
	 *
	 * @since  1.6
	 */
	public function store()
	{
		$data          = Factory::getApplication()->input->post;
		$id            = $data->get('id', '', 'INT');
		$status        = $data->get('status', '', 'STRING');
		$validstatus   = array_keys($this->getValidOrderStatus($status));
		$paymentHelper = new SocialadsPaymentHelper;
		$returnResult  = 1;

		if (!in_array($status, $validstatus))
		{
			return false;
		}

		if ($status == 'RF')
		{
			$this->reduceAdCredits($id);

			$query = $this->_db->getQuery(true);
			$query->update($this->_db->quoteName('#__ad_orders'))
				->set($this->_db->quoteName('status') . ' = ' . $this->_db->q('RF'))
				->where($this->_db->quoteName('id') . ' = ' . $id);

			$this->_db->setQuery($query);

			if (!$this->_db->execute())
			{
				return 2;
			}

			SaCommonHelper::new_pay_mail($id);

			$returnResult = 3;
		}
		elseif ($status == 'E')
		{
			$this->reduceAdCredits($id);
			$query = $this->_db->getQuery(true);
			$query->update($this->_db->quoteName('#__ad_orders'))
				->set($this->_db->quoteName('status') . ' = ' . $this->_db->q('E'))
				->where($this->_db->quoteName('id') . ' = ' . $id);

			$this->_db->setQuery($query);

			if (!$this->_db->execute())
			{
				return 2;
			}

			// $socialadshelper->new_pay_mail($id);

			$returnResult = 3;
		}

		elseif ($status == 'P')
		{
			$this->reduceAdCredits($id);

			$query = $this->_db->getQuery(true);
			$query->update($this->_db->quoteName('#__ad_orders'))
				->set($this->_db->quoteName('status') . ' = ' . $this->_db->q('P'))
				->where($this->_db->quoteName('id') . ' = ' . $id);

			$this->_db->setQuery($query);

			if (!$this->_db->execute())
			{
				return 2;
			}
		}
		elseif ($status == 'C')
		{
			$db = Factory::getDBO();
			$query = $db->getQuery(true);
			$query->select(array('p.ad_id','p.ad_credits_qty', 'o.*'));
			$query->select($db->quoteName('p.id', 'payment_info_id'));
			$query->from($db->quoteName('#__ad_payment_info', 'p'));
			$query->join('LEFT', $db->quoteName('#__ad_orders', 'o') . ' ON (' . $db->quoteName('o.id') . ' = ' . $db->quoteName('p.order_id') . ')');
			$query->where($db->quoteName('o.id') . '=' . (int) $id);
			$db->setQuery($query);
			$result = $db->loadObject();

			$query = $db->getQuery(true);
			$fields = array(
			$db->quoteName('status') . ' = ' . $db->quote('C'),
			$db->quoteName('payment_info_id') . ' = ' . $db->quote($result->payment_info_id));

			$conditions = array($db->quoteName('id') . ' = ' . $db->quote($id));
			$query->update($db->quoteName('#__ad_orders'))->set($fields)->where($conditions);

			$db->setQuery($query);

			if (!$db->execute())
			{
				return 2;
			}

			// Entry for transaction table
			$query = $db->getQuery(true);
			$query->select($db->quoteName('id'));
			$query->from($db->quoteName('#__ad_orders'));
			$query->where($db->quoteName('id') . ' = ' . $id);

			$this->_db->setQuery($query);
			$ad = $this->_db->loadresult();

			$adid = $result->ad_id;
			$qryad = $db->getQuery(true);
			$qryad->select($db->quoteName('ad_payment_type'));
			$qryad->from($db->quoteName('#__ad_data'));
			$qryad->where($db->quoteName('ad_id') . ' = ' . $adid);

			$this->_db->setQuery($qryad);
			$ad_payment_type = $this->_db->loadResult();

			if ($ad_payment_type != 2)
			{
				$query = $this->_db->getQuery(true);
				$query->update($this->_db->quoteName('#__ad_data'))
					->set($this->_db->quoteName('ad_credits') . ' = ' . $this->_db->quoteName('ad_credits') . ' + ' . $result->ad_credits_qty)
					->set($this->_db->quoteName('ad_credits_balance') . ' = ' . $this->_db->quoteName('ad_credits_balance') . ' + ' . $result->ad_credits_qty)
					->where($this->_db->quoteName('ad_id') . ' = ' . $result->ad_id);

				$this->_db->setQuery($query);
				$this->_db->execute();
			}

			// Added for date type ads

			if (empty($subscriptiondata[0]->subscr_id) && ($ad_payment_type == 2))
			{
				$paymentHelper->adddays($adid, $result->ad_credits_qty);
			}

			$query = $db->getQuery(true);
			$query->select($db->quoteName('a.ad_id'))
				->select($db->quoteName('a.pay_initial_fee'))
				->from($db->quoteName('#__ad_data', 'a'))
				->where($db->quoteName('a.ad_id') . ' = ' . $adid);

			$db->setQuery($query);
			$adDetail	 = $db->loadObject();
			$sa_params = ComponentHelper::getParams('com_socialads');
			$initialFee = $sa_params->get('initial_fee_for_ad_placement');
			$needToPayInitialFee = $sa_params->get('need_to_pay_initial_fee');

			if ($needToPayInitialFee && $initialFee && $adDetail->pay_initial_fee == 0)
			{
				$query = $db->getQuery(true);
				$query->update($db->quoteName('#__ad_data'))
					->set($db->quoteName('pay_initial_fee') . ' = ' . 1)
					->set($db->quoteName('pay_initial_fee_amout') . ' = ' . $initialFee)
					->where($db->quoteName('ad_id') . ' = ' . $adid);
				$db->setQuery($query);

				$db->execute();
			}

			JLoader::import('payment', JPATH_SITE . '/components/com_socialads/models');
			$socialadsModelpayment = new socialadsModelpayment;

			try
			{
				if (empty($ad))
				{
					// Add wallet
					$comment = 'COM_SOCIALADS_WALLET_ADS_PAYMENT';
					$transc = $socialadsModelpayment->add_transc($result->original_amount, $id, $comment);
					$socialadsModelpayment->SendOrderMAil($id, $data->get('processor', '', 'STRING'));
				}
				else
				{
					// Pay per ad
					$socialadsModelpayment->SendOrderMAil($id, $data->get('processor', '', 'STRING'));
				}
			}
			catch (Exception $e)
			{
				$app    = Factory::getApplication();
				$app->enqueueMessage($e->getMessage(), 'error');
			}

			// Trigger on after order complete trigger for jticketing
			PluginHelper::importPlugin('system');
			$result = Factory::getApplication()->triggerEvent('onAfterSaOrderStatusChange', array($id, $status, $result));
		}
		else
		{
			$query = $this->_db->getQuery(true);
			$query->update($this->_db->quoteName('#__ad_orders'))
				->set($this->_db->quoteName('status') . ' = ' . $this->_db->q('P'))
				->where($this->_db->quoteName('id') . ' = ' . $id);
			$this->_db->setQuery($query);

			if (!$this->_db->execute())
			{
				return 2;
			}
		}

		// Trigger on after order complete trigger for SocialAds
		PluginHelper::importPlugin('socialads');
		$result = Factory::getApplication()->triggerEvent('onAfterSocialAdOrderStatusChange', array($data, $id));

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
