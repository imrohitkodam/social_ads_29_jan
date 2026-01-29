<?php
/**
 * @version    SVN:<SVN_ID>
 * @package    Com_Socialads
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2015 TechJoomla. All rights reserved
 * @license    GNU General Public License version 2, or later
 */

// No direct access
defined('_JEXEC') or die(';)');

use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Session\Session;

require_once JPATH_SITE . '/components/com_socialads/helper.php';
include_once JPATH_SITE . '/components/com_socialads/controller.php';

/**
 * Payment list controller class.
 *
 * @since  1.6
 */
class SocialadsControllerPayment extends BaseController
{
	/**
	 * Method to get gateway html.
	 *
	 * @return  void
	 *
	 * @since   3.0
	 **/
	public function getPaymentGatewayHtml()
	{
		$db     = Factory::getDBO();
		$jinput = Factory::getApplication()->input;

		$model           = $this->getModel('payment');
		$selectedGateway = $jinput->get('gateway', '');
		$order_id        = $jinput->get('order_id', '');
		$payPerAd        = $jinput->get('payPerAd', 0, 'INT');
		$return          = '';

		if (!empty($selectedGateway) && !empty($order_id))
		{
			$model->updateOrderGateway($selectedGateway, $order_id);
			$payhtml = $model->getHTML($selectedGateway, $order_id, $payPerAd);
			$return  = !empty($payhtml[0])? $payhtml[0]:'';
		}

		echo $return;
		jexit();
	}

