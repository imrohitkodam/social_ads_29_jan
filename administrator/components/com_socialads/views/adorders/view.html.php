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
use Joomla\CMS\HTML\HTMLHelper;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Component\ComponentHelper;

/**
 * View class for a list of Socialads.
 *
 * @since  1.6
 */
class SocialadsViewAdorders extends HtmlView
{
	protected $items;

	protected $pagination;

	protected $state;

	/**
	 * Display the view
	 *
	 * @param   array  $tpl  An optional associative array.
	 *
	 * @return  array
	 *
	 * @since 1.6
	 */
	public function display($tpl = null)
	{
		$this->state = $this->get('State');
		$this->items = $this->get('Items');
		$this->pagination = $this->get('Pagination');
		$this->params = ComponentHelper::getParams('com_socialads');

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			throw new Exception(implode("\n", $errors));
		}

		SocialadsHelper::addSubmenu('adorders');

		$pstatus = array();

		$pstatus[] = HTMLHelper::_('select.option', 'P', Text::_('COM_SOCIALADS_AD_PENDING'));
		$pstatus[] = HTMLHelper::_('select.option', 'C', Text::_('COM_SOCIALADS_AD_CONFIRM'));
		$pstatus[] = HTMLHelper::_('select.option', 'RF', Text::_('COM_SOCIALADS_AD_REFUND'));
		$pstatus[] = HTMLHelper::_('select.option', 'E', Text::_('COM_SOCIALADS_AD_CANCEL'));

		// $this->assignRef('pstatus', $pstatus);
		$this->pstatus = $pstatus;

		$selectStatus = array();
		$selectStatus[] = HTMLHelper::_('select.option', 'P',  Text::_('COM_SOCIALADS_AD_PENDING'));
		$selectStatus[] = HTMLHelper::_('select.option', 'C',  Text::_('COM_SOCIALADS_AD_CONFIRM'));
		$selectStatus[] = HTMLHelper::_('select.option', 'RF',  Text::_('COM_SOCIALADS_AD_REFUND'));
		$selectStatus[] = HTMLHelper::_('select.option', 'E', Text::_('COM_SOCIALADS_AD_CANCEL'));

		if ($this->params->get('payment_mode') == 'pay_per_ad_mode')
		{
			$this->addToolbar();
		}

		$this->ostatus = array();
		$this->ostatus[] = HTMLHelper::_('select.option', '', Text::_('SA_ORDER_STATUS'));
		$this->ostatus[] = HTMLHelper::_('select.option', 'P',  Text::_('COM_SOCIALADS_AD_PENDING'));
		$this->ostatus[] = HTMLHelper::_('select.option', 'C',  Text::_('COM_SOCIALADS_AD_CONFIRM'));
		$this->ostatus[] = HTMLHelper::_('select.option', 'RF',  Text::_('COM_SOCIALADS_AD_REFUND'));
		$this->ostatus[] = HTMLHelper::_('select.option', 'E', Text::_('COM_SOCIALADS_AD_CANCEL'));

		if (JVERSION < '4.0')
		{
			$this->sidebar = JHtmlSidebar::render();
		}

		FormHelper::addFieldPath(JPATH_COMPONENT . '/models/fields');
		$gateways = FormHelper::loadFieldType('Gatewaylist', false);
		$this->gatewayoptions = $gateways->getOptions();
		$input = Factory::getApplication()->input;
		$layout = $input->get('layout', '', 'STRING');

		if ($layout == "details")
		{
			$this->order_id = $input->get('id', '', 'INT');
			$this->socialadsPaymentHelper = new SocialadsPaymentHelper;
			$this->adDetail = $this->socialadsPaymentHelper->getOrderAndAdDetail($this->order_id);
			$this->userInformation = $this->socialadsPaymentHelper->userInfo($this->order_id);

			// No of clicks or impression
			$this->chargeoption = $this->adDetail['ad_payment_type'];
			$this->ad_totaldisplay = $this->adDetail['ad_credits_qty'];
			$this->ad_gateways = $gateways;
		}

		parent::display($tpl);
	}

	/**
	 * Add the page title and toolbar.
	 *
	 * @return  void
	 *
	 * @since  1.6
	 */
	protected function addToolbar()
	{
		require_once JPATH_COMPONENT . '/helpers/socialads.php';

		$state = $this->get('State');
		$canDo = SocialadsHelper::getActions($state->get('filter.category_id'));

		ToolBarHelper::title(Text::_('COM_SOCIALADS') . ': ' . Text::_('COM_SOCIALADS_TITLE_ADORDERS'), 'list');

		if ($canDo->get('core.admin'))
		{
			ToolBarHelper::preferences('com_socialads');
		}

		JHtmlSidebar::setAction('index.php?option=com_socialads&view=adorders');

		$this->extra_sidebar = '';
		$mainframe = Factory::getApplication();
		$input = $mainframe->input;
		$layout = $input->get('layout', '', 'STRING');

		if ($layout == 'details')
		{
			ToolBarHelper::back('COM_SOCIALADS_BACK', 'index.php?option=com_socialads&view=adorders');

			ToolBarHelper::custom('printOrder', 'print', 'print', 'COM_SOCIALADS_PRINT', false);
		}
	}

	/**
	 * For sorting filter.
	 *
	 * @return  void
	 *
	 * @since  1.6
	 */
	protected function getSortFields()
	{
		return array(
		'o.id' => Text::_('JGRID_HEADING_ID'),
		'd.ad_id' => Text::_('COM_SOCIALADS_ADORDERS_AD_ID'),
		'd.ad_title' => Text::_('COM_SOCIALADS_ADORDERS_AD_TITLE'),
		'o.cdate' => Text::_('COM_SOCIALADS_ADORDERS_CDATE'),
		'p.ad_credits_qty' => Text::_('COM_SOCIALADS_ADORDERS_AD_CREDITS_QTY'),
		'o.amount' => Text::_('COM_SOCIALADS_ADORDERS_AD_AMOUNT'),
		'o.status' => Text::_('COM_SOCIALADS_ADORDERS_STATUS'),
		'u.username' => Text::_('COM_SOCIALADS_ADORDERS_USERNAME'),
		'o.processor' => Text::_('COM_SOCIALADS_ADORDERS_PROCESSOR'),
		'd.ad_payment_type' => Text::_('COM_SOCIALADS_ADORDERS_AD_TYPE'),
		);
	}
}
