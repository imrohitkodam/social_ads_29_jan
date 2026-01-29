<?php
/**
 * @version    SVN:<SVN_ID>
 * @package    SocialAds
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
use Joomla\CMS\Form\FormHelper;
use \Joomla\CMS\Factory;
use Joomla\CMS\Toolbar\ToolbarHelper;

/**
 * View class for a list of Socialads.
 *
 * @since  1.6
 */
class SocialadsViewCampaigns extends HtmlView
{
	protected $items;

	protected $pagination;

	protected $state;

	protected $params;

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
		$this->state      = $this->get('State');
		$this->items      = $this->get('Items');
		$this->pagination = $this->get('Pagination');
		$this->params     = ComponentHelper::getParams('com_socialads');

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			throw new Exception(implode("\n", $errors));
		}

		SocialadsHelper::addSubmenu('campaigns');
		$this->publish_states = array(
			''   => Text::_('JOPTION_SELECT_PUBLISHED'),
			'1'  => Text::_('JPUBLISHED'),
			'0'  => Text::_('JUNPUBLISHED')
		);

		if ($this->params->get('payment_mode') == 'wallet_mode')
		{
			$this->addToolbar();
		}

		$this->sidebar = JHtmlSidebar::render();
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

		FormHelper::addFieldPath(JPATH_COMPONENT . '/models/fields');
		$campaigns = FormHelper::loadFieldType('Campains', false);
		$zones = FormHelper::loadFieldType('Zones', false);

		// Get campaigns list
		$this->campaignsoptions = $campaigns->getOptions();
		FormHelper::addFieldPath(JPATH_COMPONENT . '/models/fields');
		$creates = FormHelper::loadFieldType('Usernamelist', false);

		$this->createdbyoptions = $creates->getOptions();

		if (JVERSION >= '3.0')
		{
			ToolBarHelper::title(Text::_('COM_SOCIALADS') . ': ' . Text::_('COM_SOCIALADS_TITLE_CAMPAIGNS'), 'list');
		}
		else
		{
			ToolBarHelper::title(Text::_('COM_SOCIALADS') . ': ' . Text::_('COM_SOCIALADS_TITLE_CAMPAIGNS'), 'campaigns.png');
		}

		// Check if the form exists before showing the add/edit buttons
		$formPath = JPATH_COMPONENT_ADMINISTRATOR . '/views/campaign';

		if (file_exists($formPath))
		{
			if ($canDo->get('core.create'))
			{
				ToolBarHelper::addNew('campaign.add', 'JTOOLBAR_NEW');
			}

			if ($canDo->get('core.edit') && isset($this->items[0]))
			{
				ToolBarHelper::editList('campaign.edit', 'JTOOLBAR_EDIT');
			}
		}

		if ($canDo->get('core.edit.state'))
		{
			if (isset($this->items[0]->state))
			{
				ToolBarHelper::divider();
				ToolBarHelper::custom('campaigns.publish', 'publish.png', 'publish_f2.png', 'JTOOLBAR_PUBLISH', true);
				ToolBarHelper::custom('campaigns.unpublish', 'unpublish.png', 'unpublish_f2.png', 'JTOOLBAR_UNPUBLISH', true);
				ToolBarHelper::custom('campaigns.adCsvExport', 'download', 'download', 'COM_SOCIALADS_ADS_CSV_EXPORT', false);
			}

			if (isset($this->items[0]->checked_out))
			{
				ToolBarHelper::custom('campaigns.checkin', 'checkin.png', 'checkin_f2.png', 'JTOOLBAR_CHECKIN', true);
			}
		}

		if (isset($this->items[0]))
		{
			if ($canDo->get('core.delete'))
			{
				ToolBarHelper::deleteList('', 'campaigns.delete', 'JTOOLBAR_DELETE');
			}
		}

		if ($canDo->get('core.admin'))
		{
			ToolBarHelper::preferences('com_socialads');
		}

		// Set sidebar action - New in 3.0
		JHtmlSidebar::setAction('index.php?option=com_socialads&view=campaigns');

		$this->extra_sidebar = '';
	}
}
