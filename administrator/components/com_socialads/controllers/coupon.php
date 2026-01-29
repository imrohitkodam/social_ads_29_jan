<?php
/**
 * @version    SVN:<SVN_ID>
 * @package    Com_Socialads
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2015 TechJoomla. All rights reserved
 * @license    GNU General Public License version 2, or later
 */

// No direct access
defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Factory;

	/**
	 * Coupon controller class.
	 *
	 * @since  3.2
	 */
class SocialadsControllerCoupon extends FormController
{
	/**
	 *Function to construct a zones view
	 *
	 * @since  3.0
	 */
	public function __construct()
	{
		$this->view_list = 'coupons';
		parent::__construct();
	}

	/**
	 *Function to get code
	 *
	 * @return  void
	 *
	 * @since  3.0
	 */
	public function getcode()
	{
		$input = Factory::getApplication()->input;
		$selectedcode = $input->get('selectedcode', '', 'STRING');
		$model = $this->getModel('coupon');
		$coupon_code = $model->getcode(trim($selectedcode));
		echo $coupon_code;
		exit();
	}

	/**
	 *Function to select code
	 *
	 * @return  void
	 *
	 * @since  3.0
	 */
	public function getselectcode()
	{
		$input = Factory::getApplication()->input;
		$selectedcode = $input->get('selectedcode', '', 'STRING');
		$couponid = $input->get('couponid', 0, 'INT');
		$model = $this->getModel('coupon');
		$coupon_code = $model->getselectcode(trim($selectedcode), $couponid);
		echo $coupon_code;

		exit();
	}
}
