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
class JFormFieldCampains extends JFormFieldList
{
	protected $type = 'Campains';
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
		$app     = Factory::getApplication();
		$userid = $user->id;
		$db	= Factory::getDbo();
		$query	= $db->getQuery(true);
		$query->select($db->quoteName(array('id', 'campaign')));
		$query->from($db->quoteName('#__ad_campaign', 'ac'));

		if ($app->isClient("site"))
		{
			$query->where($db->quoteName('ac.created_by') . ' = ' . (int) $userid);
		}

		// Get the options.
		$db->setQuery($query);

		$allcampaigns = $db->loadObjectList();

		$options = array();

		$options[] = HTMLHelper::_('select.option', 0, Text::_('COM_SOCIALADS_SELECT_CAMPAIGN'));

		foreach ($allcampaigns as $c)
		{
			$options[] = HTMLHelper::_('select.option', $c->id, $c->campaign);
		}

		return $options;
	}
}
