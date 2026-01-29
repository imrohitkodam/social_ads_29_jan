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

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Factory;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Filesystem\Folder;

require_once JPATH_ROOT . '/administrator/components/com_easysocial/includes/foundry.php';
/**
 * Plugin class to promote easysocial events in Socialads.
 *
 * @since  1.6
 */
class PlgSocialadsPromoteEsEvent extends CMSPlugin
{
	/**
	 * Load plugin language file automatically so that it can be used inside component
	 *
	 * @var    boolean
	 * @since  3.1.13
	 */
	protected $autoloadLanguage = true;

	/**
	 * Methode to promote easysocial events
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

		$name  = basename(__FILE__);
		$name  = File::stripExt($name);

		// Check if the EasySocial is Installed or Not
		$eschk = $this->checkForEsExtension();

		if (!empty($eschk))
		{
			$query = $db->getQuery(true);
			$query->select("CONCAT_WS('|', '" . $name . "', e.id) as value");
			$query->select("e.title AS text");
			$query->from($db->quoteName('#__social_clusters', 'e'));
			$query->join('LEFT', $db->quoteName('#__users', 'u') . 'ON' . $db->quoteName('e.creator_uid') . '=' . $db->quoteName('u.id'));
			$query->where($db->quoteName('u.id') . " = " . $db->quote($user->id));
			$db->setQuery($query);
			$eslist = $db->loadObjectlist();

			if (empty($eslist))
			{
				return array();
			}
			else
			{
				return $eslist;
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
		$db = Factory::getDBO();

		$eschk = $this->checkForEsExtension();

		if (!empty($eschk))
		{
			$event   = Foundry::event();
			$data    = $event->loadEvents($id);
			$photoId = $data->cover->photo_id;

			$photo   = ES::table('Photo');
			$photo->load($photoId);
			$storageUrl = $photo->getSource('thumbnail');

			$previewData[0]           = new stdclass;
			$previewData[0]->title    = $data->title;
			$previewData[0]->image    = $storageUrl;
			$previewData[0]->url      = $data->getPermalink(true, true);
			$previewData[0]->bodytext = $data->description;

			$params = new stdClass;
			$params->eventId = $id;

			$previewData[0]->params = json_encode($params);

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
		$extpath = JPATH_ROOT . "/components/com_easysocial";

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
