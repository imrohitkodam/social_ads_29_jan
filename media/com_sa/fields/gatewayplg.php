<?php
/**
 * @version    SVN: <svn_id>
 * @package    SocialAds
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2015 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

// No direct access
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\Form\FormField;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;


/**
 * Class for custom gateway element
 *
 * @since  1.0.0
 */
class JFormFieldGatewayplg extends JFormField
{
	protected $type = 'Gatewayplg';

	/**
	 * Function to genarate html of custom element
	 *
	 * @return  HTML
	 *
	 * @since  1.0.0
	 */
	public function getInput()
	{
		if (empty($this->options))
		{
			return $this->fetchElement($this->name, $this->value, $this->element, '');
		}
		else
		{
			return $this->fetchElement($this->name, $this->value, $this->element, $this->options['control']);
		}
	}

	/**
	 * Function to fetch a tooltip
	 *
	 * @param   string  $name          name of field
	 * @param   string  $value         value of field
	 * @param   string  &$node         node of field
	 * @param   string  $control_name  control_name of field
	 *
	 * @return  HTML
	 *
	 * @since  1.0.0
	 */
	public function fetchElement($name, $value, &$node, $control_name)
	{
		$db = Factory::getDBO();
		$condtion = array(0 => 'payment');
		$query	= $db->getQuery(true);

		if (JVERSION >= '1.6.0')
		{
			$query->select($db->quoteName('extension_id', 'id'));
			$query->select($db->quoteName('enabled', 'published'));
			$query->select($db->quoteName(array('name', 'element')));
			$query->from($db->quoteName('#__extensions'));
			$query->where($db->quoteName('enabled') . ' = ' . 1);
			$query->where($db->quoteName('folder') . ' IN (' . implode(',', $db->quote($condtion)) . ')');
		}
		else
		{
			$query->select($db->quoteName(array('id', 'name', 'element', 'published')));
			$query->from($db->quoteName('#__plugins'));
			$query->where($db->quoteName('published') . ' = ' . 1);
			$query->where($db->quoteName('folder') . ' IN (' . implode(',', $db->quote($condtion)) . ')');
		}

		$db->setQuery($query);
		$gatewayplugin = $db->loadobjectList();

		$options = array();

		foreach ($gatewayplugin as $gateway)
		{
			$gatewayname = ucfirst(str_replace('plugpayment', '', $gateway->element));
			$options[] = HTMLHelper::_('select.option', $gateway->element, $gatewayname);
		}

		if (JVERSION >= 1.6)
		{
			$fieldName = $name;
		}
		else
		{
			$fieldName = $control_name . '[' . $name . ']';
		}

		$default = array();
		$default[] = "bycheck";
		$default[] = "byorder";

		if (empty($value))
		{
			$value = $default;
		}

		$html = HTMLHelper::_('select.genericlist', $options, $fieldName, 'class="inputbox form-select"  multiple="multiple" size="5"', 'value', 'text', $value,
			$control_name . $name);

		$class = "";
		if (JVERSION < '4.0')
		{
			$href = "index.php?option=com_plugins&view=plugins&filter_folder=payment&filter_enabled=";
		}
		else
		{
			$href = "index.php?option=com_plugins&view=plugins&filter_enabled=&filter[folder]=payment";
		}

		// Show link for payment plugins.
		$html .= '<a
			href="'. $href .'"
			target="_blank"
			class="btn btn-small btn-primary ' . $class . '">'
				. Text::_('COM_SOCIALADS_SETTINGS_SETUP_PAYMENT_PLUGINS') .
			'</a>';

		return $html;
	}

	/**
	 * Function to fetch a tooltip
	 *
	 * @param   string  $label         label of field
	 * @param   string  $description   description of field
	 * @param   string  &$node         node of field
	 * @param   string  $control_name  control_name of field
	 * @param   string  $name          name of field
	 *
	 * @return  HTML
	 *
	 * @since  1.0.0
	 */
	public function fetchTooltip($label, $description, &$node, $control_name, $name)
	{
		return null;
	}
}
