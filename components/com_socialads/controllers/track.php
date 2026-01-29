<?php
/**
 * @version    SVN:<SVN_ID>
 * @package    SocialAds
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2015 TechJoomla. All rights reserved
 * @license    GNU General Public License version 2, or later
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;


require_once JPATH_COMPONENT . '/controller.php';
/**
 * Campaign controller class.
 *
 * @since  1.6
 */
class SocialadsControllerTrack extends SocialadsController
{
	/**
	 * Method to display a view.
	 *
	 * @param   boolean  $cachable   If true, the view output will be cached
	 * @param   array    $urlparams  An array of safe url parameters and their variable types, for valid values see {@link JFilterInput::clean()}.
	 *
	 * @return  JController  This object to support chaining.
	 *
	 * @since   1.5
	 */
	public function display($cachable = false, $urlparams = false)
	{
		return true;

		// @parent::display();
	}

	/**
	 * Method to redirect ad
	 *
	 * @return void
	 *
	 * @since 3.0
	 */
	public function redirect()
	{
		global $mainframe;
		$mainframe = Factory::getApplication();
		$input     = Factory::getApplication()->input;

		// Get ad id
		// $adid = $input->get('adid', 0, 'INT');
		$adid = $input->get('id', 0, 'INT');

		/* require_once JPATH_SITE . '/components/com_socialads/helpers/ads.php';
			$adRetriever     = new adRetriever();
			$statue_adcharge = $adRetriever->getAdStatus($adid);
		*/
		$statue_adcharge = SaAdEngineHelper::getInstance()->getAdStatus($adid);

		if ($statue_adcharge['status_ads'] == 1)
		{
			$caltype = $input->get('caltype', 0, 'INT');
			$widget  = $input->get('widget', '', 'STRING');

			// $adRetriever->reduceCredits($adid, $caltype, $statue_adcharge['ad_charge'], $widget);
			$saCreditsHelper = new SaCreditsHelper;
			$saCreditsHelper->reduceCredits($adid, $caltype, $statue_adcharge['ad_charge'], $widget);

			/*START API Trigger*/
			PluginHelper::importPlugin('system');
			Factory::getApplication()->triggerEvent('onSA_Adclick');
			/*END API Trigger*/

			// Added for added for sa_jbolo integration
			$chatoption = $input->get('chatoption', 0, 'INT');

			if ($chatoption)
			{
				jexit();
			}
			// End added for added for sa_jbolo integration

			// $result = $this->getURL();
			$result = SaAdsHelper::getUrl($adid);
			$mainframe->redirect($result);
		}
	}

	/**
	 * Method to publish records.
	 *
	 * @return void
	 *
	 * @since 3.0
	 */
	public function ignore()
	{
		$input = Factory::getApplication()->input;
		$db    = Factory::getDBO();
		$user  = Factory::getUser();

		$adid = $input->get('ignore_id', 0, 'INT');
		$fdid = $input->get('feedback', '', 'STRING');

		if ($fdid && $user)
		{
			// Query to find if logged in user has already blocked the same user...
			$query = $db->getQuery(true);
			$fields = array($db->quoteName('ad_feedback') . ' = ' . $db->quote($fdid));
			$conditions = array(
				$db->quoteName('userid') . ' = ' . (int) $user->id,
				$db->quoteName('adid') . ' = ' . (int) $adid
			);

			$query->update($db->quoteName('#__ad_ignore'))
					->set($fields)
					->where($conditions);
			$db->setQuery($query);
			$db->execute();
		}
		elseif($adid && $user)
		{
			// Query to find if logged in user has already blocked the same user...
			$query = $db->getQuery(true);
			$query->select('userid', 'adid');
			$query->from($db->qn('#__ad_ignore'));
			$query->where($db->qn('userid') . ' = ' . (int) $user->id);
			$query->where($db->qn('adid') . ' = ' . (int) $adid);
			$db->setQuery($query);
			$existing = $db->loadObjectList();

			if (!$existing)
			{
				$data = new stdClass;
				$data->id = null;
				$data->userid = $user->id;
				$data->adid = $adid;

				if (!$db->insertObject('#__ad_ignore', $data))
				{
					echo "0";
				}
				else
				{
					PluginHelper::importPlugin('socialads');
					Factory::getApplication()->triggerEvent('onAfterSocialAdIgnore', array($adid, $user->id));
					echo "1";
				}
			}
		}
	}

	/**
	 * Method to undo ad.
	 *
	 * @return void
	 *
	 * @since 3.0
	 */
	public function undoIgnore()
	{
		$db    = Factory::getDBO();
		$my    = Factory::getUser();
		$input = Factory::getApplication()->input;
		$adid  = $input->get('ignore_id', 0, 'INT');

		if ($adid)
		{
			// Query to find if logged in user has already blocked the same user...
			$query = $db->getQuery(true);
			$conditions = array(
				$db->quoteName('userid') . ' = ' . (int) $my->id,
				$db->quoteName('adid') . ' = ' . (int) $adid
			);

			$query->delete($db->quoteName('#__ad_ignore'));
			$query->where($conditions);
			$db->setQuery($query);
			$db->execute();
		}
	}
}
