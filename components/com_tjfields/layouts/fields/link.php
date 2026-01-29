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

if ($field->value)
{
	// This is working for normal form video field
	$languageConstant = "COM_TJFIELDS_VIEW_FIELD_" . strtoupper($field->type);
	echo "<a href=" . $field->value . " target='_blank'>" . Text::_($languageConstant) . "</a>";
}