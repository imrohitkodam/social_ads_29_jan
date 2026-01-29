<?php
/**
 * @package    TJ-Fields
 * @author     TechJoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2019 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\Table\Table;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

if (!key_exists('field', $displayData) || !key_exists('fieldXml', $displayData))
{
	return;
}

JLoader::import('tjfields', JPATH_SITE . '/components/com_tjfields/helpers/');

$field = $displayData['field'];
$isSubFormField = (isset($displayData['isSubFormField'])) ? $displayData['isSubFormField'] : 0;
$subFormFileFieldId = (isset($displayData['subFormFileFieldId'])) ? $displayData['subFormFileFieldId'] : 0;

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

	$path = Uri::root() . 'images/tjmedia/';

	$db = Factory::getDbo();
	Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjfields/tables');
	$data->tjFieldFieldTable = Table::getInstance('field', 'TjfieldsTable', array('dbo', $db));
	$data->tjFieldFieldTable->load(array('name' => $field->element->attributes()->name));

	if (!empty($data->tjFieldFieldTable))
	{
		$path .= str_replace(".", "/", $data->tjFieldFieldTable->get('client') . '/');
	}

	$tjFieldHelper = new TjfieldsHelper;
	$mediaLink = $tjFieldHelper->getMediaUrl($field->value, $extraParamArray);
	?>
	<div>
		<img src="<?php echo $path . $field->value; ?>" height="<?php echo $field->element->attributes()->height;?>" width="<?php $field->element->attributes()->width;?>" />
		<a href="<?php echo $mediaLink;?>" class="btn btn-success">
		<?php echo Text::_("COM_TJFIELDS_FILE_DOWNLOAD");?></a>
	</div>
	<?php
}