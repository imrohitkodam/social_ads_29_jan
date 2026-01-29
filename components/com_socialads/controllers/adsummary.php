<?php
/**
 * @version    SVN: <svn_id>
 * @package    Socialads
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2015 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

// No direct access.
defined('_JEXEC') or die();
use Joomla\CMS\Factory;

$lang = Factory::getLanguage();


/**
 * controller class.
 *
 * @package     Socialads
 * @subpackage  com_socialads
 * @since       2.2
 */
require_once JPATH_COMPONENT . '/controller.php';

/**
 * Controller for adsummary view.
 *
 * @since  1.6
 */
class SocialadsControllerAdsummary extends SocialadsController
{
	/**
	 * Method to delete ad records.
	 *
	 * @return void
	 *
	 * @since 3.0
	 */
	public function deletead()
	{
		$mainframe = Factory::getApplication();
		$input = Factory::getApplication()->input;
		$adid = $input->get('adid');
		require_once JPATH_ROOT . '/components/com_socialads/helper.php';
		$socialadshelper = new SocialadsFrontendHelper;
		$itemid = $socialadshelper->getSocialadsItemid('adsummary');
		$model = $this->getModel('adsummary');
		$successCount = $model->delete($adid);

		$mainframe->enqueueMessage($msg, 'success');
		$mainframe->redirect('index.php?option=com_socialads&view=adsummary&Itemid=' . $itemid);
	}
}
