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
use Joomla\CMS\Factory;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;

jimport('joomla.filesystem.path');

JLoader::register('JFormFieldSubform', JPATH_SITE . '/libraries/joomla/form/fields/list.php');

/**
 * The Field to load the form inside current form
 *
 * @Example with all attributes:
 * 	<field name="field-name" type="subform"
 * 		formsource="path/to/form.xml" min="1" max="3" multiple="true" buttons="add,remove,move"
 * 		layout="joomla.form.field.subform.repeatable-table" groupByFieldset="false" component="com_example" client="site"
 * 		label="Field Label" description="Field Description" />
 *
 * @since  1.3
 */
class JFormFieldCompareWithNumberFields extends JFormFieldList
{
	/**
	 * The form field type.
	 * @var    string
	 */
	protected $type = 'Comparewithnumberfields';

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

		$input = Factory::getApplication()->input;
		$fieldId = $input->get('id', '', 'INT');
		$currentClient = $input->get('client', '', "STRING");

		$db    = Factory::getDbo();
		$query = $db->getQuery(true);

		$query->select($db->quoteName(array('id', 'label', 'name')));
		$query->from($db->quoteName('#__tjfields_fields'));
		$query->where($db->quoteName('client') . ' = ' . $db->quote($currentClient));

		if ($fieldId)
		{
			Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjfields/tables');
			$fieldTable = Table::getInstance('Field', 'TjfieldsTable', array('dbo', $db));
			$fieldTable->load($fieldId);

			$query->where($db->quoteName('name') . ' != ' . $db->quote($fieldTable->name));
		}

		$query->where($db->quoteName('type') . ' = ' . $db->quote('number'));
		$query->where($db->quoteName('state') . '=1');
		$db->setQuery($query);
		$fields  = $db->loadObjectList();

		$options[] = HTMLHelper::_('select.option', '', Text::_('COM_TJFIELDS_FORM_NUMBER_FIELD_COMPARE_WITH_FIELD'));

		foreach ($fields as $field)
		{
			$options[] = HTMLHelper::_('select.option', $field->name, $field->label);
		}

		return HTMLHelper::_('select.genericlist', $options, $this->name, 'class="inputbox"',
		'value', 'text', $this->value, $this->id, $this->name
		);
	}
}
