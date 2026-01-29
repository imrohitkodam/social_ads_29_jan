<?php
/**
 * @version    SVN:<SVN_ID>
 * @package    Com_Socialads
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2015 TechJoomla. All rights reserved
 * @license    GNU General Public License version 2, or later
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\MVC\Model\FormModel;
use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Registry\Registry;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Date\Date;


/**
 * payment model class.
 *
 * @since  1.6
 */
class SocialadsModelPayment extends FormModel
{
	/**
	 * This function to get a form data
	 *
	 * @param   integer  $data      database values
	 * @param   boolean  $loadData  load data
	 *
	 * @return  array
	 *
	 * @since  1.6
	 **/
	public function getForm($data = array(), $loadData = true)
	{
		// Get the form.
		$form = $this->loadForm('com_socialads', 'payment', array('control' => 'jform', 'load_data' => $loadData));

		if (empty($form))
		{
			return false;
		}

		return $form;
	}

	/**
	 * This function to get order details
	 *
	 * @param   integer  $tid  order id of a order
	 *
	 * @return  array
	 *
	 * @since  1.6
	 **/
	public function getdetails($tid)
	{
		$paymentHelper = new SocialadsPaymentHelper;
		$adData        = $paymentHelper->getOrderAndAdDetail($tid);
		$userId        = Factory::getUser()->id;
		$params        = ComponentHelper::getParams('com_socialads');
		$adMode        = $params->get('payment_mode');
		$orderdata     = array();

		// If admin created an ad userId should be on behalf of whom admin is creating an ad
		if ($adMode == 'pay_per_ad_mode')
		{
			$userId = Factory::getUser()->authorise('core.admin') ? $adData['created_by'] : Factory::getUser()->id;
		}

		// Get a db connection.
		$db = Factory::getDbo();
		$tid = (int) $tid;

		if ($tid <= 0)
		{
			return $orderdata;
		}

		// Create a new query object.
		$query = $db->getQuery(true);
		$query -> select($db->quoteName(array('o.payee_id', 'o.processor', 'o.amount', 'o.original_amount', 'o.coupon', 'o.prefix_oid')))
			->from($db->quoteName('#__ad_orders', 'o'))
			->where($db->quoteName('o.id') . ' =' . $tid);

		// Get the order details against payee_id if userId is present
		if ($userId)
		{
			$query->where($db->quoteName('payee_id') . " = " . $userId);
		}

		$this->_db->setQuery($query);
		$details = $this->_db->loadObjectlist();

		if (!empty($details))
		{
			$orderdata = array(
				'payment_type'    => '',
				'order_id'        => $tid,
				'pg_plugin'       => $details[0]->processor,
				'user'            => $details[0]->payee_id,
				'amount'          => $details[0]->amount,
				'original_amount' => $details[0]->original_amount,
				'prefix_oid'      => $details[0]->prefix_oid,
				'coupon'          => $details[0]->coupon,
				'success_message' => ''
			);
		}

		return $orderdata;
	}

