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

FormHelper::loadFieldClass('list');

/**
 * Custom Field class for the Joomla Framework.
 *
 * @package     Com_Socialads
 * @subpackage  com_socialads
 * @since       1.6
 */
class JFormFieldCampaignslist extends JFormFieldList
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
		$query->select($db->quoteName(array('camp_id', 'campaign')));
		$query->from($db->quoteName('#__ad_campaign'));
		$query->where($db->qn('state') . ' = ' . $userid);

		// Get the options.
		$db->setQuery($query);

		$options = $db->loadObjectList();

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
