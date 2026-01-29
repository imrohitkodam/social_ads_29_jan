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
 * @package     Com_Socialads
 * @subpackage  com_socialads
 * @since       1.6
 */
class JFormFieldGatewaylist extends JFormFieldList
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
		$user = Factory::getUser();
		$userid = $user->id;
		$db	= Factory::getDbo();
		$query	= $db->getQuery(true);

		$query->select('DISTINCT(processor)');
		$query->where($db->quoteName('processor'). ' != ' . $db->q(''));
		$query->from($db->quoteName('#__ad_orders'));
		$query->group($db->quoteName('processor'));

		// Get the options.
		$db->setQuery($query);

		$allgateway = $db->loadObjectList();

		$options[] = HTMLHelper::_('select.option', 0, Text::_('COM_SOCIALADS_SELECT_GATEWAY'));

		foreach ($allgateway as $c)
		{
			$options[] = HTMLHelper::_('select.option', $c->processor);
		}
		
		// Check for a database error.
		try
		{
			$db->execute();
		}
		catch (\RuntimeException $e)
		{
			$this->setMessage($e->getMessage(), 'error');

			return false;
		}

		return $options;
	}
}
