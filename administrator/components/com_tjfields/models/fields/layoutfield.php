<?php
/**
 * @version    SVN: <svn_id>
 * @package    TJ-Fields
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2018 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

defined('JPATH_PLATFORM') or die;

use Joomla\CMS\Form\FormField;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

jimport('joomla.html.html');
jimport('joomla.form.formfield');

/**
 * Supports an formsource select list of subform
 *
 * @since  1.3
 */
class JFormFieldlayoutfield extends JFormField
{
	/**
	 * The form field type.
	 *
	 * @var		string
	 *
	 * @since	1.3
	 */
	protected $type = 'text';

	/**
	 * Method to get the field input markup.
	 *
	 * @return	string	The field input markup.
	 *
	 * @since	1.3
	 */
	protected function getInput()
	{
		$options = array();
		$options[] = HTMLHelper::_(
								'select.option', 'joomla.form.field.subform.repeatable',
								Text::_('COM_TJFIELDS_FORM_LBL_FIELD_SELECT_FIELD_LAYOUT_REPETABLE')
							);

		$options[] = HTMLHelper::_(
								'select.option', 'joomla.form.field.subform.repeatable-table',
								Text::_('COM_TJFIELDS_FORM_LBL_FIELD_SELECT_FIELD_LAYOUT_REPETABLE_TABLE')
							);

		return HTMLHelper::_('select.genericlist', $options, $this->name,
		'class="inputbox required"', 'value', 'text', $this->value, $this->options['control'] . $this->name
		);
	}
}
