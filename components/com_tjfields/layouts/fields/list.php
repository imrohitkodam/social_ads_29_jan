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

$xmlField = $displayData['fieldXml'];
$field = $displayData['field'];

if (!$xmlField instanceof SimpleXMLElement)
{
	return;
}

$xmlOptions = $xmlField->children();
$options = array();

foreach ($xmlOptions as $xmlOption)
{
	$xmlOptionText = ($xmlOption instanceof SimpleXMLElement) ? $xmlOption->__toString() : '';
	$xmlOptionValue = ($xmlOption['value'] instanceof SimpleXMLElement) ? $xmlOption['value']->__toString() : '';
	$options[$xmlOptionValue] = $xmlOptionText;
}

if ($field->value)
{
	if (!is_array($field->value))
	{
		// If single select
		if (isset($options[$field->value]))
		{
			$options[$field->value] = htmlspecialchars($options[$field->value], ENT_COMPAT, 'UTF-8');
			echo Text::_($options[$field->value]);
		}
		elseif ($field->value != Text::_('COM_TJFIELDS_TJLIST_OTHER_OPTION_VALUE'))
		{
			echo Text::_(str_replace($field->type . ':-', '', $field->value));
			echo "<br>";
		}
	}
	else
	{
		// If multi select
		$savedValues = $field->value;

		foreach ($options as $optionValue => $optionText)
		{
			if (in_array($optionValue, $savedValues))
			{
				// If multi select
				foreach ($savedValues as $k => $value)
				{
					if ($optionValue == $value)
					{
						unset($savedValues[$k]);
						echo Text::_(htmlspecialchars($optionValue, ENT_COMPAT, 'UTF-8'));
						echo "<br>";
					}
				}
			}
		}

		if (!empty($savedValues))
		{
			foreach ($savedValues as $savedValue)
			{
				if ($savedValue != Text::_('COM_TJFIELDS_TJLIST_OTHER_OPTION_VALUE'))
				{
					$savedValue = str_replace($field->type . ':-', '', $savedValue);

					if (!empty($savedValue))
					{
						$savedValue = explode(',', $savedValue);

						foreach ($savedValue as $value)
						{
							echo Text::_(htmlspecialchars($value, ENT_COMPAT, 'UTF-8'));
							echo "<br>";
						}
					}
				}
			}
		}
	}
}
