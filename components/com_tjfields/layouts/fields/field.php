<?php
/**
 * @package	TJ-Fields
 * @author	 TechJoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2019 TechJoomla. All rights reserved.
 * @license	GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

if (!key_exists('field', $displayData) || !key_exists('fieldXml', $displayData))
{
	return;
}

$xmlField = $displayData['fieldXml'];
$field = $displayData['field'];

if ($field->value)
{
	if (is_array($field->value))
	{
		foreach ($field->value as $eachFieldValue)
		{
			?>
			<p><?php echo "-" . htmlspecialchars($eachFieldValue, ENT_COMPAT, 'UTF-8'); ?></p>
			<?php
		}
	}
	else
	{
		echo htmlspecialchars($field->value, ENT_COMPAT, 'UTF-8');
	}
}