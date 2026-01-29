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
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Language\Text;

jimport('joomla.application.component.view');

/**
 * View class for list of fields.
 *
 * @package     Tjfields
 * @subpackage  com_tjfields
 * @since       2.2
 */
class TjfieldsViewFields extends HtmlView
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
		$input           = Factory::getApplication()->input;
		$client          = $input->get('client', '', 'STRING');

		$this->state      = $this->get('State');
		$this->items      = $this->get('Items');
		$this->pagination = $this->get('Pagination');

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			throw new Exception(implode("\n", $errors));
		}

		TjfieldsHelper::addSubmenu('fields');

		$this->addToolbar();

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
		require_once JPATH_COMPONENT . '/helpers/tjfields.php';
		$input           = Factory::getApplication()->input;
		$client          = $input->get('client', '', 'STRING');
		$client          = explode('.', $client);
		$component_title = '';

		$toolbar = Toolbar::getInstance('toolbar');
		$toolbar->appendButton('Custom', '<a id="tjHouseKeepingFixDatabasebutton" class="btn btn-default hidden"><span class="icon-refresh"></span>' . Text::_('COM_TJFIELDS_FIX_DATABASE') . '</a>');

		if (!empty($client))
		{
			switch ($client['0'])
			{
				case 'com_jticketing' :
					$component_title = Text::_('COM_JTICKETING_COMPONENT');
					JToolBarHelper::back('COM_JTICKETING_HOME', 'index.php?option=com_jticketing&view=cp');
					break;

				case 'com_tjlms':
					$component_title = Text::_('COM_TJLMS_COMPONENT_LABEL') . ' : ';

					$lang = Factory::getLanguage();
					$lang->load('com_tjlms', JPATH_ADMINISTRATOR, 'en-GB', true);

					break;
			}
		}

		$state = $this->get('State');
		$canDo = TjfieldsHelper::getActions($client[0], 'field');

		if (JVERSION >= '3.0')
		{
			JToolBarHelper::title($component_title . Text::_('COM_TJFIELDS_TITLE_FIELDS'), 'list');
		}
		else
		{
			JToolBarHelper::title($component_title . Text::_('COM_TJFIELDS_TITLE_FIELDS'), 'fields.png');
		}

		// Check if the form exists before showing the add/edit buttons
		$formPath = JPATH_COMPONENT_ADMINISTRATOR . '/views/field';

		if (file_exists($formPath))
		{
			if ($canDo->get('core.create'))
			{
				JToolBarHelper::addNew('field.add', 'JTOOLBAR_NEW');
			}
		}

		if ($canDo->get('core.edit.state'))
		{
			if (isset($this->items[0]->state))
			{
				JToolBarHelper::divider();
				JToolBarHelper::custom('fields.publish', 'publish.png', 'publish_f2.png', 'JTOOLBAR_PUBLISH', true);
				JToolBarHelper::custom('fields.unpublish', 'unpublish.png', 'unpublish_f2.png', 'JTOOLBAR_UNPUBLISH', true);
			}
			elseif (isset($this->items[0]))
			{
				// If this component does not use state then show a direct delete button as we can not trash
				JToolBarHelper::deleteList('', 'fields.delete', 'JTOOLBAR_DELETE');
			}

			if (isset($this->items[0]->checked_out))
			{
				JToolBarHelper::custom('fields.checkin', 'checkin.png', 'checkin_f2.png', 'JTOOLBAR_CHECKIN', true);
			}
		}

		// Show trash and delete for components that uses the state field
		if (isset($this->items[0]->state))
		{
			if ($state->get('filter.state') == -2 && $canDo->get('core.delete'))
			{
				JToolBarHelper::deleteList('', 'fields.delete', 'JTOOLBAR_EMPTY_TRASH');
				JToolBarHelper::divider();
			}
			elseif ($canDo->get('core.edit.state'))
			{
				JToolBarHelper::trash('fields.trash', 'JTOOLBAR_TRASH');
				JToolBarHelper::divider();
			}
		}

		if ($canDo->get('core.admin'))
		{
			JToolBarHelper::preferences('com_tjfields');
		}

		$this->extra_sidebar = '';
		$this->filterForm    = $this->get('FilterForm');
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
			'a.label' => Text::_('COM_TJFIELDS_FIELDS_LABEL'),
			'a.type' => Text::_('COM_TJFIELDS_FIELDS_FIELD_TYPE'),
			'a.state' => Text::_('JSTATUS'),
			'a.client' => Text::_('COM_TJFIELDS_FIELDS_CLIENT')
		);
	}
}
