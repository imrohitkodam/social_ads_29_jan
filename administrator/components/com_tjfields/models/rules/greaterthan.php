<?php
/**
 * @package    TJ-Fields
 *
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2019 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormRule;
use Joomla\CMS\Form\Rule\NumberRule;
use Joomla\Registry\Registry;
use Joomla\String\StringHelper;

/**
 * JFormRule for com_tjfields to make sure the email address is not blocked.
 *
 * @since  1.6
 */
class FormRuleGreaterThan extends NumberRule
{
	/**
	 * Method to test for value of the field greater than the given field
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
		// Check the basic validations
		if (!parent::test($element, $value, $group, $input, $form))
		{
			return false;
		}

		$field = (string) $element['field'];

		// Check that a validation field is set.
		if (!$field)
		{
			return false;
		}

		$input = Factory::getApplication()->input;
		$recordId = $input->get('recordid', '', 'INT');

		$test = '';

		if ($recordId)
		{
			$db = Factory::getDbo();
			$query = $db->getQuery(true);
			$query->select($db->quoteName('fv.value'));
			$query->from($db->quoteName('#__tjfields_fields_value', 'fv'));
			$query->join('INNER', $db->quoteName('#__tjfields_fields', 'f') . ' ON ' . $db->quoteName('f.id') . ' = ' . $db->quoteName('fv.field_id'));
			$query->where($db->quoteName('fv.content_id') . ' = ' . $recordId);
			$query->where($db->quoteName('f.name') . ' = ' . $db->quote($field));
			$db->setQuery($query);
			$test = $db->loadresult();
		}

		// Test the two values against each other.
		return (int) $value > (int) $test;
	}
}
