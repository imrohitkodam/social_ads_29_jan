
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

use Joomla\CMS\Table\Table;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\Registry\Registry;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;

/**
 * Form Field class for the Joomla Platform.
 * Supports a generic list of options.
 *
 * @since  1.7.0
 */
class JFormFieldRelated extends JFormFieldList
{
	/**
	 * The form field type.
	 *
	 * @var    string
	 * @since  1.7.0
	 */
	protected $type = 'Related';

	/**
	 * Method to get the field options.
	 *
	 * @return  array  The field option objects.
	 *
	 * @since   3.7.0
	 */
	public function getOptions()
	{
		// Load TJ-Fields language file
		$lang = Factory::getLanguage()->load('com_tjfields', JPATH_ADMINISTRATOR);

		$fieldname = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $this->fieldname);

		$db = Factory::getDbo();
		Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjfields/tables');
		$fieldTable = Table::getInstance('field', 'TjfieldsTable', array('dbo', $db));
		$fieldTable->load(array('name' => $fieldname));

		// Get object of TJ-Fields field model
		JLoader::import('components.com_tjfields.models.field', JPATH_ADMINISTRATOR);
		$tjFieldsModelField = BaseDatabaseModel::getInstance('Field', 'TjfieldsModel');
		$options = $tjFieldsModelField->getRelatedFieldOptions($fieldTable->id);

		return $options;
	}

	/**
	 * Method to get the field input markup for a generic list.
	 * Use the multiple attribute to enable multiselect.
	 *
	 * @return  string  The field input markup.
	 *
	 * @since   3.7.0
	 */
	protected function getInput()
	{
		$html      = parent::getInput();
		$fieldname = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $this->fieldname);
		$user      = Factory::getUser();
		$input     = Factory::getApplication()->input;
		$db        = Factory::getDbo();

		Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjfields/tables');
		$fieldTable = Table::getInstance('field', 'TjfieldsTable', array('dbo', $db));
		$fieldTable->load(array('name' => $fieldname));

		// Get decoded data object
		$fieldParams = new Registry($fieldTable->params);

		// UCM fields and fields from which options are to be generated
		$realtedFields = (array) $fieldParams->get('fieldName');

		if (count($realtedFields) == 1)
		{
			Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjucm/tables');
			$ucmTypeTable = Table::getInstance('Type', 'TjucmTable', array('dbo', $db));
			$ucmTypeTable->load(array('unique_identifier' => $realtedFields['fieldName0']->client));

			// Check if user is authorised to add the record in given UCM Type
			$canCreate = $user->authorise('core.type.createitem', 'com_tjucm.type.' . $ucmTypeTable->id);

			if ($canCreate)
			{
				foreach ($realtedFields['fieldName0']->fieldIds as $fieldId)
				{
					$canCreate = ($user->authorise('core.field.addfieldvalue', 'com_tjfields.field.' . $fieldId)) ? true : false;

					if (empty($canCreate))
					{
						break;
					}
				}
			}

			// UCM fields and field value to get
			$showAddNewRecordLink = $fieldParams->get('showAddNewRecordLink');
			$clusterAware = $fieldParams->get('clusterAware');

			if ($canCreate && !empty($showAddNewRecordLink))
			{
				$tjUcmFrontendHelper = new TjucmHelpersTjucm;
				$itemId = $tjUcmFrontendHelper->getItemId('index.php?option=com_tjucm&view=itemform&client=' . $ucmTypeTable->unique_identifier);
				$masterUcmLink = Route::_('index.php?option=com_tjucm&view=itemform&Itemid=' . $itemId, false);

				if ($clusterAware)
				{
					$clusterId = $input->get("cluster_id", 0, "INT");

					if ($clusterId)
					{
						$masterUcmLink .= (strpos($masterUcmLink, '?')) ? '&cluster_id=' . $clusterId : '?cluster_id=' . $clusterId;
					}
				}

				$html .= "<div><a target='_blank' href='" . $masterUcmLink . "'>" . Text::_("COM_TJFIELDS_FORM_DESC_FIELD_RELATED_ADD_RECORD") . "</a></div>";
			}
		}

		if ($fieldParams['showAddNewRecordLink'] && $this->id && $fieldTable->id)
		{
			$clusterId = $input->get("cluster_id", 0, "INT");
			$document  = Factory::getDocument();

			$document->addScript(Uri::root() . 'media/com_tjucm/js/ui/itemform.min.js');

			$document->addScriptDeclaration('jQuery(document).ready(function() {
				jQuery("#' . $this->id . '_chzn").click(function(){
					tjUcmItemForm.getRelatedFieldOptions("' . $this->id . '", "' . $fieldTable->id . '", "' . $clusterId . '");
				});
			});');
		}

		return $html;
	}
}