	/**
	 * This function to get payment plugin
	 *
	 * @param   string   $pg_plugin  payment gateway name
	 * @param   integer  $order_id   order id of a order
	 * @param   integer  $payPerAd   Default variable for payment mode
	 *
	 * @return  array
	 *
	 * @since  1.6
	 **/
	public function getPaymentVars($pg_plugin, $order_id, $payPerAd = "pay_per_ad_mode")
	{
		global $mainframe;
		$mainframe = Factory::getApplication();
		$orderdata = $this->getdetails((int) $order_id);

		$vars           = new stdclass;
		$vars->order_id = $orderdata['order_id'];

		if (!empty($orderdata['payment_type']))
		{
			$vars->payment_type = $orderdata['payment_type'];
		}
		else
		{
			$vars->payment_type = "";
		}

		$order_user           = Factory::getUser($orderdata['user']);
		$vars->user_id        = $orderdata['user'];
		$vars->user_name      = $order_user->name;
		$vars->user_firstname = $order_user->name;
		$vars->user_email     = $order_user->email;
		$params               = ComponentHelper::getParams('com_socialads');
		$payPerAd             = $params->get('payment_mode');

		if ($payPerAd == 'wallet_mode')
		{
			$vars->item_name = Text::_('COM_SOCIALADS_ADWALLET_PAYMENT_DESC');
		}
		else
		{
			$vars->item_name = Text::_('COM_SOCIALADS_PAY_PER_AD_PAYMENT_DESC');
		}

		$msg_fail = Text::_('COM_SOCIALAD_PAYMENT_ERROR_IN_SAVING_DETAILS');

		$calledFrom = "&adminCall=0";

		if ($mainframe->isClient("administrator"))
		{
			$calledFrom = "&adminCall=1";
			$vars->return = Route::_(Uri::root() . "administrator/index.php?option=com_socialads&view=forms" . $calledFrom, false);
		}
		else
		{
			$defaultMsg = '';

			if ($pg_plugin == 'paypal')
			{
				$defaultMsg = "&saDefMsg=1";
			}

			$vars->return = Route::_(Uri::root() . "index.php?option=com_socialads&view=ads&layout=default" . $defaultMsg, false);

			if ($payPerAd == 'wallet_mode')
			{
				$wallet_itemid = SaCommonHelper::getSocialadsItemid('wallet');
				$vars->return = Route::_(
					Uri::root() . "index.php?option=com_socialads&view=wallet&layout=default&Itemid="
					. $wallet_itemid . $defaultMsg, false
				);
			}
		}

		// CANCEL URL
		if ($mainframe->isClient("administrator"))
		{
			$vars->submiturl     = Uri::root() . "administrator/index.php?option=com_socialads&view=adorders";
			$vars->cancel_return = Uri::root() . "administrator/index.php?option=com_socialads&view=adorders";
		}
		else
		{
			$vars->submiturl     = Route::_(Uri::root() . "index.php?option=com_socialads&view=ads");
			$vars->cancel_return = Route::_(Uri::root() . "index.php?option=com_socialads&view=ads&layout=default&processor=" . $pg_plugin, $msg_fail);
		}

		// Notify_url URL
		if ($mainframe->isClient("administrator") && $pg_plugin != 'paypal')
		{
			// @TODO support payper ad mode in processpayment function
			// If (empty($payPerAd))
			{
				// Call dummy controller url
				$vars->url = $vars->notify_url = Route::_(
					Uri::root() . "administrator/index.php?option=com_socialads&task=pay.processpayment&pg_nm=" .
					$pg_plugin . "&pg_action=onTP_Processpayment&order_id=" .
					$orderdata['prefix_oid'] . "&original_amount=" . $orderdata['original_amount'] .
					"&mode=" . $payPerAd . $calledFrom, false
				);
			}
		}
		else
		{
			/* @TODO support payper ad mode in processpayment function
			 If (empty($payPerAd)) */

			$vars->url = $vars->notify_url = Route::_(
				Uri::root() . "index.php?option=com_socialads&task=payment.processpayment&pg_nm=" .
				$pg_plugin . "&pg_action=onTP_Processpayment&order_id=" . $orderdata['prefix_oid'] .
				"&original_amount=" . $orderdata['original_amount'] . "&mode=" .
				$payPerAd . $calledFrom, false
				);
		}

		$vars->currency_code = $params->get('currency');
		$vars->amount = $orderdata['amount'];
		$vars->client = "socialads";
		$vars->success_message = $orderdata['success_message'];

		/**
		if ($vars->payment_type=='recurring')
		{
			$vars->notify_url= $vars->url=$vars->url."&payment_type=recurring";
			$vars->recurring_startdate=$orderdata['recurring_startdate'];
			$vars->recurring_payment_interval_unit="days";
			$vars->recurring_payment_interval_totaloccurances=$orderdata['recurring_payment_interval_totaloccurances'];
			$vars->recurring_payment_interval_length=$orderdata['recurring_payment_interval_length'];
		}
		**/

		$vars->userInfo = $this->userInfo((int) $order_id, $orderdata['user']);

		return $vars;
	}

	/**
	 * This function to get users info
	 *
	 * @param   integer  $order_id  order id of a order
	 * @param   integer  $userid    user id of a perticular user
	 *
	 * @return  array
	 *
	 * @since  1.6
	 **/
	public function userInfo($order_id, $userid = '')
	{
		if (empty($userid))
		{
			$user = Factory::getUser();
			$userid = $user->id;
		}

		$db = Factory::getDBO();
		$query	= $db->getQuery(true);
		$query->select($db->quoteName(array('user_id', 'user_email', 'firstname', 'lastname', 'country_code', 
						'state_code', 'address', 'city', 'phone', 'zipcode')));
		$query->from($db->quoteName('#__ad_users'));
		$query->where($db->quoteName('user_id') . ' = ' . $userid);
		$query->order($db->quoteName('id') . ' DESC');

		$db->setQuery($query);
		$billDetails = $db->loadAssoc();

		if (!empty($billDetails))
		{
			// Make address in 2 lines
			$billDetails['add_line1'] = $billDetails['address'];
			$billDetails['add_line2'] = '';

			// Remove new line
			$remove_character         = array("\n", "\r\n", "\r");
			$billDetails['add_line1'] = str_replace($remove_character, ' ', !empty($billDetails['add_line1']) ? $billDetails['add_line1'] : '');
			$billDetails['add_line2'] = str_replace($remove_character, ' ', !empty($billDetails['add_line2']) ? $billDetails['add_line2'] : '');
		}

		return $billDetails;
	}

	/**
	 * This function to confirm payment
	 *
	 * @param   string   $pg_plugin  payment gateway name
	 * @param   integer  $oid        order id of a order
	 *
	 * @return  void
	 *
	 * @since  1.6
	 **/
	public function confirmpayment($pg_plugin, $oid)
	{
		$post = Factory::getApplication()->input->post->getArray();
		$vars = $this->getPaymentVars($pg_plugin, $oid);

		if (!empty($post) && !empty($vars))
		{
			PluginHelper::importPlugin('payment', $pg_plugin);
			$result = Factory::getApplication()->triggerEvent('onTP_ProcessSubmit', array($post,$vars));
		}
		else
		{
			Factory::getApplication()->enqueueMessage(Text::_('SOME_ERROR_OCCURRED'), 'error');
		}
	}

