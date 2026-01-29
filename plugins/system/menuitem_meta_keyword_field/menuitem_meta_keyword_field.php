<?php
/**
 * @package    metadata_keyword
 *
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2022  Techjoomla. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

// No direct access.
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Plugin\CMSPlugin;

// Load language file for plugin
$lang = Factory::getLanguage();
$lang->load('plg_system_menuitem_meta_keyword', JPATH_ADMINISTRATOR);

/**
 * Class for TJ assets loader plugin
 *
 * @package     JBolo
 * @subpackage  metadata_keyword
 * @since       3.1.4
 */
class PlgSystemMenuitem_Meta_Keyword_Field extends CMSPlugin
{
	/**
	 * The form event.
	 *
	 * @param   Form      $form  The form
	 * @param   stdClass  $data  The data
	 *
	 * @return  boolean
	 *
	 * @since   3.7.0
	 */
	public function onContentPrepareForm(Form $form, $data)
	{
		$context = $form->getName();

		if (JVERSION >= '4.0' && $context == 'com_menus.item')
		{
			$path = JPATH_SITE . "/plugins/system/menuitem_meta_keyword_field/forms/metadata_keyword.xml";
			$form->loadFile($path, true, '/form/*');
		}

		return true;
	}
}