	/**
	 * Method to makePayment.
	 *
	 * @return  void
	 *
	 * @since   3.0
	 **/
	public function makePayment()
	{
		$input = Factory::getApplication()->input;

		// Used for recurring
		// $arb_flag = ($input->get('arb_flag')) ? $input->get('arb_flag') : 0;
		$SocialadsPaymentHelper = new SocialadsPaymentHelper;
		$mod = $this->getModel('payment');

		// Amount added in amount tab
		$amount           = $input->get('amount', '', 'FLOAT');
		$amt              = $amount;
		$processor        = $input->get('processor', '', 'STRING');
		$cop_dis_opn_hide = $input->get('cop_dis_opn_hide', '', 'INT');
		$tax          = $input->get('tax', '', 'INT');

		if ($tax)
		{
		    $tax = $input->get('tax', '', 'INT');
		}
		else 
		{
		    $tax = 0;
		}

		$cop_dis_opn_hide == 1 ? $cop = $input->get('cop', '', 'STRING') : $cop = '';

		$input->set('coupon_code', $cop);

		// To check whether coupon code is exist in database, used/or not by same user and how many times it is used
		$couponGet = $SocialadsPaymentHelper->getcoupon($cop);

		if ($cop_dis_opn_hide == 1)
		{
			$adcop = $couponGet;

			if ($adcop)
			{
				if ($adcop[0]->val_type == 1)
				{
					// Discount rate
					$val = ($adcop[0]->value / 100) * $amount;
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

			$amt = round($amount - $val, 2);
		}

		if ($amt <= 0)
		{
			$amt = 0;
		}

		$user       = Factory::getUser();
		$option     = $processor;
		PluginHelper::importPlugin('socialads', $option);

		if ($amt <= 0 && $adcop)
		{
			$paymentdata                  = new stdClass;
			$paymentdata->id              = '';
			$paymentdata->ad_id           = 0;
			$paymentdata->cdate           = date('Y-m-d H:i:s');
			$paymentdata->processor       = $option;
			$paymentdata->ad_amount       = $amt;
			$paymentdata->ad_original_amt = $amount;
			$paymentdata->status          = 'C';
			$paymentdata->ad_coupon       = $cop;
			$paymentdata->payee_id        = $user->id;

			// Get user's Ip Address
			JLoader::import('components.com_socialads.helpers.tjgeoloc', JPATH_SITE);
			$paymentdata->ip_address = TJGeoLocationHelper::getUserIP();

			$sticketid = $this->checkduplicaterecord($paymentdata);

			if (!$sticketid)
			{
				if (!$db->insertObject('#__ad_payment_info', $paymentdata, 'id'))
				{
					echo $db->stderr();

					return false;
				}
			}
			else
			{
				$this->setSession_ticketid($sticketid);

				return $sticketid;
			}

			echo "<div class='coupon_discount_all'> </div>";
			jexit();
		}
		else
		{
			$payment_type = $recurring_startdate = "";
			$success_msg  = '';
			$totalamt     = $amt;

			if ($option == 'jomsocialpoints' or $option == 'alphauserpoints')
			{
				$plugin       = PluginHelper::getPlugin('payment', $option);
				$pluginParams = json_decode($plugin->params);
				$totalamt     = $amt + $tax;
				$success_msg  = Text::sprintf('TOTAL_POINTS_DEDUCTED_MESSAGE', $amt);
			}

			$orderdata = array(
				'payment_type'    => $payment_type,
				'order_id'        => '',
				'pg_plugin'       => $option,
				'user'            => $user,
				'adid'            => 0,
				'amount'          => $totalamt,
				'original_amount' => $amount,
				'tax' => $tax,
				'coupon'          => $cop,
				'success_message' => $success_msg
			);

			// Here orderid is id in payment_info table
			$orderid = $mod->createorder($orderdata);

			if (!$orderid)
			{
				echo $msg = Text::_('ERROR_SAVE');
				exit();
			}

			$orderdata['order_id'] = $orderid;
			$html = $mod->getHTML($processor, $orderid);

			if (!empty($html))
			{
				echo $html[0];
			}

			jexit();
		}
	}

	/**
	 * Function get called when user click on confirm payment
	 *
	 * @return  void
	 *
	 * @since  1.6
	 *
	 **/
	public function confirmpayment()
	{
		$model     = $this->getModel('payment');
		$session   = Factory::getSession();
		$jinput    = Factory::getApplication()->input;
		$order_id  = $session->get('order_id');
		$pg_plugin = $jinput->get('processor');
		$response  = $model->confirmpayment($pg_plugin, $order_id);
	}

	/**
	 * Function process payment
	 *
	 * @return  void
	 *
	 * @since  1.6
	 *
	 **/
	public function processpayment()
	{
		$mainframe = Factory::getApplication();
		$input     = Factory::getApplication()->input;
		$session   = Factory::getSession();

		if ($session->has('payment_submitpost'))
		{
			$post = $session->get('payment_submitpost');
			$session->clear('payment_submitpost');
		}
		else
		{
			$post = Factory::getApplication()->input->post->getArray();
		}

		$org_amt    = $input->get('original_amt', '', 'FLOAT');

		$pg_nm      = $input->get('pg_nm');
		$pg_action  = $input->get('pg_action');
		$prefix_oid = $input->get('order_id', '', 'STRING');
		$mode = $input->get('mode', "pay_per_ad_mode", 'STRING');

		$model      = $this->getModel('payment');
		$db         = Factory::getDBO();
		$query      = $db->getQuery(true);
		$query -> select($db->quoteName('o.id'))
				->from($db->quoteName('#__ad_orders', 'o'))
				->where($db->quoteName('o.prefix_oid') . 'LIKE "%' . $prefix_oid . '%"');
		$db->setQuery($query);
		$order_id = $db->loadResult();

		if ($pg_nm == 'razorpay') 
		{
			// Get the JSON payload from Razorpay
			$jinput = file_get_contents('php://input');
			$event = json_decode($jinput, true);
			$entity = $event['payload']['payment']['entity'];
			$notes = $event['payload']['payment']['entity']['notes'];

			if ($notes['client'] == 'socialads')
			{
				$order_id = $notes['order_id'];
				$org_amt = $notes['org_amt'];
				$post = $entity;
			}
		}


		if (empty($post) || empty($pg_nm) )
		{
			Factory::getApplication()->enqueueMessage(Text::_('SOME_ERROR_OCCURRED'), 'error');

			return;
		}

		$response = $model->processpayment($post, $pg_nm, $pg_action, $order_id, $org_amt, $mode);

		// $response['msg'] = trim($response['msg']);

		if (empty($response['msg']))
		{
			$response['msg'] = Text::_('COM_SOCIALADS_PAYMENT_THANK_YOU_FOR_ORDER');
		}

		$mainframe->enqueueMessage($response['msg'], 'success');
		$mainframe->redirect($response['return']);
	}

	/**
	 * Function to add payment
	 *
	 * @return  boolean|JSON
	 *
	 * @since  1.6
	 *
	 **/
	public function addCouponPayment()
	{
		$app        = Factory::getApplication();
		$input      = $app->input;
		$user       = Factory::getUser();
		$content    = false;
		$couponCode = $input->get('coupon_code', '', 'STRING');
		$value      = $input->get('value', '', 'FLOAT');

		if (!$user->id)
		{
			return false;
		}

		if (!empty($couponCode) && !empty($value) && $value > 0)
		{
			$model = $this->getModel('payment');

			$coupon = $model->isCouponExists($couponCode);

			$isValidCoupon = $model->isValidCoupon($couponCode);

			if ($coupon !== false && $coupon->value !== null && $isValidCoupon !== false)
			{
				$comment     = 'COM_SOCIALADS_WALLET_COUPON_ADDED';
				$success_msg = Text::sprintf('TOTAL_POINTS_DEDUCTED_MESSAGE', $value);

				$orderdata   = array(
					'order_id'        => '',
					'pg_plugin'       => '',
					'user'            => $user,
					'adid'            => 0,
					'amount'          => $value,
					'original_amount' => $value,
					'coupon'          => $couponCode,
					'success_message' => $success_msg,
					'status'          => 'C',
					'comment'         => $comment
				);

				$orderId = $model->createorder($orderdata);
				$transc  = $model->add_transc($value, $orderId, $comment);
				$content = json_encode($orderId);
			}
		}

		echo $content;
		jexit();
	}

	/**
	 * Function to add payment
	 *
	 * @param   String  $couponCode  Coupon code
	 *
	 * @return  boolean|JSON
	 *
	 * @since  1.6
	 *
	 **/
	public function getcoupon($couponCode = '')
	{
		// Prevent CSRF attack
		Session::checkToken('get') or jexit(Text::_('JINVALID_TOKEN'));
		$user    = Factory::getUser();

		if (!$user->id)
		{
			return false;
		}

		$input = Factory::getApplication()->input;

		if (empty($couponCode))
		{
			$couponCode = $input->get('coupon_code', '', 'STRING');
		}

		$model = $this->getModel('payment');

		if (!$model->isCouponExists($couponCode))
		{
			echo json_encode(array("not_exists" => true));
			jexit();
		}

		$isValidCoupon = $model->isValidCoupon($couponCode);

		if ($isValidCoupon == 1)
		{
			echo json_encode(array("unpublished" => true));
			jexit();
		}

		if ($isValidCoupon == 2)
		{
			echo json_encode(array("expired" => true));
			jexit();
		}

		if ($isValidCoupon == 3)
		{
			echo json_encode(array("max_use_exceeded" => true));
			jexit();
		}

		if ($isValidCoupon == 4)
		{
			echo json_encode(array("max_use_per_user_exceeded" => true));
			jexit();
		}

		if ($isValidCoupon == 6)
		{
			echo json_encode(array("not_started" => true));
			jexit();
		}

		$SocialadsPaymentHelper = new SocialadsPaymentHelper;
		$couponCount            = $SocialadsPaymentHelper->getcoupon($couponCode);

		if (empty($couponCount))
		{
			echo json_encode(array("expired" => true));
			jexit();
		}

		if ($isValidCoupon == 5)
		{
			$c = array();
			$c[] = array(
			"value"    => $couponCount[0]->value,
			"val_type" => $couponCount[0]->val_type
			);

			echo json_encode($c);
			jexit();
		}
	}

	/**
	 * Function to get jomsocial or alphauser points
	 *
	 * @return  void
	 *
	 * @since  1.6
	 *
	 **/
	public function getpoints()
	{
		$user         = Factory::getUser();
		$db           = Factory::getDBO();
		$input        = Factory::getApplication()->input;
		$count        = -1;
		$plugin       = PluginHelper::getPlugin('payment', $input->get('plugin_name', '', 'STRING'));
		$pluginParams = json_decode($plugin->params);

		switch ($input->get('plugin_name', '', 'STRING'))
		{
			case 'jomsocialpoints':
				$query       = "SELECT points FROM #__community_users WHERE userid=" . $user->id;
				$db->setQuery($query);
				$count       = $db->loadResult();
				$conversion1 = $pluginParams->conversion;
				echo $count . "|" . $conversion1;
			break;

			// AlphaUserPoints Plugin Payment
			case 'alphauserpoints':
				$query       = "SELECT points FROM #__alpha_userpoints where userid=" . $user->id;
				$db->setQuery($query);
				$count       = $db->loadResult();
				$conversion2 = $pluginParams->conversion;
				echo $count . "|" . $conversion2;
			break;

			default: echo $count;
		}

		jexit();
	}

	/**
	 * Process free order
	 *
	 * @params  void
	 *
	 * @since  1.0
	 *
	 * @return void
	 */
	public function sa_processFreeOrder()
	{
		$model = $this->getModel('payment');
		$model->processFreeOrder();
	}

	/**
	 * Method to get tax.
	 *
	 * @return  void
	 *
	 * @since   3.0
	 **/
	public function getPaymentTax()
	{
		$db     = Factory::getDBO();
		$jinput = Factory::getApplication()->input;
		$amount = $jinput->get('amount', '');

		PluginHelper::importPlugin('adstax');
		$taxresults = Factory::getApplication()->triggerEvent('onAfterSocialAdAddTax', array($amount));
		$appliedTax = 0;

		if (!empty($taxresults))
		{
			foreach ($taxresults as $tax)
			{
				if (!empty($tax))
				{
					$appliedTax += $tax[1];
				}
			}
		}

		$amountAfterTax = $amount + $appliedTax;
		$returnData = [
			'originalAmount' => $amount,
			'appliedTax' => $appliedTax,
			'amountAfterTax' => $amountAfterTax
		];

		echo json_encode($returnData);
		jexit();
	}
}