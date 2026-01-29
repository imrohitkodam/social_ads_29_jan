<?php
/**
 * @package    Techjoomla.Libraries
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\Form\FormRule;

use Joomla\Registry\Registry;

/**
 * Rule for date validation
 *
 * @since  __DEPLY_VERSION__
 */
class JFormRuleDate extends FormRule
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
	 * @since   __DEPLY_VERSION__
	 * @throws  \UnexpectedValueException if rule is invalid.
	 */
	public function test(\SimpleXMLElement $element, $value, $group = null, Registry $input = null, Form $form = null)
	{
		if (empty($value))
		{
			return  false;
		}

		// This applied when date string format is checked.

		if (!empty($element->attributes()->dateFormat))
		{
			$format = $element->attributes()->dateFormat;
		}
		else
		{
			// If no format is passed then get the defaul format.
			$format = "Y-m-d";

			// Generate different format depending on the xml params
			if (!empty($element->attributes()->showtime))
			{
				$format = 'Y-m-d H:i:s';
			}
		}

		// Validate for format
		$dt = DateTime::createFromFormat($format, $value);

		if ($dt instanceof DateTime)
		{
			return true;
		}

		return false;
	}
}
