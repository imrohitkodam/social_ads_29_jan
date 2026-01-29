<?php
/**
 * @package     TJ-Fields
 * @subpackage  Form
 * author       TechJoomla <extensions@techjoomla.com>
 * @copyright   Copyright (c) 2009-2019 TechJoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

// No direct access
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Table\Table;
HTMLHelper::_('bootstrap.renderModal');

if (!key_exists('field', $displayData) || !key_exists('fieldXml', $displayData))
{
	return;
}

JLoader::import('tjfields', JPATH_SITE . '/components/com_tjfields/helpers/');

$xmlField = $displayData['fieldXml'];
$field = $displayData['field'];
$isSubFormField = (isset($displayData['isSubFormField'])) ? $displayData['isSubFormField'] : 0;
$subFormFileFieldId = (isset($displayData['subFormFileFieldId'])) ? $displayData['subFormFileFieldId'] : 0;

$renderer = ($xmlField['renderer'] instanceof SimpleXMLElement) ? $xmlField['renderer']->__toString() : 'download';

if ($field->value)
{
	Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjfields/tables');
	$fieldsValueTable = Table::getInstance('Fieldsvalue', 'TjfieldsTable');
	$fieldsValueTable->load(array('value' => $field->value));

	$extraParamArray = array();
	$extraParamArray['id'] = $fieldsValueTable->id;

	// Creating media link by check subform or not
	if ($isSubFormField)
	{
		$extraParamArray['subFormFileFieldId'] = $subFormFileFieldId;
	}

	$tjFieldHelper = new TjfieldsHelper;
	$mediaLink = $tjFieldHelper->getMediaUrl($field->value, $extraParamArray, $renderer);
	$imageData = getimagesize($mediaLink);
	$widthHeight = "";

	if (strpos($imageData['mime'], 'image') !== false)
	{
		$widthHeight  = ", " . $imageData[0] . ", " . $imageData[1];
	}

	$fileTitle = substr($field->value, strpos($field->value, '_', 12) + 1);

	if ($renderer == 'download')
	{
		echo "<div><strong class='ml-15'><a href=" . $mediaLink . ">" . $fileTitle . "</a></strong></div>";
	}
	else
	{
		HTMLHelper::script('media/com_tjfields/js/ui/file.js');
		$extension = strtolower(end(explode('.', $fileTitle)));

		if (in_array($extension, array('ppt', 'pptx', 'doc', 'docx', 'xls', 'xlsx', 'pps', 'ppsx')))
		{
			$mediaLink = 'https://view.officeapps.live.com/op/embed.aspx?src=' . $mediaLink;
		}
		elseif (in_array($extension, array('png', 'jpeg', 'jpg', 'gif')))
		{
			$mediaLink = $mediaLink;
		}
		else
		{
			$mediaLink = 'https://docs.google.com/gview?url=' . $mediaLink . '&embedded=true';
		}

		echo '<div><strong class="ml-15"><a style="cursor:pointer;" onclick="tjFieldsFileField.previewMedia(\'' . $mediaLink . '\'' . $widthHeight . ');">' . $fileTitle . '</a></strong></div>';
	}
}
