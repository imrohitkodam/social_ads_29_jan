<?php
/**
 * @package    TJFields
 * 
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2019 TechJoomla. All rights reserved
 * @license    GNU General Public License version 2, or later
 */

defined('JPATH_PLATFORM') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Form\FormHelper;

JFormHelper::loadFieldClass('list');
JFormHelper::loadFieldClass('category');

/**
 * Form Field class for the Joomla Platform.
 * Supports an HTML select list of categories
 *
 * @since  1.5
 */
class JFormFieldItemCategory extends JFormFieldCategory
{
	/**
	 * The form field type.
	 *
	 * @var    string
	 * @since  1.5
	 */
	public $type = 'Itemcategory';

	/**
	 * Method to get the field options for category
	 * Use the extension attribute in a form to specify the.specific extension for
	 * which categories should be displayed.
	 * Use the show_root attribute to specify whether to show the global category root in the list.
	 *
	 * @return  array    The field option objects.
	 *
	 * @since   1.5
	 */
	protected function getOptions()
	{
		if ($this->element['name'] instanceof SimpleXMLElement)
		{
			$client = str_replace('_itemcategoryitemcategory', '', $this->element['name']->__toString());
			$client = 'com_tjucm.' . str_replace('com_tjucm_', '', $client);
		}

		$app = Factory::getApplication();

		if (empty($client))
		{
			$jinput = $app->input;
			$client = $jinput->get('client', '', "STRING");
		}

		if (empty($client))
		{
			$menu = $app->getMenu()->getActive();

			if ($menu->params instanceof Joomla\Registry\Registry)
			{
				$ucmTypeAlias = $menu->params->get('ucm_type', '', 'STRING');

				$db = Factory::getDbo();
				Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjucm/tables');
				$ucmTypeTable = Table::getInstance('Type', 'TjucmTable', array('dbo', $db));
				$ucmTypeTable->load(array('alias' => $ucmTypeAlias));

				$client = $ucmTypeTable->unique_identifier;
			}
		}

		$this->element->addAttribute('extension', $client);

		$options = array(array('value' => '', "text" => Text::_("JGLOBAL_SELECT_AN_OPTION")));
		$options = array_merge($options, parent::getOptions());

		return $options;
	}
}
