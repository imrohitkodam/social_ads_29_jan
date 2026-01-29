<?php
/**
 * @version    SVN:<SVN_ID>
 * @package    Com_Socialads
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2015 TechJoomla. All rights reserved
 * @license    GNU General Public License version 2, or later
 */

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\HTML\HTMLHelper;
use \Joomla\CMS\Language\Text;
use \Joomla\CMS\Uri\Uri;
use \Joomla\CMS\Factory;
use \Joomla\CMS\Component\ComponentHelper;
use \Joomla\CMS\Router\Route;

// Import Joomla view library

/**
 * HTML View class for the Wallet
 *
 * @since  1.6
 */
class SocialadsViewWallet extends HtmlView
{
	protected $pagination;

	/**
	 * Overwriting JView display method
	 *
	 * @param   boolean  $cachable   parameter.
	 * @param   boolean  $urlparams  url parameter.
	 * @param   array    $tpl        An optional associative array.
	 *
	 * @return  array
	 *
	 * @since 1.6
	 */
	public function display($cachable = false, $urlparams = false,$tpl = null)
	{
		$this->session1 = Factory::getSession();
		$this->params = ComponentHelper::getParams('com_socialads');
		$this->user = Factory::getUser();
		$this->mainframe = Factory::getApplication();
		$this->input = Factory::getApplication()->input;
		$app = Factory::getApplication();

		if (!$this->user->id)
		{
			$msg = Text::_('COM_SOCIALADS_LOGIN_MSG');
			$uri = Uri::getInstance()->toString();
			$url = urlencode(base64_encode($uri));
			$itemId = SaCommonHelper::getSocialadsItemid('user');
			$this->mainframe->enqueueMessage($msg, 'success');
			$this->mainframe->redirect(Route::_('index.php?option=com_users&view=login&Itemid=' . $itemId . '&return=' . $url, false));
		}

		$payment_mode = $this->params->get('payment_mode');

		if ($payment_mode == 'pay_per_ad_mode')
		{
			$app->enqueueMessage(Text::_('COM_SOCIALADS_WALLET_NO_AUTH_SEE'), 'warning');

			return false;
		}

		$init_balance = SaWalletHelper::getBalance();
		$this->paymentitemid  = SaCommonHelper::getSocialadsItemid('payment');

		if ($init_balance != 1.00)
		{
			$not_msg = Text::_('COM_SOCIALADS_WALLET_MINIMUM_BALANCE_MESSAGE');
			$not_msg = str_replace(
			'{clk_pay_link}', '<a href="'
			. Route::_('index.php?option=com_socialads&view=payment&Itemid=' . $this->paymentitemid)
			. '">' . Text::_('COM_SOCIALADS_WALLET_CLKHERE') . '</a>', $not_msg
			);

			$app = Factory::getApplication();
			$app->enqueueMessage($not_msg, 'error');
		}

		$currentBSViews = $this->params->get('bootstrap_version', "bs3");
		$this->bsVersion = $currentBSViews;

		$option = $this->input->get('option', '', 'STRING');
		$month = $this->mainframe->getUserStateFromRequest($option . 'month', 'month', '', 'int');
		$year = $this->mainframe->getUserStateFromRequest($option . 'year', 'year', '', 'int');
		$builadModel = $this->getModel();
		$user_id = $this->user->id;
		$this->state = $this->get('State');

		$builadModel->setState('user', $user_id);
		$this->wallet = $this->items = $this->get('Items');
		$this->pagination = $this->get('Pagination');
		$builadModel->setState('list.limit', $this->get('Total'));
		$this->walletTrasaction = $this->get('Items');
		$this->months = array(
			0 => Text::_('COM_SOCIALADS_WALLET_MONTH'),
			1 => Text::_('JANUARY_SHORT'),
			2 => Text::_('FEBRUARY_SHORT'),
			3 => Text::_('MARCH_SHORT'),
			4 => Text::_('APRIL_SHORT'),
			5 => Text::_('MAY_SHORT'),
			6 => Text::_('JUNE_SHORT'),
			7 => Text::_('JULY_SHORT'),
			8 => Text::_('AUGUST_SHORT'),
			9 => Text::_('SEPTEMBER_SHORT'),
			10 => Text::_('OCTOBER_SHORT'),
			11 => Text::_('NOVEMBER_SHORT'),
			12 => Text::_('DECEMBER_SHORT'),
		);
		$lists['month'] = $month;
		$lists['year'] = $year;
		$this->lists = $lists;
		$this->month = array();

		foreach ($this->months as $key => $value) :
			$this->month[] = HTMLHelper::_('select.option', $key, $value);
		endforeach;

		// Year filter
		$this->year = array();
		$curYear = date('Y');
		$this->year = range($curYear, 2000, 1);

		foreach ($this->year as $key => $value)
		{
			unset($this->year[$key]);
			$this->year[$value] = $value;
		}

		foreach ($this->year as $key => $value) :
			$year1[] = HTMLHelper::_('select.option', $key, $value);
		endforeach;

		parent::display($tpl);
	}
}
