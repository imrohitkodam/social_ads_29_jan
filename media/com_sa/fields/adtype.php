<?php
/**
 * @version    SVN: <svn_id>
 * @package    SocialAds
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2015 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

// No direct access.
defined('_JEXEC') or die;

use Joomla\CMS\Form\FormField;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;

/**
 * Element for activity stream
 *
 * @since  1.0.0
 */
class JFormFieldAdtype extends JFormField
{
	public $type = 'adtype';

	/**
	 * Function to get the input
	 *
	 * @return  Filter
	 *
	 * @since  1.0.0
	 */
	public function getInput()
	{
		$this->layout = "joomla.form.field.list-fancy-select";
		if (empty($this->options))
		{
			return self::fetchElement($this->name, $this->value, $this->element, '');
		}
		else
		{
			return self::fetchElement($this->name, $this->value, $this->element, $this->options['control']);
		}
	}

	/**
	 * Function to get the activity stream filter
	 *
	 * @param   STRING  $name          name of the field
	 * @param   STRING  $value         value of the field
	 * @param   STRING  &$node         name of the field
	 * @param   STRING  $control_name  name of the field
	 *
	 * @return  Filter
	 *
	 * @since  1.0.0
	 */
	public function fetchElement($name, $value, &$node, $control_name)
	{
		$options[] = HTMLHelper::_('select.option', 'text_media', Text::_('COM_SOCIALADS_TITLE_ZONE_AD_TYPE_TEXT_AND_MEDIA'));
		$options[] = HTMLHelper::_('select.option', 'text', Text::_('COM_SOCIALADS_TITLE_ZONE_AD_TYPE_TEXT'));
		$options[] = HTMLHelper::_('select.option', 'media', Text::_('COM_SOCIALADS_TITLE_ZONE_AD_TYPE_MEDIA'));
		$options[] = HTMLHelper::_('select.option', 'html5_zip', Text::_('COM_SOCIALADS_TITLE_ZONE_AD_HTML5'));
		$fieldName = $name;

		$default = array();
		$default[] = 'text_media';
		$default[] = 'text';
		$default[] = 'media';

		if (empty($value))
		{
			$value = $default;
		}

		$optionalField = 'class="inputbox form-select adtypeFilter"  multiple="multiple" size="10"';

		return HTMLHelper::_('select.genericlist', $options, $fieldName, $optionalField, 'value', 'text', $value, $control_name . $name);
	}
}