	/**
	 * This function to get payment plugin data
	 *
	 * @return  array
	 *
	 * @since  1.6
	 **/
	public function getAPIpluginData()
	{
		$condtion = array(0 => 'payment');
		$condtionatype = join(',', $condtion);

		$query	= $this->_db->getQuery(true);
		$query->select($this->_db->quoteName('extension_id', 'id'));
		$query->select($this->_db->quoteName('enabled', 'published'));
		$query->select($this->_db->quoteName(array('name', 'element')));
		$query->from($this->_db->quoteName('#__extensions'));
		$query->where($this->_db->quoteName('enabled') . ' = ' . 1);
		$query->where($this->_db->quoteName('folder') . ' IN (' . implode(',', $this->_db->quote($condtion)) . ')');

		$this->_db->setQuery($query);
		$paymentPluginData = $this->_db->loadobjectList();

		foreach ($paymentPluginData as $payParam)
		{
					// Code to get the plugin param name
					$plugin = PluginHelper::getPlugin('payment', $payParam->element);
					$params = new Registry($plugin->params);
					$pluginName = $params->get('plugin_name', $payParam->name, 'STRING');
					$payParam->name = $pluginName;
		}

		return $paymentPluginData;
	}

	/**
	 * This function to create a order
	 *
	 * @param   array  $orderdata  order data
	 *
	 * @return  integer
	 *
	 * @since  1.6
	 **/
	public function createorder($orderdata = '')
	{
		header('Set-Cookie: ' . session_name() . '=' . $_COOKIE[session_name()] . '; SameSite=None; Secure; HttpOnly');

		$user                    = Factory::getUser();
		$db                      = Factory::getDBO();
		$date                    = Factory::getDate('now');
		$paymentdata             = new stdClass;
		$paymentdata->id         = '';
		$paymentdata->cdate      = $date->toSQL();

		// Get use's IP Address
		JLoader::import('components.com_socialads.helpers.tjgeoloc', JPATH_SITE);
		$paymentdata->ip_address = TJGeoLocationHelper::getUserIP();

		// $paymentdata->ad_id = $orderdata['adid'];

		$paymentdata->processor = $orderdata['pg_plugin'];

		// $paymentdata->ad_credits_qty = $orderdata['credits'];
		$paymentdata->amount          = $orderdata['amount'];
		$paymentdata->original_amount = $orderdata['original_amount'];

		if (isset($orderdata['tax']))
		{
			$paymentdata->tax = $orderdata['tax'];
			$paymentdata->amount += $paymentdata->tax;
		}

		if (empty($orderdata['status']) or $orderdata['status'] == 'p')
		{
			$paymentdata->status = 'P';
		}
		else
		{
			$paymentdata->status = $orderdata['status'];
		}

		$paymentdata->coupon = $orderdata['coupon'];

		if (empty($orderdata['payee_id']))
		{
			$paymentdata->payee_id = $user->id;
		}
		else
		{
			$paymentdata->payee_id = $orderdata['payee_id'];
		}

		if (isset($orderdata['comment']))
		{
			$paymentdata->comment = $orderdata['comment'];
		}

		$sticketid = $this->checkduplicaterecord($paymentdata);

		if (!$sticketid)
		{
			if (!$db->insertObject('#__ad_orders', $paymentdata, 'id'))
			{
				echo $db->stderr();

				return false;
			}

			$orderid = $db->insertID();

			$sa_params = ComponentHelper::getParams('com_socialads');
			$order_prefix = (string) $sa_params->get('order_prefix');

			// String length should not be more than 5
			$order_prefix = substr($order_prefix, 0, 5);

			// Take separator set by admin
			$separator = (string) $sa_params->get('separator');

			$res = new stdclass;

			$res->prefix_oid = $order_prefix . $separator;

			// Check if we have to add random number to order id
			$use_random_orderid    = (int) $sa_params->get('random_orderid');
			$socialadPaymentHelper = new SocialadsPaymentHelper;

			if ($use_random_orderid)
			{
				$random_numer = $socialadPaymentHelper->_random(5);
				$res->prefix_oid .= $random_numer . $separator;

				// This length shud be such that it matches the column lenth of primary key
				// It is used to add pading
				$len = (23 - 5 - 2 - 5);

				// Order_id_column_field_length - prefix_length - no_of_underscores - length_of_random number
			}
			else
			{
				// This length shud be such that it matches the column lenth of primary key
				// It is used to add pading
				$len = (23 - 5 - 2);
			}

			$maxlen = 23 - strlen($res->prefix_oid) - strlen($orderid);

			$padding_count = (int) $sa_params->get('padding_count');

			// Use padding length set by admin only if it is les than allowed(calculate) length

			if ($padding_count > $maxlen)
			{
				$padding_count = $maxlen;
			}

			if (strlen((string) $orderid) <= $len)
			{
				$append = '';

				for ($z = 0;$z < $padding_count;$z++)
				{
					$append .= '0';
				}

				$append = $append . $orderid;
			}

			$res->id = $orderid;
			$res->prefix_oid = $res->prefix_oid . $append;

			if (!$db->updateObject('#__ad_orders', $res, 'id'))
			{
				// Return false;
			}
		}
		else
		{
			$query = $db->getQuery(true);
			$fields = array(
				$db->quoteName('amount') . ' = ' . $db->quote($paymentdata->amount),
				$db->quoteName('processor') . ' = ' . $db->quote($paymentdata->processor)
			);

			$conditions = array($db->quoteName('id') . ' = ' . (int) $sticketid);
			$query->update($db->quoteName('#__ad_orders'))->set($fields)->where($conditions);
			$db->setQuery($query);
			$db->execute();

			$orderid = $sticketid;
		}

		// Send mail for status pending

		$session = Factory::getSession();

		if ($session->has('order_id'))
		{
			$session->clear('order_id');
		}

		$session->set('order_id', $orderid);

		return $orderid;
	}

