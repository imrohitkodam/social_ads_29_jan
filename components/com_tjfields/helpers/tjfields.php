<?php
/**
 * @version    SVN: <svn_id>
 * @package    Tjfields
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2018 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Uri\Uri;
JLoader::import("/techjoomla/media/storage/local", JPATH_LIBRARIES);
use Joomla\Registry\Registry;
use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;

/**
 * Helper class for tjfields
 *
 * @package     Tjfields
 * @subpackage  com_tjfields
 * @since       2.2
 */
class TjfieldsHelper
{
	/**
	 * htaccess file content (used to restrict direct access of media file)
	 *
	 * @var    mixed
	 * @since  1.4
	 */
	protected $htaccessFileContent = '<FilesMatch ".*">
    Order Allow,Deny
    Deny from All
</FilesMatch>';

	protected $htaccess = '.htaccess';

	/**
	 * Function used for renderring. fetching value
	 *
	 * @param   array  $data  get data
	 *
	 * @return  array
	 */
	public function FetchDatavalue($data)
	{
		$content_id        = $data['content_id'];
		$client            = $data['client'];
		$query_user_string = '';

		$db    = Factory::getDbo();
		$query = $db->getQuery(true);
		$query->select('fv.field_id, fv.user_id, f.type, fv.value, f.params, f.name, f.label');
		$query->from($db->qn('#__tjfields_fields_value', 'fv'));
		$query->join('INNER', $db->qn('#__tjfields_fields', 'f') . ' ON (' .
		$db->qn('f.id') . ' = ' . $db->qn('fv.field_id') . ')');

		$query->where('fv.content_id=' . $content_id);
		$query->where('fv.client="' . $client . '" ' . $query_user_string);
		$query->where('f.state=' . $db->quote("1"));
		$db->setQuery($query);

		$field_data_value = $db->loadObjectlist();

		$fieldDataValue = array();

		foreach ($field_data_value as $k => $data)
		{
			$fieldParams = json_decode($data->params);
			$multipleValueField = (isset($fieldParams->multiple) && !empty($fieldParams->multiple)) ? 1 : 0;
			$fieldDataValue[$data->field_id] = new stdclass;

			if ($data->type == "radio" || $data->type == "single_select")
			{
				$fieldDataValue[$data->field_id]->value[] = $data->value;
				$fieldDataValue[$data->field_id]->field_id = $data->field_id;
			}
			elseif ($data->type == "multi_select" || (($data->type == "related" || $data->type == "tjlist") && $multipleValueField))
			{
				$fieldDataValue[$data->field_id]->value[] = $data->value;
				$fieldDataValue[$data->field_id]->field_id = $data->field_id;
			}
			else
			{
				$fieldDataValue[$data->field_id]->value = $data->value;
				$fieldDataValue[$data->field_id]->field_id = $data->field_id;
			}

			// Add field name, label, type, params & user_id
			$fieldDataValue[$data->field_id]->name    = $data->name;
			$fieldDataValue[$data->field_id]->label   = $data->label;
			$fieldDataValue[$data->field_id]->type    = $data->type;
			$fieldDataValue[$data->field_id]->params  = $data->params;
			$fieldDataValue[$data->field_id]->user_id = $data->user_id;
		}

		// Check if the field type is list or radio (fields which have option)
		foreach ($fieldDataValue as $fdata)
		{
			if (!empty($fdata))
			{
				$fieldParams = json_decode($fdata->params);
				$multipleValueField = (isset($fieldParams->multiple) && !empty($fieldParams->multiple)) ? 1 : 0;

				if ($fdata->type == 'single_select' || $fdata->type == 'radio')
				{
					$fdata->value = $this->getOptions($fdata->field_id, json_encode($fdata->value));
				}
				elseif ($fdata->type == "multi_select" || (($fdata->type == "related" || $fdata->type == "tjlist") && $multipleValueField))
				{
					$values = array();

					foreach ($fdata->value as $value)
					{
						$tmp = new stdclass;
						$tmp->value = $value;
						$values[] = $tmp;
					}

					$fdata->value = $values;
				}
			}
		}

		return $fieldDataValue;
	}

	/**
	 * Get field Id and type.
	 *
	 * @param   string  $fname  name of field
	 * @param   string  $fid    id of field
	 *
	 * @return  object
	 */
	public function getFieldData($fname = '', $fid = '')
	{
		$db    = Factory::getDbo();
		$query = $db->getQuery(true);
		$query->select($db->quoteName(array('id', 'type', 'name', 'label', 'params')));
		$query->from($db->quoteName('#__tjfields_fields'));

		if ($fname)
		{
			$query->where($db->quoteName('name') . ' = ' . $db->quote($fname));
		}
		else
		{
			$query->where($db->quoteName('id') . ' = ' . (int) $fid);
		}

		$db->setQuery($query);
		$field_data = $db->loadObject();

		return $field_data;
	}

