<?php
/**
 * @version    SVN:<SVN_ID>
 * @package    Js_Events
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2015 TechJoomla. All rights reserved
 * @license    GNU General Public License version 2, or later
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Filesystem\Folder;

$lang = Factory::getLanguage();
$lang->load('plg_socialadspromote_esprofile', JPATH_ADMINISTRATOR);

/**
 * Plugin class to promote EasySocial profile in Socialads.
 *
 * @since  1.6
 */
class PlgSocialadsPromoteEs_Profile extends CMSPlugin
{
	/**
	 * Methode to promote EasySocial profile
	 *
	 * @param   integer  $uid  users ID
	 *
	 * @return  array
	 *
	 * @since   1.6
	 */
	public function onPromoteList($uid = '')
	{
		if ($uid)
		{
			$user = Factory::getUser($uid);
		}
		else
		{
			$user = Factory::getUser();
		}

		$db    = Factory::getDbo();
		$name = basename(__FILE__);
		$name  = File::stripExt($name);
		$eschk = $this->checkForEsExtension();

		if (!empty($eschk))
		{
			$query = $db->getQuery(true);
			$query->select("CONCAT_WS('|', '" . $name . "', u.id) as value");
			$query->select("u.name AS text");
			$query->from($db->quoteName('#__users', 'u'));
			$query->join('LEFT', $db->quoteName('#__social_users', 's') . 'ON' . $db->quoteName('u.id') . '=' . $db->quoteName('s.user_id'));
			$query->where($db->quoteName('u.id') . " = " . $db->quote($user->id));
			$db->setQuery($query);
			$itemlist = $db->loadObjectlist();

			return $itemlist;
		}
	}

	/**
	 * Methode to get promotion data
	 *
	 * @param   integer  $id  Id of a event
	 *
	 * @return  array
	 *
	 * @since   1.6
	 */
	public function onSocialAdPromoteData($id)
	{
		$db    = Factory::getDbo();
		$eschk = $this->checkForEsExtension();

		if (!empty($eschk))
		{
			// Get user name and about me
			$query = $db->getQuery(true);
			$query->select('u.name as title');
			$query->from($db->quoteName('#__users', 'u'));
			$query->where($db->quoteName('u.id') . " = " . $db->quote($id));
			$db->setQuery($query);
			$previewData = $db->loadObjectList();

			// Get user avatar and profile URL
			jimport('techjoomla.jsocial.jsocial');
			jimport('techjoomla.jsocial.easysocial');
			$jSocialObj = new JSocialEasySocial;
			$imagePath = $jSocialObj->getAvatar(Factory::getUser($id));
			$link      = $jSocialObj->getProfileUrl(Factory::getUser($id));

			$previewData[0]->image = $imagePath;
			$previewData[0]->url   = Uri::root() . substr(Route::_($link, false), strlen(Uri::base(true)) + 1);

			// Set bodytext as empty
			// @TODO - needs to get about me data here
			$previewData[0]->bodytext = '';

			return $previewData;
		}
		else
		{
			return '';
		}
	}

	/**
	 * Methode to check if the extension folder is present
	 *
	 * @params  integer  $id  Id of a event
	 *
	 * @return  array
	 *
	 * @since   1.6
	 */
	public function checkForEsExtension()
	{
		$extpath = JPATH_ROOT . '/components/com_easysocial';

		if (Folder::exists($extpath))
		{
			return 1;
		}
		else
		{
			return 0;
		}
	}
}
