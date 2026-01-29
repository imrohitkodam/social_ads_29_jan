<?php
/**
 * @package		com_jmailalerts
 * @version		$versionID$
 * @author		TechJoomla
 * @author mail	extensions@techjoomla.com
 * @website		http://techjoomla.com
 * @copyright	Copyright © 2009-2013 TechJoomla. All rights reserved.
 * @license		GNU General Public License version 2, or later
*/

defined('JPATH_BASE') or die;

use Joomla\CMS\Form\FormField;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;

jimport('joomla.html.html');
jimport('joomla.form.formfield');

/**
 * Supports an HTML select list of categories
 */
class JFormFieldCustomfield extends JFormField
{
	/**
	 * The form field type.
	 *
	 * @var		string
	 * @since	1.6
	 */
	protected $type = 'text';

	/**
	 * Method to get the field input markup.
	 *
	 * @return	string	The field input markup.
	 * @since	1.6
	 */
	protected function getInput()
	{
		// Initialize variables.
		switch($this->name)
		{
			case 'jform[group_id]':
				$controlName = (isset($this->options['control'])) ? $this->options['control'] : '';

				return $this->fetchGroupid($this->name,$this->value,$this->element, $controlName);
			break;

			/*case 'jform[client_type]':
				return $this->fetchClientType($this->name,$this->value,$this->element,$this->options['control']);
			break;*/
		}

	}
	/**
	 * Method to genereate list of allowed frequencies
	 * @return	list	The list of frequencies
	 */

	protected function fetchGroupid($name, $value, &$node, $control_name)
	{
		$input = Factory::getApplication()->input;
		$db=Factory::getDbo();
		$query	= $db->getQuery(true);
		$query->select('grp.id,grp.name FROM `#__tjfields_groups` as grp');
		$query->where('grp.state=1 AND client="'.$input->get('client','','STRING').'"');
		$db->setQuery($query);
		$groups=$db->loadObjectList();
		$options = array();
		foreach($groups as $group){
			$options[] = HTMLHelper::_('select.option',$group->id, $group->name);
		}

		$class = (JVERSION < '4.0.0') ? '' : 'form-select';

		return HTMLHelper::_('select.genericlist',  $options, $name, 'class="inputbox required ' . $class . '"', 'value', 'text', $value,'jform_group_id');
	}

	/**
	 * Method to genereate list of allowed frequencies
	 * @return	list	The list of frequencies
	 */
	 /*
	function fetchClientType($name, $value, &$node, $control_name)
	{
		//print_r($value); die('asda');
		$input = Factory::getApplication()->input;

		$full_client = $input->get('client','','STRING');
		$full_client =  explode('.',$full_client);

		$client = $full_client[0];

		if(empty($value))
		{
			$value = $full_client[1];
		}

					$db=Factory::getDbo();
					$query	= $db->getQuery(true);
					$query->select('client_type FROM `#__tjfields_client_type` as client_type');
					$query->where('client_type.client="'.$client.'"');
					$db->setQuery($query);

					$client_type=$db->loadObjectList();

		$options = array();
		foreach($client_type as $type){
			$options[] = HTMLHelper::_('select.option',$type->client_type, $type->client_type);
		}
		return HTMLHelper::_('select.genericlist',  $options, $name, 'class="inputbox required"', 'value', 'text', $value, $control_name.$name );
		//return HTMLHelper::_('select.genericlist', $options, $fieldName, 'class="inputbox required"', 'value', 'text', $value, $control_name.$name );
	}
	*/
}
