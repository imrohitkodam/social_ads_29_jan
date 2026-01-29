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

if ($field->value != '')
{
	$checked = ($field->value == 1) ? ' checked="checked"' : '';?>
	<input type="checkbox" disabled="disabled" value="1" <?php echo $checked;?> />
	<?php
}
