<?php
/**
 * @version    SVN: <svn_id>
 * @package    TJ-Fields
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2018 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

defined('JPATH_PLATFORM') or die;

use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;

jimport('joomla.html.html');
JFormHelper::loadFieldClass('list');

/**
 * List of fields
 *
 * @since  1.3
 */
class JFormFieldtjfieldfields extends JFormFieldList
{
	/**
	 * The form field type.
	 *
	 * @var		string
	 *
	 * @since	1.3
	 */
	protected $type = 'tjfieldfields';

	/**
	 * Method to get the field input markup.
	 *
	 * @return	string	The field input markup.
	 *
	 * @since	1.3
	 */
	protected function getInput()
	{
		$installUcm = 0;

		$installCluster = 0;

		// To check com_tjucm component is installed
		if (ComponentHelper::getComponent('com_tjucm', true)->enabled)
		{
			$installUcm = 1;
		}

		// To check com_cluster component is installed
		if (ComponentHelper::getComponent('com_cluster', true)->enabled)
		{
			$installCluster = 1;
		}

		$options = array();
		$options[] = HTMLHelper::_('select.option', 'text', Text::_('COM_TJFIELDS_TEXT'));
		$options[] = HTMLHelper::_('select.option', 'radio', Text::_('COM_TJFIELDS_RADIO'));
		$options[] = HTMLHelper::_('select.option', 'checkbox', Text::_('COM_TJFIELDS_CHECKBOX'));
		$options[] = HTMLHelper::_('select.option', 'tjlist', Text::_('COM_TJFIELDS_TJLIST'));
		$options[] = HTMLHelper::_('select.option', 'single_select', Text::_('COM_TJFIELDS_SINGLE_SELECT'));
		$options[] = HTMLHelper::_('select.option', 'multi_select', Text::_('COM_TJFIELDS_MULTI_SELECT'));
		$options[] = HTMLHelper::_('select.option', 'sql', Text::_('COM_TJFIELDS_SQL'));
		$options[] = HTMLHelper::_('select.option', 'textarea', Text::_('COM_TJFIELDS_TEXTAREA'));
		$options[] = HTMLHelper::_('select.option', 'textareacounter', Text::_('COM_TJFIELDS_TEXTAREACOUNTER'));
		$options[] = HTMLHelper::_('select.option', 'calendar', Text::_('COM_TJFIELDS_CALENDAR'));
		$options[] = HTMLHelper::_('select.option', 'editor', Text::_('COM_TJFIELDS_EDITOR'));
		$options[] = HTMLHelper::_('select.option', 'email', Text::_('COM_TJFIELDS_EMAIL'));
		$options[] = HTMLHelper::_('select.option', 'tjfile', Text::_('COM_TJFIELDS_FILE'));
		$options[] = HTMLHelper::_('select.option', 'spacer', Text::_('COM_TJFIELDS_SPACER'));
		$options[] = HTMLHelper::_('select.option', 'subform', Text::_('COM_TJFIELDS_SUBFORM'));
		$options[] = HTMLHelper::_('select.option', 'image', Text::_('COM_TJFIELDS_IMAGE'));
		$options[] = HTMLHelper::_('select.option', 'captureimage', Text::_('COM_TJFIELDS_CAPTURE_IMAGE'));
		$options[] = HTMLHelper::_('select.option', 'audio', Text::_('COM_TJFIELDS_AUDIO'));
		$options[] = HTMLHelper::_('select.option', 'video', Text::_('COM_TJFIELDS_VIDEO'));
		$options[] = HTMLHelper::_('select.option', 'itemcategory', Text::_('COM_TJFIELDS_ITEM_CATEGORY'));
		$options[] = HTMLHelper::_('select.option', 'number', Text::_('COM_TJFIELDS_NUMBER'));
		$options[] = HTMLHelper::_('select.option', 'hidden', Text::_('COM_TJFIELDS_HIDDEN'));

		if ($installUcm === 1)
		{
			$options[] = HTMLHelper::_('select.option', 'ucmsubform', Text::_('COM_TJFIELDS_UCMSUBFORM'));
			$options[] = HTMLHelper::_('select.option', 'related', Text::_('COM_TJFIELDS_RELATED'));
		}

		if ($installCluster === 1)
		{
			$options[] = HTMLHelper::_('select.option', 'cluster', Text::_('COM_TJFIELDS_CLUSTER'));
		}

		$options[] = HTMLHelper::_('select.option', 'ownership', Text::_('COM_TJFIELDS_OWNERSHIP'));
		$options[] = HTMLHelper::_('select.option', 'color', Text::_('COM_TJFIELDS_COLOR'));

		$options = array_merge(parent::getOptions(), $options);

		$view = Factory::getApplication()->input->get('view', '', 'STRING');

		$onchange = ($view == 'field') ? "show_option_div(this.value);" : "this.form.submit();";

		$class = (JVERSION < '4.0.0') ? '' : 'form-select';

		return HTMLHelper::_('select.genericlist', $options, $this->name,
			'class="required ' . $class . '" onchange="' . $onchange . '"', 'value', 'text', $this->value, 'jform_type'
		);
	}
}
