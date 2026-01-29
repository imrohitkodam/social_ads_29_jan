<?php
/**
 * @package    Techjoomla.Libraries
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Form\FormRule;
use Joomla\CMS\Language\Text;
use Joomla\Registry\Registry;

// Load lang. file
$lang = Factory::getLanguage();
$lang->load('lib_techjoomla', JPATH_SITE, '', true);

/**
 * Rule for blankspaces
 *
 * @since  __DEPLY_VERSION__
 */
class JFormRuleBlankspace extends FormRule
{
	/**
	 * Method to test the value.
	 *
	 * @param   \SimpleXMLElement  $element  The SimpleXMLElement object representing the `<field>` tag for the form field object.
	 * @param   mixed              $value    The form field value to validate.
	 * @param   string             $group    The field name group control value. This acts as as an array container for the field.
	 *                                       For example if the field has name="foo" and the group value is set to "bar" then the
	 *                                       full field name would end up being "bar[foo]".
	 * @param   Registry           $input    An optional Registry object with the entire data set to validate against the entire form.
	 * @param   Form               $form     The form object for which the field is being tested.
	 *
	 * @return  boolean  True if the value is valid, false otherwise.
	 *
	 * @since   1.6
	 * @throws  \UnexpectedValueException if rule is invalid.
	 */
	public function test(\SimpleXMLElement $element, $value, $group = null, Registry $input = null, Form $form = null)
	{
		if (trim($value) == '')
		{
			// This applied to only required fields
			if ($element->attributes()->required)
			{
				$msg = Text::_('LIB_TECHJOOMLA_FORM_RULES_ONLY_WHITESPACES_NOT_ALLOWED') .
				Text::_('LIB_TECHJOOMLA_FORM_RULES_FIELD') . '"' . Text::_($element->attributes()->label) . '"';

				if (!empty($element->attributes()->message))
				{
					$element->attributes()->message = $msg;
					\Factory::getApplication()->enqueueMessage($msg, 'warning');
				}

				return false;
			}
		}

		return true;
	}
}
