<?php
/**
 * @package	TJ-Fields
 * @author	 TechJoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2019 TechJoomla. All rights reserved.
 * @license	GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\Language\Text;

if (!key_exists('field', $displayData) || !key_exists('fieldXml', $displayData))
{
	return;
}

$field = $displayData['field'];

$value = $field->value;

if ($value == '')
{
	return;
}

$options = $field->getOptions();

if (empty($options))
{
	return;
}

$fieldOptions = array();

foreach ($options as $option)
{
	$option = (object) $option;
	$fieldOptions[$option->value] = Text::_(htmlspecialchars($option->text, ENT_COMPAT, 'UTF-8'));
}

if (!is_array($field->value))
{
	// If single select
	if (isset($fieldOptions[$field->value]))
	{
		echo $fieldOptions[$field->value];
	}
}
else
{
	// If multi select
	foreach ($field->value as $value)
	{
		if (isset($fieldOptions[$value]))
		{
			echo $fieldOptions[$value];
			echo "<br>";
		}
	}
}