	/**
	 * This function to check duplicate record
	 *
	 * @param   array  $res1  order related data to check already exist
	 *
	 * @return  integer
	 *
	 * @since  1.6
	 **/
	public function checkduplicaterecord($res1)
	{
		// Clone object for php
		$res2 = clone $res1;
		$db = Factory::getDBO();
		$res2->original_amount = number_format((float) $res2->original_amount, 2, '.', '');
		$res2->cdate = date('Y-m-d', strtotime($res2->cdate));

		$query = $this->_db->getQuery(true);
		$query->select($this->_db->quoteName('id'));
		$query->from($this->_db->quoteName('#__ad_orders'));
		$query->where($db->quoteName('status') . " = " . $db->q('P'));
		$query->where($db->quoteName('payee_id') . " = " . $res2->payee_id);
		$query->where($db->quoteName('original_amount') . " = " . $res2->original_amount);
		$query->where("DATE_FORMAT(cdate,'%Y-%m-%d') = " . $db->quote($res2->cdate));

		$db->setQuery($query);

		return $id = $db->loadresult();
	}

	/**
	 * This function to process a payment
	 *
	 * @param   array    $post          payment related data
	 * @param   string   $pg_nm         payment gateway name
	 * @param   string   $pg_action     payment gateway name
	 * @param   integer  $order_id      order id of a order
	 * @param   integer  $org_amt       original amount
	 * @param   integer  $payment_mode  pricing mode, Wallet mode or Pay per Ad mode
	 *
	 * @return  array
	 *
	 * @since  1.6
	 **/
	public function processpayment($post, $pg_nm, $pg_action, $order_id, $org_amt, $payment_mode = 'pay_per_ad_mode')
	{
		$return_resp    = array();
		$db             = Factory::getDBO();
		$input          = Factory::getApplication()->input;
		$isadmin        = $input->get('adminCall', 0, 'INTEGER');
		$order_id       = (int) $order_id;
		$component_link = 'index.php?option=com_socialads';

		if ($payment_mode == 'pay_per_ad_mode')
		{
			$ads_itemid = SaCommonHelper::getSocialadsItemid('ads');
			$returnUrl  = $component_link . '&view=wallet&Itemid=' . $ads_itemid;
		}
		else
		{
			$wallet_itemid = SaCommonHelper::getSocialadsItemid('wallet');
			$returnUrl     = $component_link . '&view=wallet&Itemid=' . $wallet_itemid;
		}

		// Authorise Post Data
		if ($post['plugin_payment_method'] == 'onsite')
		{
			$plugin_payment_method = $post['plugin_payment_method'];
		}

		// Get VARS
		$vars = $this->getPaymentVars($pg_nm, $order_id);

		// END vars
		PluginHelper::importPlugin('payment', $pg_nm);
		$data = Factory::getApplication()->triggerEvent($pg_action, array($post, $vars));
		$data = $data[0];

		// Get order id
		if (empty($order_id))
		{
			$order_id = $data['order_id'];
		}

		if ($order_id <= 0)
		{
			$return_resp['return'] = Route::_(Uri::root() . $returnUrl, false);

			return $return_resp;
		}

		$return_resp['return'] = $data['return'];
		$processed             = 0;
		$res                   = $this->storelog($pg_nm, $data);
		$processed             = $this->dataProcessed($data['transaction_id'], $order_id);

		$query = $db->getQuery(true);

		$query->select($db->quoteName('amount'));
		$query->from($db->quoteName('#__ad_orders'));
		$query->where($db->quoteName('id') . ' = ' . $order_id);

		$this->_db->setQuery($query);
		$order_amount = $this->_db->loadResult();

		$return_resp['status'] = '0';

		if ($data['status'] == 'C' && $order_amount == $data['total_paid_amt'])
		{
			if ($processed == 0)
			{
				$this->saveOrder($data, $order_id, $pg_nm, $payment_mode);

				if ($payment_mode == 'pay_per_ad_mode')
				{
					$link = empty($isadmin) ? $component_link . '&view=ads&itemid=' . $ads_itemid : 'administrator/index.php?option=com_socialads&view=forms';
				}
				else
				{
					$link = empty($isadmin) ? $component_link .
					'&view=wallet&itemid=' . $wallet_itemid : 'administrator/index.php?option=com_socialads&view=wallets';
				}

				// @TODO - manoj -needs to chk what urls to pass
				// $return_resp['return'] = JUri::root() . substr(JRoute::_($link, false), strlen(JUri::base(true)) + 1);
				$return_resp['return'] = Route::_(Uri::root() . $link, false);
			}

			$return_resp['msg'] = $data['success'];
			$return_resp['status'] = '1';
		}
		elseif (!empty($data['status']))
		{
			if ($plugin_payment_method and  $data['status'] == 'P')
			{
				if ($payment_mode == 'pay_per_ad_mode')
				{
					$link = empty($isadmin) ? $component_link . '&view=ads&itemid=' . $ads_itemid : 'administrator/index.php?option=com_socialads&view=forms';
				}
				else
				{
					$link = empty($isadmin) ? $component_link . '&view=wallet&itemid=' .
					$wallet_itemid : 'administrator/index.php?option=com_socialads&view=wallets';
				}

				// @TODO - manoj -needs to chk what urls to pass
				// $return_resp['return'] = JUri::root() . substr(JRoute::_($link, false), strlen(JUri::base(true)) + 1);
				$return_resp['return'] = Route::_(Uri::root() . $link, false);
			}

			if ($order_amount != $data['total_paid_amt'])
			{
				$data['status'] = 'E';
				$this->cancelOrder($data, $order_id, $pg_nm);
			}
			elseif ($data['status'] != 'C')
			{
				$data['status'] = 'P';
				$this->cancelOrder($data, $order_id, $pg_nm);
			}
			elseif ($data['status'] != 'C' and $processed == 0)
			{
				$data['status'] = 'P';
				$this->updateOrderStatus($data, $order_id, $pg_nm);
			}

			$return_resp['status'] = '0';

			if (!empty($data['error']))
			{
				$return_resp['msg'] = $data['error']['code'] . $data['error']['desc'];
			}

			if ($payment_mode == 'pay_per_ad_mode')
			{
				$link = ($isadmin == 0) ? $component_link . '&view=ads&itemid=' . $ads_itemid : 'administrator/index.php?option=com_socialads&view=forms';
			}
			elseif ($payment_mode == 'wallet_mode')
			{
				$link = ($isadmin == 0) ? $component_link . '&view=wallet&itemid=' . $wallet_itemid : 'administrator/index.php?option=com_socialads&view=wallets';
			}

			$return_resp['return'] = Route::_(Uri::root() . $link, false);
		}

		// $this->SendOrderMAil($order_id,$pg_nm);
		// As we have not going to send any mail till order confirm
		return $return_resp;
	}

