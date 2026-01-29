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
use Joomla\CMS\Filesystem\Folder;

$lang = Factory::getLanguage();
$lang->load('plg_socialadspromote_jsevent', JPATH_ADMINISTRATOR);

/**
 * Plugin class to promote JomSocial events in Socialads.
 *
 * @since  1.6
 */
class PlgSocialadsPromoteJsEvent extends CMSPlugin
{
	/**
	 * Methode to promote jomsocial events
	 *
	 * @param   integer  $uid  users ID
	 *
	 * @return  array
	 *
	 * @since   1.6
	 */
	public function onPromoteList($uid = '')
	{
		$db = Factory::getDBO();

		if ($uid)
		{
			$user = Factory::getUser($uid);
		}
		else
		{
			$user = Factory::getUser();
		}

		$name = basename(__FILE__);
		$name = File::stripExt($name);
		$jschk = $this->checkForCbExtension();

		if (!empty($jschk))
		{
			$query = $db->getQuery(true);
			$query->select("CONCAT_WS('|', '" . $name . "', e.id) as value");
			$query->select("e.title AS text");
			$query->from($db->quoteName('#__community_events', 'e'));
			$query->join('LEFT', $db->quoteName('#__users', 'u') . 'ON' . $db->quoteName('e.creator') . '=' . $db->quoteName('u.id'));
			$query->where($db->quoteName('u.id') . " = " . $db->quote($user->id));
			$db->setQuery($query);
			$itemlist = $db->loadObjectlist();

			if (empty($itemlist))
			{
				$list = array();

				return $list;
			}
			else
			{
				return $itemlist;
			}
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
		$db    = Factory::getDBO();
		$jschk = $this->checkForCbExtension();

		if (!empty($jschk))
		{
			$query = $db->getQuery(true);
			$query->select('title as title, cover as image, description as bodytext');
			$query->from($db->quoteName('#__community_events'));
			$query->where($db->quoteName('id') . " = " . $db->quote($id));
			$db->setQuery($query);
			$previewdata = $db->loadObjectList();

			// Include Jomsocial core
			$jspath = JPATH_ROOT . "/components/com_community";
			include_once $jspath . "/libraries/core.php";
			$previewdata[0]->url = Uri::root() .
				substr(CRoute::_('index.php?option=com_community&view=events&task=viewevent&eventid=' . $id), strlen(Uri::base(true)) + 1);

			if ($previewdata[0]->image == '')
			{
				$previewdata[0]->image = 'components/com_community/assets/event.png';
			}

			$previewdata[0]->bodytext = strip_tags($previewdata[0]->bodytext);

			return $previewdata;
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
	public function checkForCbExtension()
	{
		$extpath = JPATH_ROOT . "/components/com_community";

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
