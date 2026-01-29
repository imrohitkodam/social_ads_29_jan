<?php
/**
 * @version    SVN:<SVN_ID>
 * @package    Com_SocialAds
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2015 TechJoomla. All rights reserved
 * @license    GNU General Public License version 2, or later
 */

// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\HTML\HTMLHelper;

use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Toolbar\ToolbarHelper;

/**
 * View class for a list of Socialads.
 *
 * @since  1.6
 */
class SocialadsViewForms extends HtmlView
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
		// If any Ad id in session, clear it
		Factory::getSession()->clear('ad_id');

		$this->state = $this->get('State');
		$this->items = $this->get('Items');
		$this->pagination = $this->get('Pagination');

		$zone_list = $this->get('Zonelist');

		FormHelper::addFieldPath(JPATH_COMPONENT . '/models/fields');
		$campaigns = FormHelper::loadFieldType('Campains', false);
		$zones = FormHelper::loadFieldType('Zones', false);

		// Get campaigns list
		$this->campaignsoptions = $campaigns->getOptions();
		$this->zoneOptions = $zones->getOptions();

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			throw new Exception(implode("\n", $errors));
		}

		SocialadsHelper::addSubmenu('forms');

		$this->publish_states = array(
			'' => Text::_('COM_SOCIALADS_CHOOSE_STATUS'),
			'1'  => Text::_('JPUBLISHED'),
			'0'  => Text::_('JUNPUBLISHED')
		);

		// Status select box
		$status = array();
		$status[] = HTMLHelper::_('select.option', '0', Text::_('COM_SOCIALADS_AD_PENDING'));
		$status[] = HTMLHelper::_('select.option', '1',  Text::_('COM_SOCIALADS_AD_CONFIRM'));
		$status[] = HTMLHelper::_('select.option', '2', Text::_('COM_SOCIALADS_ADS_REJECTED'));
		$this->status = $status;

		$this->ostatus = array();
		$this->ostatus[] = HTMLHelper::_('select.option', '-1', Text::_('COM_SOCIALADS_CHOOSE_ADS_APPROVAL_STATUS'));
		$this->ostatus[] = HTMLHelper::_('select.option', '0',  Text::_('COM_SOCIALADS_AD_PENDING'));
		$this->ostatus[] = HTMLHelper::_('select.option', '1',  Text::_('COM_SOCIALADS_AD_CONFIRM'));
		$this->ostatus[] = HTMLHelper::_('select.option', '2',  Text::_('COM_SOCIALADS_ADS_REJECTED'));

		// For zone list
		$zone_ad = array();

		foreach ($zone_list as $selected_zone)
		{
			$zone_ad['0'] = HTMLHelper::_('select.option', '0', 'Select');
			$i = 1;
			$zname = $selected_zone->zone_name;
			$zid = $selected_zone->id;
			$zone_ad[$i] = HTMLHelper::_('select.option', $zid, $zname);
			$i++;
		}

		$this->zone_array = $zone_ad;
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

		ToolBarHelper::title(Text::_('COM_SOCIALADS') . ': ' . Text::_('COM_SOCIALADS_TITLE_ADS'), 'list');

		// Check if the form exists before showing the add/edit buttons
		$formPath = JPATH_COMPONENT_ADMINISTRATOR . '/views/form';

		if (file_exists($formPath))
		{
			if ($canDo->get('core.create'))
			{
				ToolBarHelper::addNew('form.add', 'JTOOLBAR_NEW');

				if (isset($this->items[0]->state))
				{
					ToolbarHelper::save2copy('forms.saveAsCopy');
				}
			}

			if ($canDo->get('core.edit') && isset($this->items[0]))
			{
				ToolBarHelper::editList('form.edit', 'JTOOLBAR_EDIT');
			}
		}

		if ($canDo->get('core.edit.state'))
		{
			if (isset($this->items[0]->state))
			{
				ToolBarHelper::divider();
				ToolBarHelper::custom('forms.publish', 'publish.png', 'publish_f2.png', 'JTOOLBAR_PUBLISH', true);
				ToolBarHelper::custom('forms.unpublish', 'unpublish.png', 'unpublish_f2.png', 'JTOOLBAR_UNPUBLISH', true);
				ToolBarHelper::custom('forms.adCsvExport', 'download', 'download', 'COM_SOCIALADS_ADS_CSV_EXPORT', false);
			}

			if (isset($this->items[0]->checked_out))
			{
				ToolBarHelper::custom('forms.checkin', 'checkin.png', 'checkin_f2.png', 'JTOOLBAR_CHECKIN', true);
			}
		}

		if (isset($this->items[0]))
		{
			if ($canDo->get('core.delete'))
			{
				ToolBarHelper::deleteList('', 'forms.delete', 'JTOOLBAR_DELETE');
			}
		}

		if ($canDo->get('core.admin'))
		{
			ToolBarHelper::preferences('com_socialads');
		}

		if (JVERSION >= '3.0')
		{
			// Set sidebar action - New in 3.0
			JHtmlSidebar::setAction('index.php?option=com_socialads&view=forms');
			$this->extra_sidebar = '';
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
		'a.ad_id' => Text::_('JGRID_HEADING_ID'),
		'a.ordering' => Text::_('JGRID_HEADING_ORDERING'),
		'a.state' => Text::_('JSTATUS'),
		);
	}
}
