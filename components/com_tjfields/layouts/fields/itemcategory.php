<?php
/**
 * @package	TJ-Fields
 * @author	 TechJoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2019 TechJoomla. All rights reserved.
 * @license	GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\Factory;
use Joomla\CMS\Table\Table;

if (!key_exists('field', $displayData))
{
	return;
}

$field = $displayData['field'];
$categoryId = (int) $field->value;

if ($categoryId)
{
	$db = Factory::getApplication();
	Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_categories/tables');
	$categoryTable = Table::getInstance('Category', 'JTable');
	$categoryTable->load($categoryId);

	echo htmlspecialchars($categoryTable->title, ENT_COMPAT, 'UTF-8');
}