	/**
	 * This function to save order. @TODO support payper ad mode in this function.
	 *
	 * @param   array    $data          order related data
	 * @param   integer  $orderid       order id of a order
	 * @param   string   $pg_nm         payment gateway name
	 * @param   integer  $payment_mode  pricing mode, Wallet mode or Pay per Ad mode
	 *
	 * @return  boolean
	 *
	 * @since  1.6
	 **/
	public function saveOrder($data, $orderid, $pg_nm, $payment_mode = 'pay_per_ad_mode')
	{
		$db = Factory::getDBO();
		$paymentdata = new stdClass;
		$paymentdata->id = $orderid;
		$paymentdata->transaction_id = $data['transaction_id'];
		$socialadPaymentHelper = new SocialadsPaymentHelper;

		if (!empty($pg_nm))
		{
			$paymentdata->processor = $pg_nm;
		}

		if ($data['status'] == 'C')
		{
			$paymentdata->status = 'C';

			/* //@TODO Recurring code
			if (!empty($data['payment_type']) && $data['payment_type'] == 'recurring')
			{
				$paymentdata->subscr_id = $data['subscr_id'];

				if (empty($data['payment_number']))
				{
					$paymentdata->status = 'P';
				}
			}
			*/

			if ($payment_mode == 'pay_per_ad_mode')
			{
				// ^ changed in v3.1 + Manoj
				// WHERE id =" . $orderid; => id to order_id
				$query	= $db->getQuery(true);
				$query->select($db->quoteName(array('subscr_id', 'ad_credits_qty', 'ad_id')));
				$query->from($db->quoteName('#__ad_payment_info'));
				$query->where($db->quoteName('order_id') . ' = ' . $orderid);

				$db->setQuery($query);
				$ad_payment_info = $db->loadObject();

				if (!$ad_payment_info->ad_credits_qty)
				{
					$ad_payment_info->ad_credits_qty = 0;
				}

				// Added for date type ads
				$adid = $ad_payment_info->ad_id;
				$query	= $db->getQuery(true);
				$query->select($db->quoteName('ad_payment_type'));
				$query->from($db->quoteName('#__ad_data'));
				$query->where($db->quoteName('ad_id') . ' = ' . $adid);

				$db->setQuery($query);
				$ad_payment_type = $db->loadResult();

				if (($ad_payment_type == 2))
				{
					$socialadPaymentHelper->adddays($adid, $ad_payment_info->ad_credits_qty);
				}
				else
				{
					$query = $db->getQuery(true);
					$query->update($db->quoteName('#__ad_data'))
						->set($db->quoteName('ad_credits') . ' = ' . $db->quoteName('ad_credits') . ' + ' . $ad_payment_info->ad_credits_qty)
						->set($db->quoteName('ad_credits_balance') . ' = ' . $db->quoteName('ad_credits_balance') . ' + ' . $ad_payment_info->ad_credits_qty)
						->where($db->quoteName('ad_id') . ' = ' . $ad_payment_info->ad_id);
						
					$db->setQuery($query);
					$db->execute();
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
						->set($db->quoteName('pay_initial_fee_amout') . ' = ' . $adDetail->initialFee)
						->where($db->quoteName('ad_id') . ' = ' . $result->ad_id);
					$db->setQuery($query);

					$db->execute();
				}
			}
			else
			{
				$query	= $db->getQuery(true);
				$query->select($db->quoteName('original_amount'));
				$query->from($db->quoteName('#__ad_orders'));
				$query->where($db->quoteName('id') . ' = ' . $orderid);

				$db->setQuery($query);
				$tol_amt = $db->loadresult();
				$comment = "COM_SOCIALADS_WALLET_ADS_PAYMENT";
				$transc = $this->add_transc($tol_amt, $orderid, $comment);
			}
		}

		$paymentdata->extras = $data['raw_data'];

		if (!$db->updateObject('#__ad_orders', $paymentdata, 'id'))
		{
			echo $db->stderr();

			return false;
		}

		if ($paymentdata->status == 'C')
		{
			$socialadsModelpayment = new socialadsModelpayment;
			$sendmail = $socialadsModelpayment->SendOrderMAil($orderid, $pg_nm);

			// Added plugin trigger tobe executed after order payment complete
			PluginHelper::importPlugin('system');
			Factory::getApplication()->triggerEvent('onAfterSaOrderStatusChange', array($orderid, $data['status'], $data));

			PluginHelper::importPlugin('socialads');
			Factory::getApplication()->triggerEvent('onAfterSocialAdOrderStatusChange', array($data, $orderid));

			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * This function to cancel order
	 *
	 * @param   array    $data      order related data
	 * @param   integer  $order_id  order id of a order
	 * @param   string   $pg_nm     payment gateway name
	 *
	 * @return  boolean
	 *
	 * @since  1.6
	 **/
	public function cancelOrder($data, $order_id, $pg_nm)
	{
		$order_id = (int) $order_id;

		if ($order_id < 0)
		{
			return false;
		}

		$db = Factory::getDbo();
		$query = $db->getQuery(true);

		// Fields to update.
		$fields = array(
			$db->quoteName('status') . ' = ' . $db->quote($data['status']),
		);

		if (!empty($data['raw_data']) && is_string($data['raw_data']))
		{
			array_push($fields, $db->quoteName('extras') . ' = ' . $db->quote($data['raw_data']));
		}

		// Conditions for which records should be updated.
		$conditions = array(
			$db->quoteName('id') . ' = ' . $db->quote($order_id)
		);
		$query->update($db->quoteName('#__ad_orders'))->set($fields)->where($conditions);

		$db->setQuery($query);

		if (!$db->execute())
		{
			echo $db->stderr();

			return false;
		}

		return false;
	}

	/**
	 * This function to update order status
	 *
	 * @param   array    $data      order related data
	 * @param   integer  $order_id  order id of a order
	 * @param   string   $pg_nm     payment gateway name
	 *
	 * @return  boolean
	 *
	 * @since  1.6
	 **/
	public function updateOrderStatus($data, $order_id, $pg_nm)
	{
		$input = Factory::getApplication()->input;
		$db = Factory::getDbo();
		$query = $db->getQuery(true);

		// Fields to update.
		$fields = array(
			$db->quoteName('status') . ' = ' . $db->quote($data['status']),
			$db->quoteName('extras') . ' = ' . $db->quote($data['raw_data'])
		);

		// Conditions for which records should be updated.
		$conditions = array(
			$db->quoteName('id') . ' = ' . $input->get($order_id, 0, 'INT')
		);

		$query->update($db->quoteName('#__ad_orders'))->set($fields)->where($conditions);
		$db->setQuery($query);
		$result = $db->execute();

		if (!$result)
		{
			echo $db->stderr();

			return false;
		}
	}

	/**
	 * This function to check data is avilable for that order
	 *
	 * @param   integer  $transaction_id  transaction id of order
	 * @param   integer  $order_id        order id of a order
	 *
	 * @return  integer
	 *
	 * @since  1.6
	 **/
	public function dataProcessed($transaction_id, $order_id)
	{
		$order_id = (int) $order_id;
		$where = '';
		$db = Factory::getDBO();

		if ($order_id < 0)
		{
			return 0;
		}

		$query	= $db->getQuery(true);
		$query->select($db->quoteName('id'));
		$query->from($db->quoteName('#__ad_orders'));
		$query->where($db->quoteName('id') . ' = ' . $order_id);
		$query->where($db->quoteName('status') . ' = ' . $db->q('C'));

		$db->setQuery($query);
		$paymentdata = $db->loadResult();

		if (!empty($paymentdata))
		{
			return 1;
		}

		return 0;
	}

	/**
	 * This function to store payment transactions
	 *
	 * @param   string  $pg_plugin  payment gateway name
	 * @param   string  $data       payment related data
	 *
	 * @return  void
	 *
	 * @since  1.6
	 **/
	public function storelog($pg_plugin, $data)
	{
		$data1 = array();
		$data1['raw_data'] = $data['raw_data'];
		$data1['JT_CLIENT'] = "com_socialads";
		PluginHelper::importPlugin('payment', $pg_plugin);
		$data = Factory::getApplication()->triggerEvent('onTP_Storelog', array($data1));
	}

	/**
	 * This function to send order email to advertiser
	 *
	 * @param   integer  $order_id  order id of perticular process
	 * @param   string   $pg_nm     payment gateway name
	 * @param   string   $payPerAd  payment mode pay per ad or ad wallet
	 *
	 * @return  void
	 *
	 * @since  1.6
	 **/
	public function SendOrderMAil($order_id, $pg_nm, $payPerAd = "wallet_mode")
	{
		// Require when we call from backend
		require_once JPATH_SITE . "/components/com_socialads/helpers/payment.php";
		$socialadPaymentHelper = new SocialadsPaymentHelper;

		$socialadPaymentHelper->getInvoiceDetail($order_id, $pg_nm, $payPerAd);
	}

	/**
	 * This function for payment transcation
	 *
	 * @param   integer  $org_amt   original amount
	 * @param   integer  $order_id  order id of perticular process
	 * @param   integer  $comment   comment given while processing payment
	 *
	 * @return  array
	 *
	 * @since  1.6
	 **/
	public function add_transc($org_amt, $order_id, $comment)
	{
		$db = Factory::getDBO();
		$query	= $db->getQuery(true);
		$query->select($db->quoteName('payee_id'));
		$query->from($db->quoteName('#__ad_orders'));
		$query->where($db->quoteName('id') . ' = ' . $order_id);

		$db->setQuery($query);
		$userid = $db->loadresult();

		$date = microtime(true);
		$date1 = date('Y-m-d');
		$query = "SELECT balance FROM #__ad_wallet_transc WHERE time = (SELECT MAX(time)  FROM #__ad_wallet_transc WHERE user_id="
		. $userid . ") AND user_id= " . $userid;
		$db->setQuery($query);
		$bal = $db->loadresult();
		$balance = $bal + $org_amt;
		$amount_due = new stdClass;
		$amount_due->id = '';
		$amount_due->time = $date;
		$amount_due->user_id = $userid;
		$amount_due->spent = '0';
		$amount_due->earn = $org_amt;
		$amount_due->balance = $balance;
		$amount_due->type = 'O';
		$amount_due->type_id = $order_id;
		$amount_due->comment = $comment;

		if (!$db->insertObject('#__ad_wallet_transc', $amount_due, 'id'))
		{
			echo $db->stderr();

			return false;
		}

		$returnID = $db->insertID();

		// Query to renew campaign after adding money in a wallet. Campaign state 2 is for pause campaign because money is exhausted.
		$query1 = $db->getQuery(true);
		$fields = array($db->quoteName('state') . ' = 1');
		$conditions = array(
				$db->quoteName('state') . ' = 2',
				$db->quoteName('created_by') . ' = ' . $userid
			);
		$query1->update($db->quoteName('#__ad_campaign'))->set($fields)->where($conditions);
		$db->setQuery($query1);
		$result = $db->execute();

		return $returnID;
	}

	/**
	 * This function get HTML for plugin process
	 *
	 * @param   string   $pg_plugin  payment gateway plugin name
	 * @param   integer  $order_id   order id of perticular process
	 * @param   integer  $payPerAd   payment mode pay per ad or ad wallet
	 *
	 * @return  html code
	 *
	 * @since  1.6
	 **/
	public function getHTML($pg_plugin, $order_id, $payPerAd = "pay_per_ad_mode")
	{
		$vars = $this->getPaymentVars($pg_plugin, $order_id, $payPerAd);
		$pg_plugin = trim($pg_plugin);
		PluginHelper::importPlugin('payment', $pg_plugin);
		$html = Factory::getApplication()->triggerEvent('onTP_GetHTML', array($vars));

		return $html;
	}

	/**
	 * This function update order gateway on change of gateway
	 *
	 * @param   string   $selectedGateway  Gateway selected to do payment
	 * @param   integer  $order_id         order id
	 *
	 * @return  integer
	 *
	 * @since  1.6
	 **/
	public function updateOrderGateway($selectedGateway, $order_id)
	{
		$db             = Factory::getDBO();
		$row            = new stdClass;
		$sa_params      = ComponentHelper::getParams('com_socialads');
		$row->id        = $order_id;
		$row->processor = '';

		if (in_array($selectedGateway, $sa_params->get('gateways')))
		{
			$row->processor = $selectedGateway;
		}

		if (!$this->_db->updateObject('#__ad_orders', $row, 'id'))
		{
			echo $this->_db->stderr();

			return 0;
		}

		return 1;
	}

	/**
	 * Processor Free Order
	 *
	 * @params  void
	 *
	 * @return  redirect backend or fronted view
	 */
	public function processFreeOrder()
	{
		$mainframe = Factory::getApplication();
		$jinput    = Factory::getApplication()->input;
		$order_id  = $jinput->get('order_id', '', 'STRING');

		require_once JPATH_SITE . "/components/com_socialads/helpers/common.php";

		$adDetail = $this->syncOrderDetail($order_id);

		// If order amount is 0 due to coupon
		if ($adDetail->amount == 0  && !empty($adDetail->coupon))
		{
			$db  = Factory::getDBO();
			$row = new stdClass;
			$row->status = 'C';
			$row->id = $order_id;

			if (!$db->updateObject('#__ad_orders', $row, 'id'))
			{
				echo $this->_db->stderr();
			}

			$data                 = array();
			$data['status']       = 'C';
			$data['payment_type'] = '';
			$data['raw_data']     = '';
			$pg_nm                = Text::_("COM_SOCIALADS_ADORDERS_VIA_COUPON");

			$this->saveOrder($data, $order_id, $pg_nm);
		}

		$response['msg'] = Text::_('COM_SOCIALADS_DETAILS_SAVE');

		if ($mainframe->isClient("administrator"))
		{
			$link = 'index.php?option=com_socialads&view=forms';
		}
		else
		{
			$Itemid = SaCommonHelper::getSocialadsItemid('ads');
			$link   = Uri::base() . substr(Route::_('index.php?option=com_socialads&view=ads&Itemid=' . $Itemid, false), strlen(Uri::base(true)) + 1);
		}

		$mainframe->enqueueMessage($response['msg'], 'success');
		$mainframe->redirect($link);
	}

	/**
	 * This function deduct tax amount from discounted amount and store it in orders final amount
	 *
	 * @param   int  $order_id  Order table primary key
	 *
	 * @return  Object
	 *
	 * @since 3.1
	 */
	public function syncOrderDetail($order_id)
	{
		$db  = Factory::getDBO();
		$val = 0;

		// Require when we call from backend
		require_once JPATH_SITE . "/components/com_socialads/helpers/payment.php";
		$socialadPaymentHelper = new SocialadsPaymentHelper;

		$query = $db->getQuery(true);

		$query->select($db->quoteName(array('a.original_amount', 'a.coupon', 'a.tax', 'a.tax_details')));
		$query->from($db->quoteName('#__ad_orders', 'a'));
		$query->where($db->quoteName('a.id') .' = ' . $order_id);
		$query->where($db->quoteName('a.status') .' = ' . $db->q('C'));

		$db->setQuery($query);
		$orderData = $db->loadAssoc();

		if (!empty($orderData) &&  !empty($orderData['coupon']))
		{
			$adcop = $socialadPaymentHelper->getCoupon($orderData['coupon']);

			if (!empty($adcop))
			{
				// Discount rate
				if ($adcop[0]->val_type == 1)
				{
					$val = ($adcop[0]->value / 100) * $orderData['original_amount'];
				}
				else
				{
					$val = $adcop[0]->value;
				}
			}
			else
			{
				$val = 0;
			}
		}

		$discountedPrice = $orderData['original_amount'] - $val;

		// @TODO:need to check plugim type..
		PluginHelper::importPlugin('adstax');

		// Call the plugin and get the result
		$taxresults = Factory::getApplication()->triggerEvent('onAfterSocialAdAddTax', array($discountedPrice));

		$appliedTax = 0;

		if (!empty($taxresults) )
		{
			foreach ($taxresults as $tax)
			{
				if (!empty($tax) )
				{
						$appliedTax += $tax[1];
				}
			}
		}

		$amountAfterTax = $discountedPrice + $appliedTax;

		if ($amountAfterTax <= 0)
		{
			$amountAfterTax = 0;
		}

		$row              = new stdClass;
		$row->id          = $order_id;
		$row->tax         = $appliedTax;
		$row->amount      = $amountAfterTax;
		$row->coupon      = $val ? $orderData['coupon'] : '';
		$row->tax_details = json_encode($taxresults);

		if (!$db->updateObject('#__ad_orders', $row, 'id'))
		{
			echo $this->_db->stderr();
		}

		return $row;
	}

	/**
	 * This function checking coupon validations
	 *
	 * @param   String  $couponCode  Coupon code
	 *
	 * @return  boolean  true or false
	 *
	 * @since 3.1.15
	 */
	public function isValidCoupon($couponCode)
	{
		if (empty($couponCode))
		{
			return false;
		}

		// Check is coupon is exist and coupon value is valid
		Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_socialads/tables');
		$socialadsTableCoupon = Table::getInstance('coupon', 'SocialadsTable', array());
		$socialadsTableCoupon->load(array('code' => $couponCode));

		// Get current date
		$currentTime = new Date('now');
		$timezone =	Factory::getUser()->getTimezone();
		$date = new Date($currentTime);
		$date->setTimezone($timezone);
		$newdate = $date->format(Text::_('DATE_FORMAT_FILTER_DATETIME'));

		if (empty($socialadsTableCoupon->id))
		{
			return false;
		}

		if ($socialadsTableCoupon->state == 0)
		{
			return 1;
		}


		if($socialadsTableCoupon->exp_date)
		{
			if ($socialadsTableCoupon->exp_date < $newdate && $socialadsTableCoupon->exp_date != '0000-00-00 00:00:00')
			{
				return 2;
			}
		}

		if($socialadsTableCoupon->from_date)
		{
			if ($socialadsTableCoupon->from_date > $newdate && $socialadsTableCoupon->from_date != '0000-00-00 00:00:00')
			{
				return 6;
			}
		}

		$db   = Factory::getDbo();
		$user = Factory::getUser();

		if ($socialadsTableCoupon->max_use > 0 || $socialadsTableCoupon->max_per_user > 0)
		{
			$query = $db->getQuery(true);
			$query->select('COUNT(ado.coupon) as usedCoupon');
			$query->from($db->qn('#__ad_orders', 'ado'));
			$query->where($db->qn('ado.coupon') . ' = ' . $db->quote($couponCode));
			$query->where($db->qn('ado.status') . ' IN (' . $db->quote('C') . ',' . $db->quote('P') . ')');

			if ($socialadsTableCoupon->max_per_user > 0)
			{
				$query->where($db->qn('ado.payee_id') . ' = ' . (int) $user->id);
			}

			$db->setQuery($query);

			// It return used coupon count.
			$result = $db->loadObject();

			// Validate max_use of coupon
			if ($socialadsTableCoupon->max_use > 0 && $result->usedCoupon >= $socialadsTableCoupon->max_use)
			{
				return 3;
			}

			// Validate max_per_user of coupon
			if ($socialadsTableCoupon->max_per_user > 0 && $result->usedCoupon >= $socialadsTableCoupon->max_per_user)
			{
				return 4;
			}
		}

		return 5;
	}

	/**
	 * Method to check coupon exists and get it
	 *
	 * @param   String  $couponCode  Coupon code
	 *
	 * @return  Mixed  Object on success false otherwise
	 *
	 * @since 3.2.2
	 */
	public function isCouponExists($couponCode)
	{
		if (empty($couponCode))
		{
			return false;
		}

		// Check is coupon is exist and coupon value is valid
		Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_socialads/tables');
		$socialadsTableCoupon = Table::getInstance('coupon', 'SocialadsTable', array());
		$socialadsTableCoupon->load(array('code' => $couponCode));

		if (!empty($socialadsTableCoupon->id))
		{
			if ($socialadsTableCoupon->code == $couponCode)
			{
				return $socialadsTableCoupon;
			}

		}

		return false;
	}
}
