<?php
/**
 * @package     Joomla.Platform
 * @subpackage  Form
 *
 * @copyright   Copyright (C) 2005 - 2019 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('JPATH_PLATFORM') or die;

JFormHelper::loadFieldClass('list');

use Joomla\CMS\Factory;
use Joomla\CMS\Table\Table;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\Uri\Uri;

$document = Factory::getDocument();
$document->addScript(Uri::root() . 'administrator/components/com_tjfields/assets/js/relatedfield.js');

/**
 * Form Field class for the Joomla Platform.
 * Supports a generic list of options.
 *
 * @since  1.7.0
 */
class JFormFieldRelatedFields extends JFormFieldList
{
	/**
	 * The form field type.
	 *
	 * @var    string
	 * @since  1.7.0
	 */
	protected $type = 'RelatedFields';

	/**
	 * Method to get the field options.
	 *
	 * @return  array  The field option objects.
	 *
	 * @since   3.7.0
	 */
	protected function getOptions()
	{
		if (empty($this->value))
		{
			return parent::getOptions();
		}

		$db = Factory::getDbo();
		JLoader::import('field', JPATH_ROOT . '/administrator/components/com_tjfields/tables');
		$fieldTable = Table::getInstance('field', 'TjfieldsTable', array('dbo', $db));
		$fieldTable->load(array('id' => $this->value[0]));

		JLoader::import('fields', JPATH_ROOT . '/administrator/components/com_tjfields/models');
		$fieldsModel = BaseDatabaseModel::getInstance("Fields", "TjfieldsModel", array('ignore_request' => true));

		// Set client in model state
		if (!empty($fieldTable->client))
		{
			$fieldsModel->setState('filter.client', $fieldTable->client);
			$fieldsModel->setState('filter.state', 1);
		}

		$results = $fieldsModel->getItems();
		$allowedFieldTypes = array("text", "textarea", "textareacounter", "email", "number");

		$options = array();

		foreach ($results as $result)
		{
			if (in_array($result->type, $allowedFieldTypes))
			{
				$options[] = array("value" => $result->id, "text" => $result->label);
			}
		}

		return $options;
	}
}