	/**
	 * Function to store data in fields_value table
	 *
	 * @param   ARRAY  $data  Data to be stored
	 *
	 * @return  boolean
	 */
	public function saveFieldsValue($data)
	{
		if (empty($data['content_id']) || empty($data['client']) || empty($data['fieldsvalue']))
		{
			return false;
		}

		$tjUcmParentContentId = isset($data['parent_content_id']) ? $data['parent_content_id'] : $data['content_id'];
		static $tjUcmSubFormContentId = array('childContentIds' => array());

		// Get user object
		$user = Factory::getUser();

		// Get all the fields of the specified client
		JLoader::import('components.com_tjfields.models.fields', JPATH_ADMINISTRATOR);
		$tjFieldsFieldsModel = BaseDatabaseModel::getInstance('Fields', 'TjfieldsModel', array('ignore_request' => true));
		$tjFieldsFieldsModel->setState('filter.client', $data['client']);
		$tjFieldsFieldsModel->setState('filter.state', 1);
		$fields = $tjFieldsFieldsModel->getItems();

		// Get previously stored details in the record
		$db = Factory::getDbo();
		$query = $db->getQuery(true);
		$query->select('*');
		$query->from($db->quoteName('#__tjfields_fields_value'));
		$query->where($db->quoteName('content_id') . ' = ' . (INT) $data['content_id']);
		$query->where($db->quoteName('client') . ' = ' . $db->quote($data['client']));
		$db->setQuery($query);
		$storedValues = $db->loadAssocList();

		foreach ($data['fieldsvalue'] as $fieldName => $fieldValue)
		{
			$fieldKey = array_search($fieldName, array_column($fields, 'name'));
			$field = $fields[$fieldKey];
			$fieldParams = new Registry($field->params);

			$fieldStoredValuesKeys = array_keys(array_column($storedValues, 'field_id'), $field->id);
			$fieldStoredValues = array();

			foreach ($fieldStoredValuesKeys as $fieldStoredValuesKey)
			{
				$fieldStoredValues[] = $storedValues[$fieldStoredValuesKey];
			}

			// Check if user is authorised to save/edit the field value
			if (empty($storedValues))
			{
				if ($user->authorise('core.field.addfieldvalue', 'com_tjfields.group.' . $field->group_id))
				{
					$authorised = $user->authorise('core.field.addfieldvalue', 'com_tjfields.field.' . $field->id);
				}
			}
			else
			{
				if ($user->authorise('core.field.editfieldvalue', 'com_tjfields.group.' . $field->group_id))
				{
					$authorised = $user->authorise('core.field.editfieldvalue', 'com_tjfields.field.' . $field->id);
				}

				if (!$authorised)
				{
					if ($user->authorise('core.field.editownfieldvalue', 'com_tjfields.group.' . $field->group_id))
					{
						if ($user->authorise('core.field.editownfieldvalue', 'com_tjfields.field.' . $field->id) && ($data['created_by'] == $user->id))
						{
							$authorised = true;
						}
					}
				}
			}

			// If not authorised then return false
			if (empty($authorised))
			{
				continue;
			}

			if ($field->type == 'file' || $field->type == 'image' || $field->type == 'captureimage')
			{
				$this->saveMediaFieldData($fieldValue, $field->client, $data['content_id'], $field->id, $fieldStoredValues);
			}
			elseif ($field->type == 'subform')
			{
				$fieldValue = json_encode($fieldValue);
				$this->saveSingleValuedFieldData($fieldValue, $field->client, $data['content_id'], $field->id, $fieldStoredValues);
			}
			elseif ($field->type == 'ucmsubform' && is_array($fieldValue))
			{
				if (empty($fieldValue))
				{
					continue;
				}

				if (!defined('TJUCM_PARENT_CLIENT'))
				{
					define("TJUCM_PARENT_CLIENT", $data['client']);
				}

				$ucmSubformClientTmp = explode('_', str_replace("com_tjucm_", '', array_key_first($fieldValue[array_key_first($fieldValue)])));
				array_pop($ucmSubformClientTmp);
				$ucmSubformClient = 'com_tjucm.' . implode('_', $ucmSubformClientTmp);

				// Load UCM itemform model
				JLoader::import('components.com_tjucm.models.itemform', JPATH_SITE);

				// Get all the records which were previously stored for the ucmsubform field in parent form
				$db = Factory::getDbo();
				$query = $db->getQuery(true);
				$query->select('id');
				$query->from($db->quoteName('#__tj_ucm_data'));
				$query->where($db->quoteName('parent_id') . '=' . $data['content_id']);
				$query->where($db->quoteName('client') . '=' . $db->quote($ucmSubformClient));
				$db->setQuery($query);
				$ucmSubformRecordIds = $db->loadColumn();
				$ucmSubformRecordIds = (!empty($ucmSubformRecordIds)) ? $ucmSubformRecordIds : array();

				$this->saveSingleValuedFieldData($ucmSubformClient, TJUCM_PARENT_CLIENT, $data['content_id'], $field->id, $fieldStoredValues);

				foreach ($fieldValue as $key => $ucmSubformValue)
				{
					if (!empty($ucmSubformValue))
					{
						$ucmSubformContentIdFieldName = str_replace('.', '_', $ucmSubformClient . "_contentid");
						$ucmSubformContentIdFieldElementId = 'jform_' . $field->name . '__' . $key . '__' . $ucmSubformContentIdFieldName;
						$ucmSubFormContentId = (INT) (isset($ucmSubformValue[$ucmSubformContentIdFieldName])) ? $ucmSubformValue[$ucmSubformContentIdFieldName] : 0;

						if (empty($ucmSubFormContentId))
						{
							$tjUcmSubFormItemData = array('id' => '', 'parent_id' => $data['content_id'], 'client' => $ucmSubformClient, 'created_by' => $data['user_id']);
							$tjUcmItemFormModel = BaseDatabaseModel::getInstance('ItemForm', 'TjucmModel');
							$tjUcmItemFormModel->save($tjUcmSubFormItemData);
							$ucmSubFormContentId = $tjUcmItemFormModel->getState($tjUcmItemFormModel->getName() . '.id');
						}

						if (array_search($ucmSubFormContentId, $ucmSubformRecordIds) !== false)
						{
							unset($ucmSubformRecordIds[array_search($ucmSubFormContentId, $ucmSubformRecordIds)]);
						}

						$tjUcmSubFormContentId['childContentIds'][$ucmSubformContentIdFieldElementId] = (INT) $ucmSubFormContentId;
						$ucmSubFormData = array();
						$ucmSubFormData['parent_content_id'] = $data['content_id'];
						$ucmSubFormData['content_id']        = $ucmSubFormContentId;
						$ucmSubFormData['client']            = $ucmSubformClient;
						$ucmSubFormData['fieldsvalue']       = $ucmSubformValue;
						$ucmSubFormData['created_by']        = Factory::getUser()->id;
						$this->saveFieldsValue($ucmSubFormData);
					}
				}

				// Delete the records which are removed from the ucmsubform
				$tjUcmItemFormModel = BaseDatabaseModel::getInstance('ItemForm', 'TjucmModel');

				foreach ($ucmSubformRecordIds as $ucmSubformRecordId)
				{
					$tjUcmItemFormModel->delete($ucmSubformRecordId);
				}
			}
			elseif ($field->type == 'tjlist')
			{
				// Check for Tjlist - start
				$tjListParams = json_decode($field->params);

				if ($tjListParams->other)
				{
					// Get all the fields of the specified client
					JLoader::import('components.com_tjfields.models.options', JPATH_ADMINISTRATOR);
					$tjFieldOptionsModel = BaseDatabaseModel::getInstance('Options', 'TjfieldsModel', array('ignore_request' => true));
					$tjFieldOptionsModel->setState('filter.field_id', $field->id);
					$optionsValue = $tjFieldOptionsModel->getItems();

					// Get array of dropdown values
					$otherValues = array_column($optionsValue, 'value');
				}

				if (is_array($fieldValue))
				{
					// TODO:- To save data of tjlist in case of auto save - eliminate if possible
					if (strpos($fieldValue[0], ','))
					{
						$tmpFieldValue = $fieldValue;
						$fieldValue = explode(',', $fieldValue[0]);

						if (isset($tmpFieldValue[1]))
						{
							$fieldValue[] = $tmpFieldValue[1];
						}
					}

					if ($tjListParams->other)
					{
						$fieldOtherVal = $fieldOptionsVal = array();
						$postFieldValueCount = count($fieldValue);
						$k = 0;

						$otherValues[] = $field->type . 'othervalue';

						foreach ($fieldValue as $key => $listfieldVale)
						{
							$k++;

							// Update the last index element of other options multi values
							if (in_array($field->type . 'othervalue', $fieldValue) && $postFieldValueCount > 1 &&  $postFieldValueCount == $k)
							{
								$fieldOtherVal = explode(',', $listfieldVale);

								// Add prefix for other values for tjlist field
								$fieldOtherVal = preg_filter('/^/', $field->type . ':-', $fieldOtherVal);
								unset($fieldValue[$key]);
							}
							elseif (!in_array($listfieldVale, $otherValues))
							{
								// Check its other options values not a actual option list values
								$fieldValue[$key] = $field->type . ':-' . $listfieldVale;
							}
							else
							{
								$fieldValue = array_filter(
									$fieldValue,
									function($val)
									{
										return $val != 'tjlistothervalue';
									}
								);
							}
						}

						// Check other options multiple values exist in array
						if (!empty($fieldOtherVal))
						{
							$fieldValue = array_merge($fieldValue, $fieldOtherVal);
						}

						// Check options list value exist in array
						if (!empty($fieldOptionsVal))
						{
							$fieldValue = array_merge($fieldValue, $fieldOptionsVal);
						}
					}

					$this->saveMultiValuedFieldData($fieldValue, $field->client, $data['content_id'], $field->id, $fieldStoredValues);
				}
				else
				{
					// Check other option enable for tjlist field
					if ($tjListParams->other && !in_array($fieldValue, $otherValues))
					{
						// Add prefix for other values for tjlist field
						$fieldValue = $field->type . ':-' . $fieldValue;
					}

					if ($fieldValue == 'tjlist:-tjlistothervalue')
					{
						$fieldValue = 'tjlist:-';
					}

					$this->saveSingleValuedFieldData($fieldValue, $field->client, $data['content_id'], $field->id, $fieldStoredValues);
				}
			}
			elseif ($field->type == 'related' && $fieldParams->get('showParentRecordsOnly', '', 'INT'))
			{
				// This is special case to handle the copy of related fields data in copy item feature
				$jInput = Factory::getApplication()->input;
				$tempId = $jInput->get('id', '', 'STRING');
				$jInput->set('id', $tjUcmParentContentId);

				JLoader::import('components.com_tjfields.models.field', JPATH_ADMINISTRATOR);
				$tjFieldsFieldModel = BaseDatabaseModel::getInstance('Field', 'TjfieldsModel', array('ignore_request' => true));
				$relatedFieldOptions = $tjFieldsFieldModel->getRelatedFieldOptions($field->id);
				$jInput->set('id', $tempId);

				$relatedFieldOptionTmp = array();

				foreach ($relatedFieldOptions as $relatedFieldOption)
				{
					$relatedFieldOptionTmp[$relatedFieldOption['value']] = $relatedFieldOption['text'];
				}

				if (!is_array($fieldValue))
				{
					$fieldValue = array($fieldValue);
				}

				foreach ($fieldValue as $k => $fv)
				{
					if (!empty($fv) && !in_array($fv, $relatedFieldOptionValues))
					{
						$relatedFieldDataSources = $fieldParams->get('fieldName');

						foreach ($relatedFieldDataSources as $relatedFieldDataSource)
						{
							$db = Factory::getDbo();
							$query = $db->getQuery(true);
							$query->select("GROUP_CONCAT(" . $db->quoteName('value') . " SEPARATOR ' ') AS combo_value");
							$query->from($db->quoteName('#__tjfields_fields_value'));
							$query->where($db->quoteName('field_id') . ' IN (' . implode(", ", $relatedFieldDataSource->fieldIds) . ')');
							$query->where($db->quoteName('content_id') . ' = ' . $fv);
							$query->where($db->quoteName('client') . ' = ' . $db->quote($relatedFieldDataSource->client));
							$db->setQuery($query);

							$relatedValue = $db->loadResult();
							$fieldValue[$k] = array_search($relatedValue, $relatedFieldOptionTmp);
						}
					}
				}

				$this->saveMultiValuedFieldData($fieldValue, $field->client, $data['content_id'], $field->id, $fieldStoredValues);
			}
			elseif (is_array($fieldValue))
			{
				// TODO:- To save data of multiselect in case of auto save - eliminate if possible
				if (strpos($fieldValue[0], ','))
				{
					$fieldValue = explode(',', $fieldValue[0]);
				}

				$this->saveMultiValuedFieldData($fieldValue, $field->client, $data['content_id'], $field->id, $fieldStoredValues);
			}
			else
			{
				$this->saveSingleValuedFieldData($fieldValue, $field->client, $data['content_id'], $field->id, $fieldStoredValues);
			}
		}

		if (!empty($tjUcmSubFormContentId['childContentIds']))
		{
			return $tjUcmSubFormContentId;
		}

		return true;
	}

