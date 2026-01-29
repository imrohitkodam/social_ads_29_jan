<?php
/**
 * @version    SVN:<SVN_ID>
 * @package    Com_Socialads
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2015 TechJoomla. All rights reserved
 * @license    GNU General Public License version 2, or later
 */

defined('JPATH_BASE') or die;
use Joomla\CMS\Form\FormField;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Form\FormHelper;

FormHelper::loadFieldClass('list');

/**
 * Custom Field class for the Joomla Framework.
 *
 * @package  Com_Socialads
 *
 * @since    1.6
 */
class JFormFieldZones extends JFormFieldList
{
	protected $type = 'Zones';
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
		$user = Factory::getUser();
		$userid = $user->id;
		$db	= Factory::getDbo();
		$query	= $db->getQuery(true);

		$query->select($db->quoteName(array('z.id', 'z.zone_name')));
		$query->from($db->quoteName('#__ad_data', 'd'));
		$query->join('LEFT', $db->quoteName('#__ad_zone', 'z') . 'ON' . $db->quoteName('z.id') . '=' . $db->quoteName('d.ad_zone'));
		$query->order($db->qn('z.zone_name'));
		$query->group($db->quoteName('z.zone_name'));

		// Get the options.
		$db->setQuery($query);

		$allZones = $db->loadObjectList();

		$options = array();

		$options[] = HTMLHelper::_('select.option', 0, Text::_('COM_SOCIALADS_SELECT_ZONE'));

		foreach ($allZones as $c)
		{
			$options[] = HTMLHelper::_('select.option', $c->id, $c->zone_name);
		}

		return $options;
	}
}
