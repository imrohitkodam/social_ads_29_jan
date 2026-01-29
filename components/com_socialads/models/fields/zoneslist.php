<?php
/**
 * @version    SVN:<SVN_ID>
 * @package    Com_Socialads
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2015 TechJoomla. All rights reserved
 * @license    GNU General Public License version 2, or later
 */

defined('JPATH_BASE') or die;

use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;

FormHelper::loadFieldClass('list');

/**
 * Custom Field class for the Joomla Framework.
 *
 * @package  Com_Socialads
 *
 * @since    1.6
 */
class JFormFieldZoneslist extends JFormFieldList
{
	/**
	 * Method to get the field options.
	 *
	 * @return	array	The field option objects.
	 *
	 * @since	1.6
	 */
	public function getOptions()
	{
		// Initialize variables.
		$options = array();
		$db	= Factory::getDbo();
		$query	= $db->getQuery(true);
		$query->select($db->quoteName(array('z.id', 'z.zone_name')));
		$query->from($db->quoteName('#__ad_data', 'a'));
		$query->join('LEFT', $db->quoteName('#__ad_zone', 'z') . 'ON' . $db->quoteName('z.id') . '=' . $db->quoteName('a.ad_zone'));
		$query->where($db->quoteName('z.state') . ' = ' . 1);
		$query->group($db->quoteName('z.zone_name'));

		// Get the options.
		$db->setQuery($query);
		$allzones = $db->loadObjectList();

		$options   = array();
		$options[] = HTMLHelper::_('select.option', 0, Text::_('COM_SOCIALADS_SELECT_ZONE'));

		foreach ($allzones as $c)
		{
			$options[] = HTMLHelper::_('select.option', $c->id, $c->zone_name);
		}

		return $options;
	}
}
