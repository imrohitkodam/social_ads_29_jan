<?php
/**
 * @version    SVN:<SVN_ID>
 * @package    Com_Socialads
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2015 TechJoomla. All rights reserved
 * @license    GNU General Public License version 2, or later
 */

// No direct access
defined('_JEXEC') or die;

use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use \Joomla\CMS\Language\Text;
use \Joomla\CMS\Uri\Uri;
use \Joomla\CMS\Factory;
use \Joomla\CMS\Component\ComponentHelper;
use \Joomla\CMS\Router\Route;


/**
 * View class for a list of Socialads.
 *
 * @since  1.6
 */
class SocialadsViewDashboard extends HtmlView
{
	public $walletDetails;

	/**
	 * Display the view
	 *
	 * @param   array  $tpl  An optional associative array.
	 *
	 * @return  array
	 *
	 * @since 1.6
	 */
	public function display($tpl = null)
	{
		// Get params
		$this->params = ComponentHelper::getParams('com_socialads');

		// Get stats data for line chart
		$model               = $this->getModel('dashboard');
		$modelWallet         = BaseDatabaseModel::getInstance('Wallet', 'SocialadsModel');
		$this->statsforbar   = $model->getstatsforlinechart();
		$this->activeAds     = $model->getActiveAdCount();
		$this->inactiveAds   = $model->getInactiveAdCount();
		$this->totalSpent    = $model->getAllOrdersIncome();
		$this->topads        = $model->getTopAds();
		$this->pendingorders = $model->getPendingOrders($this->params->get('payment_mode'));
		$this->session       = Factory::getSession();
		$this->user          = Factory::getUser();
		$this->mainframe     = Factory::getApplication();
		$this->input         = Factory::getApplication()->input;
		$this->params        = $this->mainframe->getParams('com_socialads');

		if (!$this->user->id)
		{
			$msg = Text::_('COM_SOCIALADS_LOGIN_MSG');
			$uri = Uri::getInstance()->toString();
			$url = urlencode(base64_encode($uri));
			$itemId = SaCommonHelper::getSocialadsItemid('user');
			$this->mainframe->enqueueMessage($msg, 'success');
			$this->mainframe->redirect(Route::_('index.php?option=com_users&view=login&Itemid=' . $itemId . '&return=' . $url, false));
		}

		$modelWallet->setState('list.limit', $modelWallet->getTotal());
		$modelWallet->setState('user', $this->user->id);
		$this->walletDetails = $modelWallet->getItems();

		parent::display($tpl);
	}
}
