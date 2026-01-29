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
 * @since  1.6
 */
class SocialadsViewIgnores extends HtmlView
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

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			throw new Exception(implode("\n", $errors));
		}

		SocialadsHelper::addSubmenu('ignores');

		$this->addToolbar();

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
		ToolBarHelper::title(Text::_('COM_SOCIALADS_TITLE_IGNORES'), 'ignores.png');

		// Check if the form exists before showing the add/edit buttons
		$formPath = JPATH_COMPONENT_ADMINISTRATOR . '/views/';

		if (file_exists($formPath))
		{
			if ($canDo->get('core.create'))
			{
				ToolBarHelper::addNew('.add', 'JTOOLBAR_NEW');
			}

			if ($canDo->get('core.edit') && isset($this->items[0]))
			{
				ToolBarHelper::editList('.edit', 'JTOOLBAR_EDIT');
			}
		}

		if ($canDo->get('core.edit.state'))
		{
			if (isset($this->items[0]->state))
			{
				ToolBarHelper::divider();
				ToolBarHelper::custom('ignores.publish', 'publish.png', 'publish_f2.png', 'JTOOLBAR_PUBLISH', true);
				ToolBarHelper::custom('ignores.unpublish', 'unpublish.png', 'unpublish_f2.png', 'JTOOLBAR_UNPUBLISH', true);
			}
			elseif (isset($this->items[0]))
			{
				// If this component does not use state then show a direct delete button as we can not trash
				ToolBarHelper::deleteList('', 'ignores.delete', 'JTOOLBAR_DELETE');
			}

			if (isset($this->items[0]->state))
			{
				ToolBarHelper::divider();
				ToolBarHelper::archiveList('ignores.archive', 'JTOOLBAR_ARCHIVE');
			}

			if (isset($this->items[0]->checked_out))
			{
				ToolBarHelper::custom('ignores.checkin', 'checkin.png', 'checkin_f2.png', 'JTOOLBAR_CHECKIN', true);
			}
		}

		// Show trash and delete for components that uses the state field
		if (isset($this->items[0]->state))
		{
			if ($state->get('filter.state') == -2 && $canDo->get('core.delete'))
			{
				ToolBarHelper::deleteList('', 'ignores.delete', 'JTOOLBAR_EMPTY_TRASH');
				ToolBarHelper::divider();
			}
			elseif ($canDo->get('core.edit.state'))
			{
				ToolBarHelper::trash('ignores.trash', 'JTOOLBAR_TRASH');
				ToolBarHelper::divider();
			}
		}

		if ($canDo->get('core.admin'))
		{
			ToolBarHelper::preferences('com_socialads');
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
		'a.ad_feedback' => Text::_('COM_SOCIALADS_IGNORES_AD_FEEDBACK'),
		'a.idate' => Text::_('COM_SOCIALADS_IGNORES_IDATE'),
		);
	}
}
