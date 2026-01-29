<?php
/**
 * @version    SVN:<SVN_ID>
 * @package    SocialAds
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2015 TechJoomla. All rights reserved
 * @license    GNU General Public License version 2, or later
 */

defined('JPATH_BASE') or die;

use Joomla\CMS\Form\Field\UserField;
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
class JFormFieldUsernamelist extends JFormFieldList
{
	protected $type = 'Usernamelist';
	/**
	 * Method to get the field options.
	 *
	 * @return	array	The field option objects.
	 *
	 * @since	1.6
	 */
	public function getOptions()
	{
		$options = array();
		$user = Factory::getUser();
		$userid = $user->id;
		$db	= Factory::getDbo();
		$query	= $db->getQuery(true);

		$query->select("DISTINCT (c.created_by) AS id");
		$query->select($db->quoteName("u.name", "name"));
		$query->from($db->quoteName('#__ad_campaign', 'c'));
		$query->join('LEFT', $db->quoteName("#__users", "u") . " ON " . $db->quoteName("u.id") . " = " . $db->quoteName("c.created_by"));
		$query->order($db->quoteName("name"));

		// Get the options.
		$db->setQuery($query);

		$allusers = $db->loadObjectList();
		$options[] = HTMLHelper::_('select.option', 0, Text::_('COM_SOCIALADS_SELECT_USERNAME'));

		foreach ($allusers AS $user)
		{
			$options[] = HTMLHelper::_('select.option', $user->id, $user->name);
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
