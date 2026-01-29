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

use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Toolbar\ToolbarHelper;

/**
 * View class for a list of Socialads.
 *
 * @since  1.6
 */
class SocialadsViewCoupons extends HtmlView
{
	protected $items;

	protected $params;

	protected $pagination;

	protected $state;

	/**
	 * Display the view
	 *
	 * @param   array  $tpl  An optional associative array.
	 *
	 * @return  array
	 *
	 * @since  1.6
	 */
	public function display($tpl = null)
	{
		$this->state      = $this->get('State');
		$this->items      = $this->get('Items');
		$this->pagination = $this->get('Pagination');
		$this->params     = ComponentHelper::getParams('com_socialads');

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			throw new Exception(implode("\n", $errors));
		}

		SocialadsHelper::addSubmenu('coupons');
		$this->publish_states = array(
			'' => Text::_('JOPTION_SELECT_PUBLISHED'),
			'1'  => Text::_('JPUBLISHED'),
			'0'  => Text::_('JUNPUBLISHED')
		);
		$this->addToolbar();

		if (JVERSION < '4.0')
		{
			$this->sidebar = JHtmlSidebar::render();
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

		ToolBarHelper::title(Text::_('COM_SOCIALADS') . ': ' . Text::_('COM_SOCIALADS_TITLE_COUPONS'), 'list');

		// Check if the form exists before showing the add/edit buttons
		$formPath = JPATH_COMPONENT_ADMINISTRATOR . '/views/coupon';

		if (file_exists($formPath))
		{
			if ($canDo->get('core.create'))
			{
				ToolBarHelper::addNew('coupon.add', 'JTOOLBAR_NEW');
			}

			if ($canDo->get('core.edit') && isset($this->items[0]))
			{
				ToolBarHelper::editList('coupon.edit', 'JTOOLBAR_EDIT');
			}
		}

		if ($canDo->get('core.edit.state'))
		{
			if (isset($this->items[0]->state))
			{
				ToolBarHelper::divider();
				ToolBarHelper::custom('coupons.publish', 'publish.png', 'publish_f2.png', 'JTOOLBAR_PUBLISH', true);
				ToolBarHelper::custom('coupons.unpublish', 'unpublish.png', 'unpublish_f2.png', 'JTOOLBAR_UNPUBLISH', true);
			}

			if (isset($this->items[0]->checked_out))
			{
				ToolBarHelper::custom('coupons.checkin', 'checkin.png', 'checkin_f2.png', 'JTOOLBAR_CHECKIN', true);
			}
		}

		if (isset($this->items[0]))
		{
			if ($canDo->get('core.delete'))
			{
				ToolBarHelper::deleteList('', 'coupons.delete', 'JTOOLBAR_DELETE');
			}
		}

		if ($canDo->get('core.admin'))
		{
			ToolBarHelper::preferences('com_socialads');
		}

		JHtmlSidebar::setAction('index.php?option=com_socialads&view=coupons');

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
		'a.name' => Text::_('COM_SOCIALADS_COUPONS_NAME'),
		'a.code' => Text::_('COM_SOCIALADS_COUPONS_CODE'),
		'a.value' => Text::_('COM_SOCIALADS_COUPONS_VALUE'),
		'a.state' => Text::_('JSTATUS'),
		'a.max_use' => Text::_('COM_SOCIALADS_COUPONS_MAX_USE'),
		'a.from_date' => Text::_('COM_SOCIALADS_COUPONS_FROM_DATE'),
		'a.exp_date' => Text::_('COM_SOCIALADS_COUPONS_EXP_DATE'),
		);
	}
}
