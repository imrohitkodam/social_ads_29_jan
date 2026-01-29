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
use Joomla\CMS\Language\Text;
use Joomla\CMS\Toolbar\ToolbarHelper;

/**
 * View class for a list of Socialads.
 *
 * @since  1.0
 */
class SocialadsViewZones extends HtmlView
{
	protected $items;

	protected $pagination;

	protected $state;

	/**
	 * Display the view
	 *
	 * @param   boolean  $tpl  used to get displayed value
	 *
	 * @return  void
	 */
	public function display($tpl = null)
	{
		$this->items = $this->get('Items');
		$this->pagination = $this->get('Pagination');
		$this->state = $this->get('State');
		$this->filterForm    = $this->get('FilterForm');
		$this->activeFilters = $this->get('ActiveFilters');
		$this->modules = $this->get('ZoneModules');

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			throw new Exception(implode("\n", $errors));
		}

		SocialadsHelper::addSubmenu('zones');
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
	 * @since	1.6
	 */
	protected function addToolbar()
	{
		require_once JPATH_COMPONENT . '/helpers/socialads.php';

		$state = $this->get('State');
		$canDo = SocialadsHelper::getActions($state->get('filter.category_id'));

		ToolBarHelper::title(Text::_('COM_SOCIALADS') . ': ' . Text::_('COM_SOCIALADS_TITLE_ZONES'), 'list');

		// Check if the form exists before showing the add/edit buttons
		$formPath = JPATH_COMPONENT_ADMINISTRATOR . '/views/zone';

		if (file_exists($formPath))
		{
			if ($canDo->get('core.create'))
			{
				ToolBarHelper::addNew('zone.add', 'JTOOLBAR_NEW');
			}

			if ($canDo->get('core.edit') && isset($this->items[0]))
			{
				ToolBarHelper::editList('zone.edit', 'JTOOLBAR_EDIT');
			}
		}

		if ($canDo->get('core.edit.state'))
		{
			if (isset($this->items[0]->state))
			{
				ToolBarHelper::divider();
				ToolBarHelper::custom('zones.publish', 'publish.png', 'publish_f2.png', 'JTOOLBAR_PUBLISH', true);
				ToolBarHelper::custom('zones.unpublish', 'unpublish.png', 'unpublish_f2.png', 'JTOOLBAR_UNPUBLISH', true);
			}

			if (isset($this->items[0]->checked_out))
			{
				ToolBarHelper::custom('zones.checkin', 'checkin.png', 'checkin_f2.png', 'JTOOLBAR_CHECKIN', true);
			}
		}

		// Show trash and delete for components that uses the state field

		if (isset($this->items[0]))
		{
			if ($canDo->get('core.delete'))
			{
				ToolBarHelper::deleteList('', 'zones.delete', 'JTOOLBAR_DELETE');
			}
		}

		if ($canDo->get('core.admin'))
		{
			ToolBarHelper::preferences('com_socialads');
		}

		JHtmlSidebar::setAction('index.php?option=com_socialads&view=zones');

		$this->extra_sidebar = '';
	}

	/**
	 * Function to get a sorted list
	 *
	 * @return  void
	 */
	protected function getSortFields()
	{
		return array(
		'a.id' => Text::_('JGRID_HEADING_ID'),
		'a.ordering' => Text::_('JGRID_HEADING_ORDERING'),
		'a.state' => Text::_('JSTATUS'),
		'a.zone_name' => Text::_('COM_SOCIALADS_ZONES_ZONE_NAME'),
		'a.published' => Text::_('COM_SOCIALADS_ZONES_PUBLISHED'),
		'a.orientation' => Text::_('COM_SOCIALADS_ZONES_ORIENTATION'),
		'a.ad_type' => Text::_('COM_SOCIALADS_ZONES_AD_TYPE'),
		'a.max_title' => Text::_('COM_SOCIALADS_ZONES_MAX_TITLE'),
		'a.max_des' => Text::_('COM_SOCIALADS_ZONES_MAX_DES'),
		'a.img_width' => Text::_('COM_SOCIALADS_ZONES_IMG_WIDTH'),
		'a.img_height' => Text::_('COM_SOCIALADS_ZONES_IMG_HEIGHT'),
		'a.per_click' => Text::_('COM_SOCIALADS_ZONES_PER_CLICK'),
		'a.per_imp' => Text::_('COM_SOCIALADS_ZONES_PER_IMP'),
		'a.per_day' => Text::_('COM_SOCIALADS_ZONES_PER_DAY'),
		'a.num_ads' => Text::_('COM_SOCIALADS_ZONES_NUM_ADS'),
		'a.layout' => Text::_('COM_SOCIALADS_ZONES_LAYOUT'),
		);
	}
}
