<?php
/**
 * @package    TJ-Fields
 *
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2019 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

defined('_JEXEC') or die;

use Joomla\CMS\Form\FormRule;
use Joomla\Registry\Registry;
use Joomla\String\StringHelper;

/**
 * JFormRule for com_contact to make sure the email address is not blocked.
 *
 * @since  1.6
 */
class FormRuleTextareaCounter extends FormRule
{
	/**
	 * Method to test for banned email addresses
	 *
	 * @param   SimpleXMLElement  $element  The SimpleXMLElement object representing the <field /> tag for the form field object.
	 * @param   mixed             $value    The form field value to validate.
	 * @param   string            $group    The field name group control value. This acts as an array container for the field.
	 *                                      For example if the field has name="foo" and the group value is set to "bar" then the
	 *                                      full field name would end up being "bar[foo]".
	 * @param   Registry          $input    An optional Registry object with the entire data set to validate against the entire form.
	 * @param   JForm             $form     The form object for which the field is being tested.
	 *
	 * @return  boolean  True if the value is valid, false otherwise.
	 */
	public function test(SimpleXMLElement $element, $value, $group = null, Registry $input = null, Form $form = null)
	{
		$maxLength = ($element['maxlength'] instanceof SimpleXMLElement) ? $element['maxlength']->__toString() : '';
		$minLength = ($element['minlength'] instanceof SimpleXMLElement) ? $element['minlength']->__toString() : '';
		$required = ($element['required'] instanceof SimpleXMLElement) ? $element['required']->__toString() : 'false';

		if ($required == 'true')
		{
			if (strlen(trim($value)) == 0)
			{
				return false;
			}
		}

		if ((strlen(trim($value)) > 0) && ($maxLength != '') && (strlen(trim($value)) > (int) $maxLength))
		{
			return false;
		}

		if ((strlen(trim($value)) > 0) && ($minLength != '') && (strlen(trim($value)) < (int) $minLength))
		{
			return false;
		}

		return true;
	}
}
