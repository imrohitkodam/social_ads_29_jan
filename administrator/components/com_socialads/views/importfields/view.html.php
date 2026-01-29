<?php
/**
 * @version    SVN:<SVN_ID>
 * @package    Com_Socialads
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2015 TechJoomla. All rights reserved
 * @license    GNU General Public License version 2, or later
 */

// No direct access to this file
defined('_JEXEC') or die(';)');
use Joomla\CMS\HTML\HTMLHelper;

use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Component\ComponentHelper;

/**
 * HTML View class for the socialads Component
 *
 * @since  1.6
 */
class SocialadsViewImportfields extends HtmlView
{
	/**
	 * social_targetting view display method
	 *
	 * @param   integer  $tpl  pass value
	 *
	 * @return  void
	 *
	 * @since  1.6
	 **/
	public function display($tpl = null)
	{
		$this->adcount = $this->get('AdData');

		$this->pluginresult = $this->get('PluginData');

		$this->colfields = $this->get('colfields');

		$mappinglistt[] = HTMLHelper::_('select.option', '0', Text::_("COM_SOCIALADS_SOCIAL_TARGETING_DONT_MAP"));
		$mappinglistt[] = HTMLHelper::_('select.option', 'textbox', Text::_("COM_SOCIALADS_SOCIAL_TARGETING_FREETEXT"));
		$mappinglistt[] = HTMLHelper::_('select.option', 'numericrange', Text::_("COM_SOCIALADS_SOCIAL_TARGETING_NUMERIC_RANGE"));

		$mappinglista[] = HTMLHelper::_('select.option', '0', Text::_("COM_SOCIALADS_SOCIAL_TARGETING_DONT_MAP"));
		$mappinglista[] = HTMLHelper::_('select.option', 'textbox', Text::_("COM_SOCIALADS_SOCIAL_TARGETING_FREETEXT"));

		$mappinglists[] = HTMLHelper::_('select.option', '0', Text::_("COM_SOCIALADS_SOCIAL_TARGETING_DONT_MAP"));
		$mappinglists[] = HTMLHelper::_('select.option', 'singleselect', Text::_("COM_SOCIALADS_SOCIAL_TARGETING_SINGLE_SELECT"));
		$mappinglists[] = HTMLHelper::_('select.option', 'multiselect', Text::_("COM_SOCIALADS_SOCIAL_TARGETING_MULTIPLE_SELECT"));

		$mappinglistd[] = HTMLHelper::_('select.option', '0', Text::_("COM_SOCIALADS_SOCIAL_TARGETING_DONT_MAP"));
		$mappinglistd[] = HTMLHelper::_('select.option', 'daterange', Text::_("COM_SOCIALADS_SOCIAL_TARGETING_DATE_RANGE"));
		$mappinglistd[] = HTMLHelper::_('select.option', 'date', Text::_("COM_SOCIALADS_SOCIAL_TARGETING_DATE"));

		$mapall[] = HTMLHelper::_('select.option', '0', Text::_("COM_SOCIALADS_SOCIAL_TARGETING_DONT_MAP"));
		$mapall[] = HTMLHelper::_('select.option', 'textbox', Text::_("COM_SOCIALADS_SOCIAL_TARGETING_FREETEXT"));
		$mapall[] = HTMLHelper::_('select.option', 'numericrange', Text::_("COM_SOCIALADS_SOCIAL_TARGETING_NUMERIC_RANGE"));
		$mapall[] = HTMLHelper::_('select.option', 'singleselect', Text::_("COM_SOCIALADS_SOCIAL_TARGETING_SINGLE_SELECT"));
		$mapall[] = HTMLHelper::_('select.option', 'multiselect', Text::_("COM_SOCIALADS_SOCIAL_TARGETING_MULTIPLE_SELECT"));
		$mapall[] = HTMLHelper::_('select.option', 'daterange', Text::_("COM_SOCIALADS_SOCIAL_TARGETING_DATE_RANGE"));
		$mapall[] = HTMLHelper::_('select.option', 'date', Text::_("COM_SOCIALADS_SOCIAL_TARGETING_DATE"));

		$this->mappinglista = $mappinglista;
		$this->mappinglistt = $mappinglistt;
		$this->mappinglistd = $mappinglistd;
		$this->mappinglists = $mappinglists;
		$this->mapall = $mapall;

		$this->fields = $this->get('ImportFields');

		$this->addToolbar();

		SocialadsHelper::addSubmenu('importfields');

		if (JVERSION >= 3.0)
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

		// JFactory::getApplication()->input->set('hidemainmenu', true);
		$user = Factory::getUser();
		$state = $this->get('State');
		$canDo = SocialadsHelper::getActions($state->get('filter.category_id'));

		$viewTitle = Text::_('COM_SOCIALADS_TITLE_SOCIAL_TARGETING');

		JHtmlSidebar::setAction('index.php?option=com_socialads&view=importfields');

		$this->extra_sidebar = '';

		ToolbarHelper::title(Text::_('COM_SOCIALADS') . ': ' . $viewTitle, 'pencil-2');

		$style = '';
		$style1 = '';

		$params      = ComponentHelper::getParams('com_socialads');
		$integration = $params->get('social_integration');
		$class        = JVERSION < '4.0' ? 'btn-small' : 'btn-sm';
		$button = '<a class="toolbar btn '. $class .' validate" type="submit" onclick="javascript:saAdmin.importfields.resetTargeting();"
		href="#"><i class="icon-remove ">' . $style1 . ' </i> Reset </a>';
		$bar = JToolBar::getInstance('toolbar');

		ToolBarHelper::back(Text::_('COM_SOCIALADS_SOCIAL_TARGETING_HOME'), 'index.php?option=com_socialads');

		if ($integration != "Joomla")
		{
			$bar->appendButton('Custom', $button);
		}

		$button = '<a class="toolbar btn '. $class .' validate" type="submit" onclick="javascript:saAdmin.importfields.saveTargeting()"
		href="#"><i class="icon-save "> ' . $style . '</i> Save </a>';

		$bar = JToolBar::getInstance('toolbar');

		if ($integration != "Joomla")
		{
			$bar->appendButton('Custom', $button);
		}

		if ($canDo->get('core.admin'))
		{
			ToolBarHelper::preferences('com_socialads');
		}
	}
}
