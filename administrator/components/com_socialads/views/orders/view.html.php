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
use Joomla\CMS\HTML\HTMLHelper;

use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\Toolbar\ToolbarHelper;

/**
 * View class for a list of Socialads.
 *
 * @since  1.6
 */
class SocialadsVieworders extends HtmlView
{
	protected $items;

	protected $pagination;

	protected $params;

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
		$this->params     = ComponentHelper::getParams('com_socialads');

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			throw new Exception(implode("\n", $errors));
		}

		SocialadsHelper::addSubmenu('orders');

		if (JVERSION < 3.0)
		{
			$this->selectStatus[] = HTMLHelper::_('select.option', '-1',  Text::_('SA_SELONE'));
			$this->pay[] = HTMLHelper::_('select.option', '1', Text::_('NORMAL_PAY'));
			$this->selectStatus_gateway[] = HTMLHelper::_('select.option', '0', Text::_('FILTER_GATEWAY'));
		}

		$selectStatus = array();
		$selectStatus[] = HTMLHelper::_('select.option', 'P',  Text::_('COM_SOCIALADS_AD_PENDING'));
		$selectStatus[] = HTMLHelper::_('select.option', 'C',  Text::_('COM_SOCIALADS_AD_CONFIRM'));
		$selectStatus[] = HTMLHelper::_('select.option', 'RF',  Text::_('COM_SOCIALADS_AD_REFUND'));
		$selectStatus[] = HTMLHelper::_('select.option', 'E', Text::_('COM_SOCIALADS_AD_CANCEL'));

		if ($this->params->get('payment_mode') == 'wallet_mode')
		{
			$this->addToolbar();
		}

		$this->ostatus = array();
		$this->ostatus[] = HTMLHelper::_('select.option', '', Text::_('SA_ORDER_STATUS'));
		$this->ostatus[] = HTMLHelper::_('select.option', 'P',  Text::_('COM_SOCIALADS_AD_PENDING'));
		$this->ostatus[] = HTMLHelper::_('select.option', 'C',  Text::_('COM_SOCIALADS_AD_CONFIRM'));
		$this->ostatus[] = HTMLHelper::_('select.option', 'RF',  Text::_('COM_SOCIALADS_AD_REFUND'));
		$this->ostatus[] = HTMLHelper::_('select.option', 'E', Text::_('COM_SOCIALADS_AD_CANCEL'));

		$this->sidebar = JHtmlSidebar::render();

		FormHelper::addFieldPath(JPATH_COMPONENT . '/models/fields');
		$gateways = FormHelper::loadFieldType('Gatewaylist', false);

		$this->gatewayoptions = $gateways->getOptions();

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

		ToolBarHelper::title(Text::_('COM_SOCIALADS') . ': ' . Text::_('COM_SOCIALADS_TITLE_ORDERS'), 'list');

		if ($canDo->get('core.admin'))
		{
			ToolBarHelper::preferences('com_socialads');
		}

		JHtmlSidebar::setAction('index.php?option=com_socialads&view=adorders');

		$this->extra_sidebar = '';
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
		'a.id' => Text::_('JGRID_HEADING_ID'),
		'a.cdate' => Text::_('COM_SOCIALADS_ADORDERS_CDATE'),
		'a.amount' => Text::_('COM_SOCIALADS_ADORDERS_AD_AMOUNT'),
		'a.status' => Text::_('COM_SOCIALADS_ADORDERS_STATUS'),
		'a.processor' => Text::_('COM_SOCIALADS_ADORDERS_PROCESSOR'),
		'u.username' => Text::_('COM_SOCIALADS_ADORDERS_USERNAME'),
		);
	}
}
