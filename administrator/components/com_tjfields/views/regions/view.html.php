<?php
/**
 * @version    SVN: <svn_id>
 * @package    Tjfields
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2015 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

// No direct access
defined('_JEXEC') or die();
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Toolbar\Toolbar;

/**
 * View class for a list of regions.
 *
 * @package     Tjfields
 * @subpackage  com_tjfields
 * @since       2.2
 */
class TjfieldsViewRegions extends HtmlView
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
	public function display ($tpl = null)
	{
		$this->state         = $this->get('State');
		$this->items         = $this->get('Items');
		$this->pagination    = $this->get('Pagination');
		$this->filterForm    = $this->get('FilterForm');
		$this->activeFilters = $this->get('ActiveFilters');
		$this->input         = Factory::getApplication()->input;

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			throw new Exception(implode("\n", $errors));
		}

		TjfieldsHelper::addSubmenu('regions');

		if (JVERSION >= '3.0')
		{
			$this->sidebar = JHtmlSidebar::render();
		}

		if (JVERSION < '3.0')
		{
			// Creating status filter.
			$sstatus = array();
			$sstatus[] = HTMLHelper::_('select.option', '', Text::_('JOPTION_SELECT_PUBLISHED'));
			$sstatus[] = HTMLHelper::_('select.option', 1, Text::_('JPUBLISHED'));
			$sstatus[] = HTMLHelper::_('select.option', 0, Text::_('JUNPUBLISHED'));

			$this->sstatus = $sstatus;

			// Creating country filter.
			$countries = array();
			$countries[] = HTMLHelper::_('select.option', '', Text::_('COM_TJFIELDS_FILTER_SELECT_COUNTRY'));

			require_once JPATH_COMPONENT . '/models/fields/countries.php';
			$countriesField = new FormFieldCountries;
			$this->countries = $countriesField->getOptionsExternally();

			// Merge options
			$this->countries = array_merge($countries, $this->countries);
		}

		$this->addToolbar();
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

		$client        = Factory::getApplication()->input->get('client', '', 'STRING');
		$extention     = explode('.', $client);
		$canDo         = TjfieldsHelper::getActions($extention[0], 'region');
		$extensionName = strtoupper($client);
		$bar           = ToolBar::getInstance('toolbar');

		// Need to load the menu language file as mod_menu hasn't been loaded yet.
		$lang = Factory::getLanguage();
		$lang->load($client, JPATH_ADMINISTRATOR, null, false, true);

		if (JVERSION >= '3.0')
		{
			ToolBarHelper::title(Text::_($extensionName) . ': ' . Text::_('COM_TJFIELDS_TITLE_REGIONS'), 'list');
		}
		else
		{
			ToolBarHelper::title(Text::_($extensionName) . ': ' . Text::_('COM_TJFIELDS_TITLE_REGIONS'), 'regions.png');
		}

		// Check if the form exists before showing the add/edit buttons
		$formPath = JPATH_COMPONENT_ADMINISTRATOR . '/views/region';

		if (file_exists($formPath))
		{
			if ($canDo->get('core.create'))
			{
				ToolBarHelper::addNew('region.add', 'JTOOLBAR_NEW');
			}
		}

		if (JVERSION >= '4.0.0')
		{
			$dropdown = $bar->dropdownButton('status-group')
				->text('JTOOLBAR_CHANGE_STATUS')
				->toggleSplit(false)
				->icon('icon-ellipsis-h')
				->buttonClass('btn btn-action')
				->listCheck(true);

			$childBar = $dropdown->getChildToolbar();
		}

		if ($canDo->get('core.edit.state'))
		{
			if (isset($this->items[0]->state))
			{
				if (JVERSION < '4.0.0')
				{
					ToolBarHelper::divider();
					ToolBarHelper::custom('regions.publish', 'publish.png', 'publish_f2.png', 'JTOOLBAR_PUBLISH', true);
					ToolBarHelper::custom('regions.unpublish', 'unpublish.png', 'unpublish_f2.png', 'JTOOLBAR_UNPUBLISH', true);
				}
				else
				{
					$childBar->publish('regions.publish')->listCheck(true);
					$childBar->unpublish('regions.unpublish')->listCheck(true);
				}
			}
		}

		HTMLHelper::_('bootstrap.modal', 'collapseModal');

		if ($canDo->get('core.admin'))
		{
			ToolBarHelper::preferences('com_tjfields');
		}

		if (JVERSION >= '3.0')
		{
			// Set sidebar action
			JHtmlSidebar::setAction('index.php?option=com_tjfields&view=regions');
		}

		$this->extra_sidebar = '';
	}
}
