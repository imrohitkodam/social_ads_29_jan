<?php
/**
 * @version    SVN: <svn_id>
 * @package    Tjfields
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2015 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

jimport('joomla.application.component.view');

/**
 * View class for editing group.
 *
 * @package     Tjfields
 * @subpackage  com_tjfields
 * @since       2.2
 */
class TjfieldsViewGroups extends HtmlView
{
	protected $items;

	protected $pagination;

	protected $state;

	/**
	 * Display the view
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  void
	 */
	public function display($tpl = null)
	{
		$this->state      = $this->get('State');
		$this->items      = $this->get('Items');
		$this->pagination = $this->get('Pagination');
		$this->filterForm = $this->get('FilterForm');
		$this->activeFilters = $this->get('ActiveFilters');

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			throw new Exception(implode("\n", $errors));
		}

		TjfieldsHelper::addSubmenu('groups');
		$this->addToolbar();

		$this->publish_states = array(
			'' => Text::_('JOPTION_SELECT_PUBLISHED'),
			'1'  => Text::_('JPUBLISHED'),
			'0'  => Text::_('JUNPUBLISHED')
		);

		if (JVERSION >= '3.0')
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
	 * @since   1.6
	 */
	protected function addToolbar()
	{
		$input = Factory::getApplication()->input;
		JLoader::import('TjfieldsHelper', JPATH_ADMINISTRATOR . '/components/com_tjfields/helpers');
		$client          = $input->get('client', '', 'STRING');

		$component_title = '';

		if (!empty($client))
		{
			$client = explode('.', $client);

			switch ($client['0'])
			{
				case 'com_jticketing' :
					$component_title = Text::_('COM_JTICKETING_COMPONENT');
					break;
				case 'com_tjlms':
					$component_title = Text::_('COM_TJLMS_COMPONENT');
					break;
				case 'com_tjucm':
					$component_title = Text::_('COM_TJUCM_COMPONENT');
			}
		}

		if (!empty($client) and $client['0'] == 'com_jticketing')
		{
			JToolBarHelper::back('COM_JTICKETING_HOME', 'index.php?option=com_jticketing&view=cp');
		}

		$state = $this->get('State');
		$tjfieldsHelper = new TjfieldsHelper;

		$canDo = $tjfieldsHelper->getActions($client[0], 'group');
		JToolBarHelper::title($component_title . ": " . Text::_('COM_TJFIELDS_TITLE_GROUPS'), 'list.png');

		// Check if the form exists before showing the add/edit buttons
		$formPath = JPATH_COMPONENT_ADMINISTRATOR . '/views/group';

		if (file_exists($formPath))
		{
			if ($canDo->get('core.create'))
			{
				JToolBarHelper::addNew('group.add', 'JTOOLBAR_NEW');
			}
		}

		if ($canDo->get('core.edit.state'))
		{
			if (isset($this->items[0]->state))
			{
				JToolBarHelper::divider();
				JToolBarHelper::custom('groups.publish', 'publish.png', 'publish_f2.png', 'JTOOLBAR_PUBLISH', true);
				JToolBarHelper::custom('groups.unpublish', 'unpublish.png', 'unpublish_f2.png', 'JTOOLBAR_UNPUBLISH', true);
			}
			elseif (isset($this->items[0]))
			{
				// If this component does not use state then show a direct delete button as we can not trash
				JToolBarHelper::deleteList('', 'groups.delete', 'JTOOLBAR_DELETE');
			}

			if (isset($this->items[0]->checked_out))
			{
				JToolBarHelper::custom('groups.checkin', 'checkin.png', 'checkin_f2.png', 'JTOOLBAR_CHECKIN', true);
			}
		}

		// Show trash and delete for components that uses the state field
		if (isset($this->items[0]->state))
		{
			if ($state->get('filter.state') == -2 && $canDo->get('core.delete'))
			{
				JToolBarHelper::deleteList('', 'groups.delete', 'JTOOLBAR_EMPTY_TRASH');
				JToolBarHelper::divider();
			}
			elseif ($canDo->get('core.edit.state'))
			{
				JToolBarHelper::trash('groups.trash', 'JTOOLBAR_TRASH');
				JToolBarHelper::divider();
			}
		}

		if ($canDo->get('core.admin'))
		{
			JToolBarHelper::preferences('com_tjfields');
		}
	}

	/**
	 * Add the sort
	 *
	 * @return  void
	 *
	 * @since   1.6
	 */
	protected function getSortFields()
	{
		return array(
			'a.id' => Text::_('JGRID_HEADING_ID'),
			'a.ordering' => Text::_('JGRID_HEADING_ORDERING'),
			'a.state' => Text::_('JSTATUS'),
			'a.created_by' => Text::_('COM_TJFIELDS_GROUPS_CREATED_BY'),
			'a.name' => Text::_('COM_TJFIELDS_GROUPS_NAME'),
			'a.client' => Text::_('COM_TJFIELDS_GROUPS_CLIENT'),
			'a.client_type' => Text::_('COM_TJFIELDS_GROUPS_CLIENT_TYPE')
		);
	}
}