	/**
	 * Function to store data of single valued field in fields_value table
	 *
	 * @param   STRING  $fieldValue         Data to be stored
	 * @param   STRING  $client             Client to which the data belongs
	 * @param   INT     $contentId          Record Id to which the data belongs
	 * @param   INT     $fieldId            Field Id to which the data belongs
	 * @param   ARRAY   $fieldStoredValues  Previously stored value if any
	 *
	 * @return  boolean
	 */
	private function saveSingleValuedFieldData($fieldValue, $client, $contentId, $fieldId, $fieldStoredValues = array())
	{
		if (empty($contentId) || empty($fieldId) || empty($client))
		{
			return false;
		}

		if ($fieldValue == '' && empty($fieldStoredValues))
		{
			return false;
		}

		JLoader::import('components.com_tjfields.tables.fieldsvalue', JPATH_ADMINISTRATOR);
		JLoader::import('components.com_tjfields.tables.option', JPATH_ADMINISTRATOR);
		$fieldsValueTable = Table::getInstance('FieldsValue', 'TjfieldsTable', array('dbo', Factory::getDbo()));
		$fieldOptionTable = Table::getInstance('Option', 'TjfieldsTable', array('dbo', Factory::getDbo()));

		// Set currently logged in users id as user_id
		$fieldsValueTable->user_id = Factory::getUser()->id;

		// If field value already exists then update the value else insert the field value
		if (isset($fieldStoredValues[0]) || !empty($fieldStoredValues[0]))
		{
			if ($fieldsValueTable->bind($fieldStoredValues[0]))
			{
				// If new value for field is not blank then update it else delete it
				if ($fieldValue != '')
				{
					$fieldsValueTable->value = $fieldValue;

					// Set option_id default zero
					$fieldsValueTable->option_id = 0;
					$fieldOptionTable->load(array('field_id' => $fieldId, 'value' => $fieldValue));

					if ($fieldOptionTable->id)
					{
						$fieldsValueTable->option_id = $fieldOptionTable->id;
					}

					if ($fieldsValueTable->store())
					{
						return true;
					}
				}
				else
				{
					if ($fieldsValueTable->delete($fieldStoredValues[0]['id']))
					{
						return true;
					}
				}
			}
		}
		else
		{
			$fieldsValueTable->field_id = $fieldId;
			$fieldsValueTable->content_id = $contentId;
			$fieldsValueTable->value = $fieldValue;
			$fieldsValueTable->client = $client;

			// Set option_id default zero
			$fieldsValueTable->option_id = 0;

			$fieldOptionTable->load(array('field_id' => $fieldId, 'value' => $fieldValue));

			if ($fieldOptionTable->id)
			{
				$fieldsValueTable->option_id = $fieldOptionTable->id;
			}

			if ($fieldsValueTable->store())
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * Function to store data of multi valued field in fields_value table
	 *
	 * @param   ARRAY   $fieldValue         Data to be stored
	 * @param   STRING  $client             Client to which the data belongs
	 * @param   INT     $contentId          Record Id to which the data belongs
	 * @param   INT     $fieldId            Field Id to which the data belongs
	 * @param   ARRAY   $fieldStoredValues  Previously stored value if any
	 *
	 * @return  boolean
	 */
	private function saveMultiValuedFieldData($fieldValue, $client, $contentId, $fieldId, $fieldStoredValues = array())
	{
		if (empty($contentId) || empty($fieldId) || empty($client))
		{
			return false;
		}

		$db = Factory::getDbo();
		$previouslyStoredValues = array();

		if (!empty($fieldStoredValues))
		{
			// Get deleted options
			$previouslyStoredValues = array_column($fieldStoredValues, 'value');
			$valuesToDelete = array();

			foreach ($previouslyStoredValues as $previouslyStoredValue)
			{
				// Get the unselected options
				if (!in_array($previouslyStoredValue, $fieldValue))
				{
					$valuesToDelete[] = $previouslyStoredValue;
				}
			}

			// Delete the unselected options
			if (!empty($valuesToDelete))
			{
				$query = $db->getQuery(true);
				$conditions = array(
					$db->quoteName('value') . ' IN (' . implode(',', $db->quote($valuesToDelete)) . ')',
					$db->quoteName('client') . ' = ' . $db->quote($client),
					$db->quoteName('field_id') . ' = ' . $fieldId,
					$db->quoteName('content_id') . ' = ' . $contentId
				);

				$query->delete($db->quoteName('#__tjfields_fields_value'));
				$query->where($conditions);
				$db->setQuery($query);
				$db->execute();
			}
		}

		// Insert record for newly selected options
		if (!empty($fieldValue))
		{
			foreach ($fieldValue as $value)
			{
				if (!in_array($value, $previouslyStoredValues))
				{
					if (is_array($value))
					{
						$value = implode(',', $value);
					}
					
					$status = $this->saveSingleValuedFieldData($value, $client, $contentId, $fieldId);

					if ($status === false)
					{
						return false;
					}
				}
			}
		}

		return true;
	}

	/**
	 * Function to store data of media field in fields_value table
	 *
	 * @param   ARRAY   $fieldValue         Data to be stored
	 * @param   STRING  $client             Client to which the data belongs
	 * @param   INT     $contentId          Record Id to which the data belongs
	 * @param   INT     $fieldId            Field Id to which the data belongs
	 * @param   ARRAY   $fieldStoredValues  Previously stored value if any
	 *
	 * @return  boolean
	 */
	private function saveMediaFieldData($fieldValue, $client, $contentId, $fieldId, $fieldStoredValues = array())
	{
		if (empty($contentId) || empty($fieldId) || empty($client))
		{
			return false;
		}

		JLoader::import('components.com_tjfields.tables.fieldsvalue', JPATH_ADMINISTRATOR);
		$fieldsValueTable = Table::getInstance('FieldsValue', 'TjfieldsTable', array('dbo', Factory::getDbo()));

		JLoader::import('components.com_tjfields.tables.field', JPATH_ADMINISTRATOR);
		$fieldTable = Table::getInstance('Field', 'TjfieldsTable', array('dbo', Factory::getDbo()));
		$fieldTable->load($fieldId);
		$fieldParams = new Registry($fieldTable->params);

		if (is_array($fieldValue) && empty($fieldValue['name']) && empty($fieldValue['tmp_name']) && empty($fieldValue['size']))
		{
			return false;
		}
		else
		{
			if (strpos($fieldValue, ';base64,'))
			{
				$imageParts = explode(";base64,", $fieldValue);
				$imageBase64 = base64_decode($imageParts[1]);
				$fileName = time() . '_' . uniqid() . '.jpeg';

				$uploadPath = $fieldParams->get('uploadpath', '');
				$mediaPath = ($uploadPath != '') ? $uploadPath : JPATH_SITE . '/' . $fieldTable->type . 's/tjmedia/' . str_replace(".", "/", $client . "/");
				$folderPath = str_replace('/', DIRECTORY_SEPARATOR, $mediaPath);

				// If folder is not present create it
				if (!Folder::exists($folderPath))
				{
					Folder::create($folderPath);
				}

				$file = $folderPath . $fileName;
				file_put_contents($file, $imageBase64);

				return $this->saveSingleValuedFieldData($fileName, $client, $contentId, $fieldId, $fieldStoredValues);
			}

			return true;
		}

		// Get media library object
		$mediaLibObj = TJMediaStorageLocal::getInstance();

		// Configs for Media library
		$config = array();

		// Configure MIME type for media library
		$mimeTypes = $fieldParams->get('accept', '');

		if (!empty($mimeTypes))
		{
			$mimeTypes = explode(',', $mimeTypes);

			$validMineType = array();

			foreach ($mimeTypes as $mimeType)
			{
				$validMineType[] = $mediaLibObj->getMime(strtolower(str_ireplace('.', '', $mimeType)));
			}

			$config['type'] = $validMineType;
		}

		// Configure allowed extensions for media library
		if (!empty($mimeTypes))
		{
			foreach ($mimeTypes as $j => $allowedType)
			{
				$mimeTypes[$j] = trim(str_replace('.', '', $allowedType));
			}

			$config['allowedExtension'] = $mimeTypes;
		}

		// Configure media path for the media library
		$uploadPath = $fieldParams->get('uploadpath', '');
		$mediaPath = ($uploadPath != '') ? $uploadPath : JPATH_SITE . '/' . $fieldTable->type . 's/tjmedia/' . str_replace(".", "/", $client . "/");
		$config['uploadPath'] = str_replace('/', DIRECTORY_SEPARATOR, $mediaPath);

		// Configure size for the media library
		$config['size'] = $fieldParams->get('size');

		// Configure whether to store the media related data in the media table
		$config['saveData'] = '0';

		// Configure if user is authorised to add the media
		$config['auth'] = true;

		$mediaLibObj = TJMediaStorageLocal::getInstance($config);
		$returnData = $mediaLibObj->upload(array($fieldValue));
		$errors = $mediaLibObj->getErrors();

		if (!empty($errors))
		{
			foreach ($errors as $error)
			{
				Factory::getApplication()->enqueueMessage($error, 'error');
			}

			return false;
		}

		// Add htaccess file in the folder where the file is uploaded so that its not accessible through URL
		if ($fieldTable->type == 'file')
		{
			$htaccessFile = $mediaPath . '/' . $this->htaccess;

			if ($fieldParams->get('renderer', 'download') == 'download')
			{
				if (!File::exists($htaccessFile))
				{
					Folder::create(dirname($htaccessFile));
					File::write($htaccessFile, $this->htaccessFileContent);
				}
			}
			else
			{
				if (File::exists($htaccessFile))
				{
					// Rename the .htaccess file if the renderer is preview
					File::move($htaccessFile, $htaccessFile . '.txt');
				}
			}
		}

		// Add/Update value of the file field in fields_value table
		$fieldValue = $returnData[0]['source'];

		return $this->saveSingleValuedFieldData($fieldValue, $client, $contentId, $fieldId, $fieldStoredValues);
	}

	/**
	 * Save fields.
	 *
	 * @param   array  $data  Post array which content (client, content_id, Fname, Fvalue, u_id)
	 *
	 * @return  bool  Returns true if successful, and false otherwise.
	 */
	public function saveFieldsValueTOBEDELETED($data)
	{
		if (empty($data))
		{
			return false;
		}

		$app = Factory::getApplication();

		// Get field Id and field type.
		$insert_obj = new stdClass;
		$insert_obj->content_id = $data['content_id'];
		$insert_obj->user_id    = $data['user_id'];
		$insert_obj->email_id   = '';
		$insert_obj->client     = $data['client'];

		$insert_obj_file = new stdClass;
		$insert_obj_file->content_id = $data['content_id'];
		$insert_obj_file->user_id    = $data['user_id'];
		$insert_obj_file->email_id   = '';
		$insert_obj_file->client     = $data['client'];

		$singleSelectionFields = array('single_select', 'radio');
		$multipleSelectionFields = array('multi_select');
		$fieldsSubmitted = array();

		// Separating out the subform files data from files array
		foreach ($data['fieldsvalue'] as $k => $v)
		{
			$field_data = $this->getFieldData($k);

			if (isset($field_data->type) && ($field_data->type === 'subform' || $field_data->type === 'ucmsubform'))
			{
				$fileData = array();

				if (isset($data['fieldsvalue']['tjFieldFileField']))
				{
					foreach ($data['fieldsvalue']['tjFieldFileField'] as $key => $value)
					{
						// Checking if the subform name is present as key of array in the files array, if present separate  the array
						if ($key === $field_data->name)
						{
							$fileData[$key] = $value;

							unset($data['fieldsvalue']['tjFieldFileField'][$key]);
						}
						else
						{
							$fileData[$key] = '';
						}
					}
				}

				// Adding separated files array to respective subform data  by creating new variable filesData
				if (!empty($v))
				{
					foreach ($v as $key => $value)
					{
						if (isset($fileData[$k]))
						{
							if (array_key_exists($key, $fileData[$k]))
							{
								$data['fieldsvalue'][$field_data->name][$key]['filesData'] = $fileData[$k][$key];
							}
							else
							{
								$data['fieldsvalue'][$field_data->name][$key]['filesData'] = '';
							}
						}
					}
				}
			}
		}

		// Values array will contain menu fields value.
		foreach ($data['fieldsvalue'] as $fname => $fvalue)
		{
			$field_data = array();

			if ($fname != 'tjFieldFileField')
			{
				$field_data = $this->getFieldData($fname);
				$insert_obj->field_id = $field_data->id;
				$fieldsSubmitted[] = $insert_obj->field_id;
			}

			// Field Data
			$db    = Factory::getDbo();
			$query = $db->getQuery(true);
			$query->select('*');
			$query->from($db->quoteName('#__tjfields_fields'));
			$query->where($db->quoteName('name') . ' = ' . $db->quote($fname));
			$db->setQuery($query);
			$file_field_data_check = $db->loadObject();

			if ($fname == 'tjFieldFileField')
			{
				foreach ($fvalue as $fieldName => $singleFile)
				{
					$file_field_data = $this->getFieldData($fieldName);
					$insert_obj_file->field_id = $file_field_data->id;

					if ($file_field_data->id)
					{
						if (!empty($singleFile))
						{
							if ($singleFile['error'] != 4)
							{
								Table::addIncludePath(JPATH_ADMINISTRATOR . "/components/com_tjfields/tables");
								BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . "/components/com_tjfields/models");
								$fieldModel = BaseDatabaseModel::getInstance('Field', 'TjfieldsModel', array("ignore_request" => 1));

								$fieldId = (int) $file_field_data->id;
								$fieldItems = $fieldModel->getItem($fieldId);
								$client = $fieldItems->client;
								$type = $fieldItems->type;

								$uploadPath = "";

								if (isset($fieldItems->params['uploadpath']))
								{
									$uploadPath = $fieldItems->params['uploadpath'];
								}

								// Code for file size validation
								$acceptSize = $fieldItems->params['size'];

								// Upload path
								$mediaPath = ($uploadPath != '') ? $uploadPath : JPATH_SITE . '/' . $type . 's/tjmedia/' . str_replace(".", "/", $client . "/");
								$mediaPath = str_replace('/', DIRECTORY_SEPARATOR, $mediaPath);

								// Code for file type validation
								$acceptType = $fieldItems->params['accept'];

								// Configs for Media library
								$config = array();

								if (!empty($acceptType))
								{
									$localMime = TJMediaStorageLocal::getInstance();

									$validMIMEArray = explode(',', $acceptType);

									$validtype = array();

									foreach ($validMIMEArray as $mimeType)
									{
										$validtype[] = $localMime->getMime(strtolower(str_ireplace('.', '', $mimeType)));
									}

									$config['type'] = $validtype;
								}

								if (isset($fieldItems->params['accept']) && !empty($fieldItems->params['accept']))
								{
									$allowedTypes = explode(',', $fieldItems->params['accept']);

									foreach ($allowedTypes as $j => $allowedType)
									{
										$allowedTypes[$j] = trim(str_replace('.', '', $allowedType));
									}

									$config['allowedExtension'] = $allowedTypes;
								}

								$user = Factory::getUser();
								$config['uploadPath'] = $mediaPath;
								$config['size'] = $acceptSize;
								$config['saveData'] = '0';
								$config['auth'] = $user->authorise('core.field.addfieldvalue', 'com_tjfields.field.' . $file_field_data->id);
								$media = TJMediaStorageLocal::getInstance($config);
								$returnData = $media->upload(array($singleFile));
								$errors = $media->getErrors();

								if (!empty($errors))
								{
									foreach ($errors as $error)
									{
										$app->enqueueMessage($error, 'error');
									}
								}

								if ($file_field_data->type == 'file')
								{
									$htaccessFile = $mediaPath . '/' . $this->htaccess;

									// If the destination directory doesn't exist we need to create it
									jimport('joomla.filesystem.file');

									if (!File::exists($htaccessFile))
									{
										jimport('joomla.filesystem.folder');
										Folder::create(dirname($htaccessFile));
										File::write($htaccessFile, $this->htaccessFileContent);
									}
								}

								if ($returnData[0]['source'])
								{
									$existingFileRecordId = $this->checkRecordExistence($data, $file_field_data->id);

									$insert_obj_file->value = $returnData[0]['source'];

									if ($insert_obj_file->value)
									{
										if (!empty($existingFileRecordId))
										{
											$insert_obj_file->id = $existingFileRecordId;
											$db->updateObject('#__tjfields_fields_value', $insert_obj_file, 'id');
										}
										else
										{
											$insert_obj_file->id = '';
											$db->insertObject('#__tjfields_fields_value', $insert_obj_file, 'id');
										}
									}

									$fieldsSubmitted[] = $insert_obj_file->field_id;
								}
								else
								{
									return false;
								}
							}
						}
					}
				}
			}
			else
			{
				if (empty($file_field_data_check->accept))
				{
					// Check for duplicate entry
					$existingRecordId = $this->checkRecordExistence($data, $field_data->id);

					if (isset($fvalue) && $fvalue !== '')
					{
						$fieldParams = json_decode($field_data->params);
						$multipleValueField = (isset($fieldParams->multiple) && !empty($fieldParams->multiple)) ? 1 : 0;

						if ($field_data->type === 'subform')
						{
							$this->saveSubformData($data, $fname, $field_data);
						}
						elseif (in_array($field_data->type, $multipleSelectionFields)
							|| (($field_data->type == 'related' || $field_data->type == 'tjlist') && $multipleValueField))
						{
							$this->saveMultiselectOptions($data, $fname, $field_data);
						}
						elseif (in_array($field_data->type, $singleSelectionFields) || ($field_data->type == 'tjlist' && !$multipleValueField))
						{
							$this->saveSingleSelectFieldValue($data, $fname, $field_data, $existingRecordId);
						}
						else
						{
							$insert_obj->value = $fvalue;

							if (!empty($existingRecordId))
							{
								$insert_obj->id = $existingRecordId;
								$db->updateObject('#__tjfields_fields_value', $insert_obj, 'id');
							}
							else
							{
								$insert_obj->id = '';
								$db->insertObject('#__tjfields_fields_value', $insert_obj, 'id');
							}
						}
					}
					else
					{
						if (isset($field_data->id) && isset($data['content_id']))
						{
							// Delete entry is field is deselected
							$conditions = array(
								$db->quoteName('field_id') . ' = ' . $field_data->id,
								$db->quoteName('content_id') . ' = ' . (int) $data['content_id'],
								$db->quoteName('client') . " = " . $db->quote($data['client'])
							);
							$query = $db->getQuery(true);
							$query->delete($db->quoteName('#__tjfields_fields_value'));
							$query->where($conditions);
							$db->setQuery($query);
							$db->execute();
						}
					}
				}
			}
		}

		$fieldsSubmitted = array_filter($fieldsSubmitted);
		$unsubmittedFields = $this->getUnsubmittedFields($data['content_id'], $data['client'], $fieldsSubmitted);

		// Delete Values of unsubmitted fields
		foreach ($unsubmittedFields as $unsubmittedField)
		{
			$db = Factory::getDbo();

			// Delete entry if field is deselected
			$conditions = array(
				$db->quoteName('field_id') . ' = ' . $unsubmittedField,
				$db->quoteName('content_id') . ' = ' . (int) $data['content_id'],
				$db->quoteName('client') . " = " . $db->quote($data['client'])
			);
			$query = $db->getQuery(true);
			$query->delete($db->quoteName('#__tjfields_fields_value'));
			$query->where($conditions);
			$db->setQuery($query);
			$db->execute();
		}

		return true;
	}

	/**
	 * Function to get sunsubmitted fields value
	 *
	 * @param   INT     $content_id       content id
	 * @param   STRING  $client           client
	 * @param   ARRAY   $fieldsSubmitted  array of fields submitted
	 *
	 * @return  ARRAY|boolean
	 */
	public function getUnsubmittedFields($content_id, $client, $fieldsSubmitted)
	{
		if (!empty($content_id) && !empty($client))
		{
			// Field Data
			$db    = Factory::getDbo();
			$query = $db->getQuery(true);
			$query->select($db->quoteName('field_id'));
			$query->from($db->quoteName('#__tjfields_fields_value'));
			$query->where($db->quoteName('content_id') . " = " . (int) $content_id);
			$query->where($db->quoteName('client') . " = " . $db->quote($client));
			$db->setQuery($query);
			$dataSavedFields = $db->loadColumn();

			$unsubmittedFields = array_diff($dataSavedFields, $fieldsSubmitted);

			return $unsubmittedFields;
		}
		else
		{
			return false;
		}
	}

	/**
	 * check if the fields values are already store. so it means we need to edit the entry
	 *
	 * @param   array  $postFieldData  Post array which content (client, content_id, Fname, Fvalue, u_id)
	 * @param   array  $fieldName      Current multiselect field name
	 * @param   array  $field_data     field data
	 * @param   array  $updateId       Previous record id
	 *
	 * @return  array
	 */
	public function saveSingleSelectFieldValue($postFieldData, $fieldName, $field_data, $updateId = 0)
	{
		$currentFieldValue = $postFieldData['fieldsvalue'][$fieldName];
		$db    = Factory::getDbo();
		$query = $db->getQuery(true);

		$query->select("id")
		->from("#__tjfields_options")
		->where($db->quoteName('field_id') . " = " . (int) $field_data->id)
		->where($db->quoteName('value') . " = " . $db->quote($currentFieldValue));
		$db->setQuery($query);

		$option_id = $db->loadResult();

		// Save field value
		$insert_obj = new stdClass;
		$insert_obj->field_id = $field_data->id;

		$insert_obj->content_id = $postFieldData['content_id'];
		$insert_obj->user_id    = $postFieldData['user_id'];
		$insert_obj->email_id   = '';
		$insert_obj->client     = $postFieldData['client'];
		$insert_obj->value = $currentFieldValue;
		$insert_obj->option_id = $option_id;

		if ($updateId)
		{
			$insert_obj->id = $updateId;
			$db->updateObject('#__tjfields_fields_value', $insert_obj, 'id');
		}
		else
		{
			$insert_obj->id = '';
			$db->insertObject('#__tjfields_fields_value', $insert_obj, 'id');
		}
	}

	/**
	 * check if the fields values are already store. so it means we need to edit the entry
	 *
	 * @param   array  $postFieldData  Post array which content (client, content_id, Fname, Fvalue, u_id)
	 * @param   array  $subformFname   Current subform field name
	 * @param   array  $field_data     field data
	 *
	 * @return  true
	 */
	public function saveSubformData($postFieldData, $subformFname, $field_data)
	{
		// Select all entries for __tjfields_fields_value
		$app = Factory::getApplication();
		$db    = Factory::getDbo();
		$query = $db->getQuery(true);
		$query->select('*');
		$query->from('#__tjfields_fields_value');
		$query->where($db->quoteName('content_id') . "=" . (int) $postFieldData['content_id']);
		$query->where($db->quoteName('field_id') . "=" . (int) $field_data->id);
		$query->where($db->quoteName('client') . "=" . $db->quote($postFieldData['client']));
		$db->setQuery($query);
		$dbFieldValue = $db->loadObjectList();

		$newFields = $postFieldData['fieldsvalue'];
		$subformField = $newFields[$subformFname];

		// Params from getting subform max size
		Table::addIncludePath(JPATH_ADMINISTRATOR . "/components/com_tjfields/tables");
		BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . "/components/com_tjfields/models");
		$fieldModel = BaseDatabaseModel::getInstance('Field', 'TjfieldsModel', array("ignore_request" => 1));

		$fieldId = (int) $field_data->id;
		$fieldItems = $fieldModel->getItem($fieldId);

		// Code for subform max size validation
		$acceptmaxSize = $fieldItems->params['max'];

		if (count($subformField) > $acceptmaxSize)
		{
			return false;
		}

		foreach ($subformField as $key => $value)
		{
			if (!empty($value['filesData']))
			{
				foreach ($value['filesData'] as $k => $v)
				{
					if (!empty($v['name']))
					{
						$file_field_data = $this->getFieldData($k);

						if (!empty($file_field_data))
						{
							BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . "/components/com_tjfields/models");
							$fieldModel = BaseDatabaseModel::getInstance('Field', 'TjfieldsModel', array("ignore_request" => 1));
							$fieldId = (int) $file_field_data->id;
							$fieldItems = $fieldModel->getItem($fieldId);
							$client = $fieldItems->client;
							$type = $fieldItems->type;

							$uploadPath = "";

							if (isset($fieldItems->params['uploadpath']))
							{
								$uploadPath = $fieldItems->params['uploadpath'];
							}

							// Code for file type validation
							$acceptType = $fieldItems->params['accept'];
						}

						// Code for file size validation
						$acceptSize = $fieldItems->params['size'];

						// Upload path
						$mediaPath = ($uploadPath != '') ? $uploadPath : JPATH_SITE . '/' . $type . 's/tjmedia/' . str_replace(".", "/", $client . "/");
						$mediaPath = str_replace('/', DIRECTORY_SEPARATOR, $mediaPath);

						// Configs for Media library
						$config = array();

						if (!empty($acceptType))
						{
							$validMIMEArray = explode(',', $acceptType);

							$validtype = array();

							foreach ($validMIMEArray as $mimeType)
							{
								$localGetMime = TJMediaStorageLocal::getInstance();
								$validtype[] = $localGetMime->getMime(strtolower(str_ireplace('.', '', $mimeType)));
							}

							$config['type'] = $validtype;
						}

						if (isset($fieldItems->params['accept']) && !empty($fieldItems->params['accept']))
						{
							$allowedTypes = explode(',', $fieldItems->params['accept']);

							foreach ($allowedTypes as $j => $allowedType)
							{
								$allowedTypes[$j] = trim(str_replace('.', '', $allowedType));
							}

							$config['allowedExtension'] = $allowedTypes;
						}

						$user = Factory::getUser();
						$config['uploadPath'] = $mediaPath;
						$config['size'] = $acceptSize;
						$config['saveData'] = '0';
						$config['auth'] = $user->authorise('core.field.addfieldvalue', 'com_tjfields.field.' . $file_field_data->id);

						$media = TJMediaStorageLocal::getInstance($config);

						$returnData = $media->upload(array($v));
						$errors = $media->getErrors();

						if (!empty($errors))
						{
							foreach ($errors as $error)
							{
								$app->enqueueMessage($error, 'error');
							}
						}

						$subformField[$key][$k] = $returnData[0]['source'];

						if ($file_field_data->type == 'file')
						{
							$htaccessFile = $mediaPath . '/' . $this->htaccess;

							// If the destination directory doesn't exist we need to create it
							jimport('joomla.filesystem.file');

							if (!File::exists($htaccessFile))
							{
								jimport('joomla.filesystem.folder');
								Folder::create(dirname($htaccessFile));
								File::write($htaccessFile, $this->htaccessFileContent);
							}
						}

						unset($subformField[$key]['filesData']);
					}
				}
			}
		}

