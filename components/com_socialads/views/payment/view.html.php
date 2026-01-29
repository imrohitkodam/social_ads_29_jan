<?php
/**
 * @version    SVN:<SVN_ID>
 * @package    Com_Socialads
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2015 TechJoomla. All rights reserved
 * @license    GNU General Public License version 2, or later
 */

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\MVC\View\HtmlView;

// Import Joomla view library

use \Joomla\CMS\Language\Text;
use \Joomla\CMS\Uri\Uri;
use \Joomla\CMS\Factory;
use \Joomla\CMS\Component\ComponentHelper;
use \Joomla\CMS\Router\Route;
use Joomla\CMS\Plugin\PluginHelper;

/**
 * HTML View class for the Aniket Component
 *
 * @since  1.6
 */
class SocialadsViewPayment extends HtmlView
{
	protected $form;

	/**
	 * Overwriting JView display method
	 *
	 * @param   boolean  $tpl  used to get displayed value
	 *
	 * @return  void
	 *
	 * @since  1.6
	 **/
	public function display($tpl = null)
	{
		$user       = Factory::getUser();
		$this->form = $this->get('Form');
		$app = Factory::getApplication();

		if (!$user->id)
		{
			$msg = Text::_('COM_SOCIALADS_LOGIN_MSG');
			$uri = Uri::getInstance()->toString();
			$url = urlencode(base64_encode($uri));
			$itemId = SaCommonHelper::getSocialadsItemid('user');
			$app->enqueueMessage($msg, 'success');
			$app->redirect(Route::_('index.php?option=com_users&view=login&Itemid=' . $itemId . '&return=' . $url, false));
		}

		if (!Factory::getUser($user->id)->authorise('core.create', 'com_socialads'))
		{
			$app->enqueueMessage(Text::_('COM_SOCIALADS_WALLET_NO_AUTH_SEE'), 'warning');

			return false;
		}

		$params = ComponentHelper::getParams('com_socialads');
		PluginHelper::importPlugin('payment');
		$payment_mode = $params->get('payment_mode');

		$currentBSViews = $params->get('bootstrap_version', "bs3");
		$this->bsVersion = $currentBSViews;

		// If payment mode is payperadd the restrict the access
		if ($payment_mode == 'pay_per_ad_mode')
		{
			$app->enqueueMessage(Text::_('COM_SOCIALADS_AUTH_ERROR'), 'warning');

			return false;
		}

		$gatewayplugin       = $this->get('APIpluginData');
		$this->gatewayplugin = $gatewayplugin;
		$this->setLayout('edit');
		parent::display($tpl);
	}
}