		if (!empty($dbFieldValue))
		{
			if (!empty($subformField))
			{
				$obj = new stdClass;
				$obj->field_id = $field_data->id;
				$obj->content_id = $postFieldData['content_id'];
				$obj->value = json_encode($subformField);
				$obj->client = $postFieldData['client'];
				$obj->user_id = Factory::getUser()->id;

				$obj->id = $dbFieldValue[0]->id;
				$db->updateObject('#__tjfields_fields_value', $obj, 'id');
			}
		}
		else
		{
			$obj = new stdClass;
			$obj->field_id = $field_data->id;
			$obj->content_id = $postFieldData['content_id'];
			$obj->value = json_encode($subformField);
			$obj->client = $postFieldData['client'];
			$obj->user_id = Factory::getUser()->id;

			$db = Factory::getDbo();
			$db->insertObject('#__tjfields_fields_value', $obj, 'id');
		}

		return true;
	}

	/**
	 * check if the fields values are already store. so it means we need to edit the entry
	 *
	 * @param   array  $postFieldData     Post array which content (client, content_id, Fname, Fvalue, u_id)
	 * @param   array  $multiselectFname  Current multiselect field name
	 * @param   array  $field_data        field data
	 *
	 * @return  true
	 */
	public function saveMultiselectOptions($postFieldData, $multiselectFname, $field_data)
	{
		// Select all entries for __tjfields_fields_value
		$db    = Factory::getDbo();
		$query = $db->getQuery(true);
		$query->select('*');
		$query->from('#__tjfields_fields_value');
		$query->where($db->quoteName('content_id') . "=" . (int) $postFieldData['content_id']);
		$query->where($db->quoteName('field_id') . "=" . (int) $field_data->id);
		$query->where($db->quoteName('client') . "=" . $db->quote($postFieldData['client']));
		$db->setQuery($query);
		$dbFieldValue = $db->loadObjectList("id");

		$newFields = $postFieldData['fieldsvalue'];
		$multiselectField = $newFields[$multiselectFname];

		// Check for Tjlist - Remove Other option before save
		if ($field_data->type == 'tjlist')
		{
			$tjListParams = json_decode($field_data->params);

			if ($tjListParams->multiple && $tjListParams->other)
			{
				$otherValKey = array_search(Text::_('COM_TJFIELDS_TJLIST_OTHER_OPTION_VALUE'), $multiselectField);

				if (is_numeric($otherValKey))
				{
					unset($multiselectField[$otherValKey]);
					$multiselectField = array_values($multiselectField);

					// Search for blank values
					$blankVal = array_search('', $multiselectField);

					if (is_numeric($blankVal))
					{
						unset($multiselectField[$blankVal]);
						$multiselectField = array_values($multiselectField);
					}
				}
			}
		}

		if (!empty($dbFieldValue))
		{
			// Check for update
			foreach ($dbFieldValue as $key => $dbField)
			{
				// Current field is present then remove from both list
				if (in_array($dbField->value, $multiselectField))
				{
					unset($dbFieldValue[$key]);
					$multiselectField = array_diff($multiselectField, array($dbField->value));
				}
			}

			// Now $dbFieldValue contains fields to delete. newField contain field to insert
			if (!empty($dbFieldValue))
			{
				$delFieldValueIdsArray = array_keys($dbFieldValue);
				$delFieldValueIds = implode(',', $delFieldValueIdsArray);

				$this->deleteFieldValueEntry($delFieldValueIds);
			}

			if (!empty($multiselectField))
			{
				foreach ($multiselectField as $fieldValue)
				{
					$obj = new stdClass;
					$obj->field_id = $field_data->id;
					$obj->content_id = $postFieldData['content_id'];
					$obj->value = $fieldValue;
					$obj->client = $postFieldData['client'];
					$obj->user_id = Factory::getUser()->id;

					$this->addFieldValueEntry($obj);
				}
			}
		}
		else
		{
			// New: add all options
			foreach ($multiselectField as $fieldValue)
			{
				$obj = new stdClass;
				$obj->field_id = $field_data->id;
				$obj->content_id = $postFieldData['content_id'];
				$obj->value = $fieldValue;
				$obj->client = $postFieldData['client'];
				$obj->user_id = Factory::getUser()->id;

				$this->addFieldValueEntry($obj);
			}
		}

		return true;
	}

	/**
	 * check if the fields values are already store. so it means we need to edit the entry
	 *
	 * @param   object  $insert_obj  Partially created object.
	 *
	 * @return  array
	 */
	public function addFieldValueEntry($insert_obj)
	{
		if (!empty($insert_obj))
		{
			$db = Factory::getDbo();
			$query = $db->getQuery(true);

			$query->select($db->quoteName('id'))
			->from($db->quoteName('#__tjfields_options'))
			->where($db->quoteName('field_id') . " = " . (int) $insert_obj->field_id)
			->where($db->quoteName('value') . " = " . $db->quote($insert_obj->value));
			$db->setQuery($query);
			$insert_obj->option_id = $db->loadResult();

			$fieldData = $this->getFieldData('', $insert_obj->field_id);

			if (!empty($insert_obj->option_id) || $fieldData->type == 'related' || $fieldData->type == 'tjlist')
			{
				// Insert into db
				$db = Factory::getDbo();
				$db->insertObject('#__tjfields_fields_value', $insert_obj, 'id');
			}
		}
	}

	/**
	 * Function to build safe query for IN clause
	 *
	 * @param   string  $filterString  filter string
	 *
	 * @return  string
	 */
	public function buildSafeInClause($filterString)
	{
		$db = Factory::getDbo();

		// Check if $filterString is comma separated string.
		if ((strpos($filterString, ',') !== false))
		{
			// Remove single and double quotes from string.
			$filterString = str_replace(array('\'', '"'), '', $filterString);

			// Create an array of string.
			$filterArray  = explode(',', $filterString);

			// Joomla $db->quote every element of array.
			$filterArray  = array_map(array($db, 'quote'), $filterArray);

			// Create safe string of array.
			$filterString = implode(',', $filterArray);
		}
		else
		{
			// Remove single and double quotes from string.
			$filterString = str_replace(array('\'', '"'), '', $filterString);

			// Joomla $db->quote $filterString.
			$filterString = $db->quote($filterString);
		}

		return $filterString;
	}

	/**
	 * check if the fields values are already store. so it means we need to edit the entry
	 *
	 * @param   array  $fieldValueEntryId  Ids to delete the entries from table #__tjfields_fields_value
	 *
	 * @return  array
	 */
	public function deleteFieldValueEntry($fieldValueEntryId)
	{
		if (!empty($fieldValueEntryId))
		{
			$db = Factory::getDbo();

			$query = $db->getQuery(true);

			$fieldValueEntryId = $this->buildSafeInClause($fieldValueEntryId);

			$conditions = array(
				$db->quoteName('id') . ' IN (' . $fieldValueEntryId . ') '
			);

			$query->delete($db->quoteName('#__tjfields_fields_value'));
			$query->where($conditions);
			$db->setQuery($query);
			$db->execute();
		}
	}

	/**
	 * check if the fields values are already store. so it means we need to edit the entry
	 *
	 * @param   array  $data      Post array which content (client, content_id, Fname, Fvalue, u_id)
	 * @param   array  $field_id  id of field
	 *
	 * @return  array
	 */
	public function checkRecordExistence($data, $field_id)
	{
		$content_id = (int) $data['content_id'];
		$client     = $data['client'];

		$db    = Factory::getDbo();
		$query = $db->getQuery(true);

		$query->select($db->quoteName('id'));
		$query->from($db->quoteName('#__tjfields_fields_value'));
		$query->where($db->quoteName('content_id') . ' = ' . (int) $content_id);
		$query->where($db->quoteName('client') . ' = ' . $db->quote($client));

		if (!empty($field_id))
		{
			$query->where($db->quoteName('field_id') . ' = ' . (int) $field_id);
		}

		$db->setQuery($query);
		$existingRecordId = $db->loadresult();

		return $existingRecordId;
	}

	/**
	 * Get option which are stored in field option table.
	 *
	 * @param   string  $field_id      field if
	 * @param   string  $option_value  option value
	 *
	 * @return array Option for the particular field
	 */
	public function getOptions($field_id, $option_value = '')
	{
		$db    = Factory::getDbo();
		$query = $db->getQuery(true);

		$query->select($db->quoteName(array('options','value')));
		$query->from($db->quoteName('#__tjfields_options'));
		$query->where($db->quoteName('field_id') . ' = ' . (int) $field_id);

		if ($option_value != '')
		{
			$new_option_value = json_decode($option_value);

			if ($new_option_value != '')
			{
				if (is_array($new_option_value))
				{
					$option_value_string = implode(",", $db->quote($new_option_value));
					$query->where($db->quoteName('value') . 'IN (' . $option_value_string . ')');
				}
				else
				{
					$query->where($db->quoteName('value') . '=' . $db->quote($new_option_value));
				}
			}
			else
			{
				// Radio.
				$query->where($db->quoteName('value') . '=' . $db->quote($option_value));
			}

			$db->setQuery($query);
			$extra_options = $db->loadObjectlist();

			$fieldData = $this->getFieldData('', $field_id);

			if (!empty($fieldData))
			{
				// Check for Tjlist - start
				$tjListParams = json_decode($fieldData->params);

				if ($fieldData->type == 'tjlist' && $tjListParams->other)
				{
					$allValues = $new_option_value;

					// Get array of dropdown values
					$otherValues = array_column($extra_options, 'value');

					// For single select value, convert string into array
					if (!is_array($allValues))
					{
						$allValues = array ();
						$allValues[] = $new_option_value;
					}

					// Get other/extra values that are not present in dropdown list
					$otherValues = array_diff($allValues, $otherValues);

					$otherValues = array_values($otherValues);

					if (!empty($otherValues))
					{
						foreach ($otherValues as $othervalue)
						{
							$tjListOtherObj = new stdClass;
							$tjListOtherObj->default_option = '';
							$tjListOtherObj->options = $othervalue;
							$tjListOtherObj->value = $othervalue;
							$extra_options[] = $tjListOtherObj;
						}
					}
				}

				// Check for Tjlist - end
			}
		}
		else
		{
			$db->setQuery($query);
			$extra_options = $db->loadObjectlist();
		}

		return $extra_options;
	}

	/**
	 * Get option which are stored in field option table.
	 *
	 * @param   string  $client  Get all fields based on client
	 *
	 * @return array|string
	 */
	public function getUniversalFields($client)
	{
		$universalFields = "";

		if (!empty($client))
		{
			$db    = Factory::getDbo();
			$query = $db->getQuery(true);
			$query->select('DISTINCT * FROM #__tjfields_fields AS f');
			$query->where('NOT EXISTS (select * FROM #__tjfields_category_mapping AS cm where f.id=cm.field_id)');
			$query->where($db->quoteName('f.client') . "=" . $db->quote($client));
			$query->where($db->quoteName('f.state') . " = 1");
			$query->order($db->quoteName('f.ordering'));
			$db->setQuery($query);
			$universalFields = $db->loadObjectlist();
		}

		return $universalFields;
	}

	/**
	 * Get option which are stored in field option table.
	 *
	 * @param   string  $client  Get all fields based on client
	 * @param   int  $categoryId  Get all fields based on category
	 *
	 * @return array|string
	 */
	public function getCategoryFields($client, $categoryId)
	{
		$universalFields = "";

		if (!empty($client) && !empty($categoryId))
		{
			$db    = Factory::getDbo();
			$query = $db->getQuery(true);
			$query->select(array('f.*'));
			$query->from($db->quoteName('#__tjfields_fields', 'f'));
			$query->join('LEFT', $db->quoteName('#__tjfields_category_mapping', 'mapping') . 'ON (' . $db->quoteName('mapping.field_id') . '=' . $db->quoteName('f.id') . ')');
			$query->where($db->quoteName('mapping.category_id') . "=" . (int) $categoryId);
			$query->where($db->quoteName('f.client') . "=" . $db->quote($client));
			$query->where($db->quoteName('f.state') . " = 1");
			$query->order($db->quoteName('f.ordering'));
			$db->setQuery($query);
			$universalFields = $db->loadObjectlist();
		}

		return $universalFields;
	}

	/**
	 * Get option which are stored in field option table.
	 *
	 * @param   string  $client  Get all fields based on client
	 *
	 * @return object
	 */
	public function getCategorys($client)
	{
		$db    = Factory::getDbo();
		$query = $db->getQuery(true);

		$query->select('*');
		$query->from($db->quoteName('#__categories'));
		$query->where($db->quoteName('extension') . ' = ' . $db->quote($client));
		$query->where($db->quoteName('published') . ' = 1');

		$db->setQuery($query);
		$categorysList = $db->loadObjectlist();

		return $categorysList;
	}

	/**
	 * Get option which are stored in field option table.
	 *
	 * @param   string  $client       Get all fields based on client
	 * @param   string  $category_id  Get all fields for selected category
	 *
	 * @return object
	 */
	public function getFilterableFields($client, $category_id = '')
	{
		$coreFields = '';

		if (!empty($client))
		{
			$db    = Factory::getDbo();
			$query = $db->getQuery(true);
			$query->select('DISTINCT fv.option_id, f.id,f.name, f.label,fv.value,fo.options,f.type');
			$query->FROM("#__tjfields_fields AS f");
			$query->JOIN('INNER', '#__tjfields_fields_value AS fv ON fv.field_id = f.id');
			$query->JOIN('INNER', '#__tjfields_options AS fo ON fo.id = fv.option_id');

			$query->where($db->quoteName('f.client') . "=" . $db->quote($client));
			$query->where($db->quoteName('f.filterable') . " = 1");
			$query->where($db->quoteName('f.state') . " = 1");
			$query->where('fv.option_id IS NOT NULL');
			$query->where("f.type IN ('single_select', 'multi_select', 'radio', 'tjlist')");

			// Doesn't have mapped any category
			$query->where('NOT EXISTS (select * FROM #__tjfields_category_mapping AS cm where f.id=cm.field_id)');

			$query->order('f.ordering');
			$db->setQuery($query);
			$coreFields = $db->loadObjectlist("option_id");
			$allFields = $coreFields;

			// Type cast value of category
			$category_id = (int) $category_id;

			// If category related field present
			if (!empty($category_id) && is_int($category_id))
			{
				$db    = Factory::getDbo();
				$queryCat = $db->getQuery(true);
				$queryCat->select('DISTINCT fv.option_id, f.id,f.name, f.label,fv.value,fo.options,f.type');
				$queryCat->FROM("#__tjfields_fields AS f");
				$queryCat->JOIN('INNER', '#__tjfields_fields_value AS fv ON fv.field_id = f.id');
				$queryCat->JOIN('INNER', '#__tjfields_options AS fo ON fo.id = fv.option_id');

				$queryCat->where($db->quoteName('f.client') . "=" . $db->quote($client));
				$queryCat->where($db->quoteName('f.filterable') . " = 1");
				$queryCat->where($db->quoteName('f.state') . " = 1");
				$queryCat->where('fv.option_id IS NOT NULL');
				$queryCat->where("f.type IN ('single_select', 'multi_select', 'radio', 'tjlist')");

				$queryCat->JOIN('INNER', '#__tjfields_category_mapping AS fcm ON fcm.field_id = f.id');
				$queryCat->where($db->quoteName('fcm.category_id') . " = " . $category_id);

				$queryCat->order('f.ordering');
				$db->setQuery($queryCat);
				$catFields = $db->loadObjectlist("option_id");

				// Check for duplication for worse
				if (!empty($catFields))
				{
					foreach ($catFields as $key => $cfield)
					{
						// Add element if not exist
						if (!array_key_exists($key, $allFields))
						{
							$allFields[$key] = $cfield;
						}
					}
				}
			}
		}

		return $allFields;
	}

	/**
	 * Method buildFilterModuleQuery for client = com_quick2cart.products
	 *
	 * @return object
	 */
	public static function buildFilterModuleQuery()
	{
		$jinput  = Factory::getApplication()->input;
		$client = $jinput->get("client");

		// Get parameter name in which you are sending category id
		$fields_value_str = $jinput->get("tj_fields_value", '', "RAW");

		if ($fields_value_str)
		{
			$fields_value_str = explode(',', $fields_value_str);
			$fields_value_str = array_filter($fields_value_str, 'trim');
			$fields_value_str = implode(',', $fields_value_str);
		}

		$db    = Factory::getDbo();
		$query = $db->getQuery(true);

		// Selected field value
		if (!empty($fields_value_str))
		{
			$TjfieldsHelper = new TjfieldsHelper;
			$fieldAndFieldOptionsList = $TjfieldsHelper->getFieldAndFieldOptionsList($fields_value_str);

			// If only one fields options are there then no need to join
			if (count($fieldAndFieldOptionsList) == 1)
			{
				foreach ($fieldAndFieldOptionsList as $fieldId => $fFieldAndFieldOptions)
				{
					if (!empty($fFieldAndFieldOptions))
					{
						$query->select('DISTINCT fv1.content_id');
						$query->from('#__tjfields_fields_value AS fv1');
						$query->where("fv1.option_id IN (" . $fFieldAndFieldOptions->optionsStr . ")");

						return $query;
					}
				}
			}
			else
			{
				$query->select('DISTINCT fv1.content_id');
				$fromFlag = 0;
				$i = 1;

				foreach ($fieldAndFieldOptionsList as $fieldId => $fFieldAndFieldOptions)
				{
					if (empty($fromFlag))
					{
						$query->from('#__tjfields_fields_value AS fv' . $i);
						$query->where("fv" . $i . ".option_id IN (" . $fFieldAndFieldOptions->optionsStr . ")");

						$fromFlag = 1;
					}
					else
					{
						$query->join('INNER', $db->qn('#__tjfields_fields_value') . ' AS fv' . $i . ' ON (' .
						$db->qn('fv' . $i . '.content_id') . ' = ' . $db->qn('fv' . ($i - 1 ) . '.content_id') . ')');
						$query->where("fv" . $i . ".option_id IN (" . $fFieldAndFieldOptions->optionsStr . ")");
					}

					$i++;
				}
			}

			$query->where('fv1.client="' . $client . '" ');

			return $query;
		}
	}

	/**
	 * This function to load class.
	 *
	 * @param   string  $path       Path of file.
	 * @param   string  $className  Class Name to load.
	 *
	 * @return  Object of provided class.
	 */
	public static function loadClass($path, $className)
	{
		if (!class_exists($className))
		{
			JLoader::register($className, $path);
			JLoader::load($className);
		}

		if (class_exists($className))
		{
			return new $className;
		}
		else
		{
			throw new RuntimeException(sprintf('Unable to load class: %s', $className));
		}
	}

	/**
	 * Get option which are stored in field option table.
	 *
	 * @param   STRING  $options  Field's Option id's string
	 *
	 * @return object
	 */
	public function getFieldAndFieldOptionsList($options)
	{
		$fieldAndFieldOptionsList = array();

		if (!empty($options))
		{
			$db    = Factory::getDbo();
			$query = $db->getQuery(true);
			$query->select('field_id, GROUP_CONCAT( id ) AS optionsStr ');
			$query->FROM('#__tjfields_options as fo');
			$query->where('fo.id  IN  (' . $options . ')');
			$query->group('fo.field_id');

			$db->setQuery($query);
			$fieldAndFieldOptionsList = $db->loadObjectlist('field_id');
		}

		return $fieldAndFieldOptionsList;
	}

	/**
	 * Get filter results.
	 *
	 * @return string
	 */
	public function getFilterResults()
	{
		$db = Factory::getDbo();
		$jinput  = Factory::getApplication()->input;

		// Function will return -1 when no content found according to selected fields in filter
		$tjfieldIitem_ids = "-1";
		$fields_value_str = $jinput->get("tj_fields_value", '', "RAW");

		if (!empty($fields_value_str))
		{
			$tjquery = $this->buildFilterModuleQuery();
			$db->setQuery($tjquery);
			$client_ids = $db->loadColumn();

			if (!empty($client_ids))
			{
				$tjfieldIitem_ids = implode(",", $client_ids);
			}

			// Return all the content ids which are matching the filters condition
			return $tjfieldIitem_ids;
		}
		else
		{
			// Return -2 when no filters are selected
			return '-2';
		}
	}

	/**
	 * Get fields for given client
	 *
	 * @param   STRING  $client  client
	 *
	 * @return array|boolean
	 */
	public function getClientFields($client)
	{
		if (!empty($client))
		{
			$db    = Factory::getDbo();
			$query = $db->getQuery(true);
			$query->select('name');
			$query->from('#__tjfields_fields');
			$query->quoteName('client') . ' = ' . $db->quote($client);
			$db->setQuery($query);
			$clientFields = $db->loadColumn();

			return $clientFields;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Fuction to get field id from file path
	 *
	 * @param   STRING  $filePath  media file path
	 *
	 * @return string|boolean
	 */
	public function getFileIdFromFilePath($filePath)
	{
		if (!empty($filePath))
		{
			$db    = Factory::getDbo();
			$query = $db->getQuery(true);
			$query->select($db->quoteName('id'));
			$query->from($db->quoteName('#__tjfields_fields_value'));
			$query->where($db->quoteName('value') . "=" . $db->quote($filePath));
			$db->setQuery($query);
			$mediaId = $db->loadResult();

			return $mediaId;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Fuction to get file path from id
	 *
	 * @param   STRING  $mediaId  media id
	 *
	 * @return string|boolean
	 */
	public function getMediaPathFromId($mediaId)
	{
		if (!empty($mediaId))
		{
			$db = Factory::getDbo();
			$query = $db->getQuery(true);
			$query->select($db->quoteName('value'));
			$query->from($db->quoteName('#__tjfields_fields_value'));
			$query->where($db->quoteName('id') . "=" . (INT) $mediaId);
			$db->setQuery($query);
			$mediaPath = $db->loadResult();

			return $mediaPath;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Method to get media URL.
	 *
	 * @param   STRING  $fileName             media file path
	 * @param   ARRAY   $extraUrlParamsArray  extra url params
	 * @param   ARRAY   $renderer             extra url params
	 *
	 * @return  string|boolean  True on success.
	 *
	 * @since   3.2
	 */
	public function getMediaUrl($fileName, $extraUrlParamsArray = array(), $renderer = 'download')
	{
		if (!empty($fileName))
		{
			if ($renderer == 'download')
			{
				$extraUrlParams = '';

				// If url extra param is present
				if (!empty($extraUrlParamsArray))
				{
					$extraUrlParams = "&id=" . $extraUrlParamsArray['id'];

					if (isset($extraUrlParamsArray['subFormFileFieldId']))
					{
						$extraUrlParams .= "&subFormFileFieldId=" . $extraUrlParamsArray['subFormFileFieldId'];
					}
				}

				// Here, fpht means file encoded path
				$encodedFileName = base64_encode($fileName);
				$basePathLink = 'index.php?option=com_tjfields&task=getMediaFile&fpht=';
				$mediaURL = Uri::base() . substr(Route::_($basePathLink . $encodedFileName . $extraUrlParams), strlen(Uri::base(true)) + 1);

				$csrf = Session::getFormToken() . '=1';
				$mediaURLlink = $mediaURL . '&' . $csrf;
			}
			else
			{
				Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjfields/tables');
				$fieldsValueTable = Table::getInstance('Fieldsvalue', 'TjfieldsTable');
				$fieldsValueTable->load(array('value' => $fileName));

				if ($fieldsValueTable->id)
				{
					$fieldTable = Table::getInstance('Field', 'TjfieldsTable');
					$fieldTable->load($fieldsValueTable->field_id);

					$fieldParams = json_decode($fieldTable->params);
					$uploadPath = $fieldParams->uploadpath;

					$mediaURLlink = $uploadPath . '/' . $fileName;
					$mediaURLlink = str_replace(JPATH_SITE, Uri::root(), $mediaURLlink);
				}
			}

			return $mediaURLlink;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Method to convert file size from MB to Bytes.
	 *
	 * @param   int  $mb  file size in mb
	 *
	 * @return  int|bool  True on success.
	 *
	 * @since   3.2
	 */
	public function formatSizeUnits($mb)
	{
		if (!empty($mb))
		{
			// 1 Megabyte is equal to 1048576 bytes (binary)
			$bytes = $mb * 1048576;

			return $bytes;
		}
		else
		{
			return false;
		}
	}

	/**
	 * tjFileDelete .
	 *
	 * @param   Array  $data  file path.
	 *
	 * @return boolean|string
	 *
	 * @since	1.6
	 */
	public function deleteFile($data)
	{
		$user = Factory::getUser();

		if (!$user->id)
		{
			return false;
		}

		Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjfields/tables');
		$fieldValueTable = Table::getInstance('Fieldsvalue', 'TjfieldsTable');
		$fieldValueTable->load(array('id' => $data['valueId']));

		$subData = new stdClass;
		$fieldId = 0;

		if ($data['isSubformField'] == 1)
		{
			$fileUser = $fieldValueTable->user_id;
			$fieldId = $fieldValueTable->field_id;
		}
		else
		{
			if ($data['fileName'] === $fieldValueTable->value)
			{
				$fileUser = $fieldValueTable->user_id;
				$fieldId = $fieldValueTable->field_id;
			}
		}

		$file_extension = strtolower(substr(strrchr($data['fileName'], "."), 1));
		$localGetMime = TJMediaStorageLocal::getInstance();

		$ctype = $localGetMime->getMime($file_extension);

		if (!empty($fileUser))
		{
			$canEdit = $user->authorise('core.field.editfieldvalue', 'com_tjfields.field.' . $fieldId);

			$canEditOwn = $user->authorise('core.field.editownfieldvalue', 'com_tjfields.field.' . $fieldId);

			if ($canEdit || (($user->id == $fileUser) && $canEditOwn))
			{
				$type = explode('/', $ctype);

				if ($type[0] === 'image')
				{
					$deleteData = array();
					$deleteData[] = $data['storagePath'] . '/' . $data['fileName'];
					$deleteData[] = $data['storagePath'] . '/S_' . $data['fileName'];
					$deleteData[] = $data['storagePath'] . '/M_' . $data['fileName'];
					$deleteData[] = $data['storagePath'] . '/L_' . $data['fileName'];

					foreach ($deleteData as $image)
					{
						if (File::exists($image))
						{
							if (!File::delete($image))
							{
								return false;
							}
						}
					}
				}
				else
				{
					if (!File::delete($data['storagePath'] . '/' . $data['fileName']))
					{
						return false;
					}
				}

				return $fieldValueTable->delete();
			}

			return false;
		}

		return false;
	}

	/**
	 * This define the  language constant which you have use in js file.
	 *
	 * @since   1.0
	 * @return   null
	 */
	public static function getLanguageConstantForJs()
	{
		Text::script('COM_TJFIELDS_FILE_DELETE_CONFIRM');
		Text::script('COM_TJFIELDS_FILE_ERROR_MAX_SIZE');
		Text::script('COM_TJFIELDS_FILE_DELETE_SUCCESS');
	}
}
