<?php
/**
 * @version    SVN:<SVN_ID>
 * @package    Com_Socialads
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2015 TechJoomla. All rights reserved
 * @license    GNU General Public License version 2, or later
 */

// No direct access to this file
defined('_JEXEC') or die;

use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\HTML\HTMLHelper;


use \Joomla\CMS\Language\Text;
use \Joomla\CMS\Uri\Uri;
use \Joomla\CMS\Factory;
use \Joomla\CMS\Router\Route;

/**
 * HTML View class for the My orders
 *
 * @since  1.6
 */
class SocialadsViewMyorders extends HtmlView
{
	protected $items;

	protected $pagination;

	protected $state;

	protected $params;

	/**
	 * Display the view
	 *
	 * @param   string  $tpl  Template name
	 *
	 * @return void
	 *
	 * @throws Exception
	 */
	public function display($tpl = null)
	{
		$app = Factory::getApplication();
		$this->state      = $this->get('State');
		$this->items = $this->get('Items');
		$this->pagination = $this->get('Pagination');
		$this->params     = $app->getParams('com_socialads');
		$this->user = Factory::getUser();
		$this->input = Factory::getApplication()->input;
		$this->session = Factory::getSession();
		$this->mainframe = Factory::getApplication();
		$this->myordersItemid = SaCommonHelper::getSocialadsItemid('myorders');

		if (!$this->user->id)
		{
			$msg = Text::_('COM_SOCIALADS_LOGIN_MSG');
			$uri = Uri::getInstance()->toString();
			$url = urlencode(base64_encode($uri));
			$itemId = SaCommonHelper::getSocialadsItemid('user');
			$this->mainframe->enqueueMessage($msg, 'success');
			$this->mainframe->redirect(Route::_('index.php?option=com_users&view=login&Itemid=' . $itemId . '&return=' . $url, false));
		}

		$payment_mode = $this->params->get('payment_mode');

		if ($payment_mode == 'wallet_mode')
		{
			$app->enqueueMessage(Text::_('COM_SOCIALADS_WALLET_NO_AUTH_SEE'), 'warning');

			return false;
		}

		$currentBSViews = $this->params->get('bootstrap_version', "bs3");
		$this->bsVersion = $currentBSViews;

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			throw new Exception(implode("\n", $errors));
		}

		$this->ostatus = array();
		$this->ostatus[] = HTMLHelper::_('select.option', '', Text::_('COM_SOCIALADS_ORDERS_APPROVAL_STATUS'));
		$this->ostatus[] = HTMLHelper::_('select.option', 'P',  Text::_('COM_SOCIALADS_SA_PENDIN'));
		$this->ostatus[] = HTMLHelper::_('select.option', 'C',  Text::_('COM_SOCIALADS_SA_CONFIRM'));
		$this->ostatus[] = HTMLHelper::_('select.option', 'RF',  Text::_('COM_SOCIALADS_SA_REFUND'));
		$this->ostatus[] = HTMLHelper::_('select.option', 'E', Text::_('COM_SOCIALADS_SA_REJECTED'));

		$this->_prepareDocument();
		parent::display($tpl);
	}

	/**
	 * Prepares the document
	 *
	 * @return void
	 *
	 * @throws Exception
	 */
	protected function _prepareDocument()
	{
		$app   = Factory::getApplication();
		$menus = $app->getMenu();
		$title = null;

		// Because the application sets a default page title,
		// we need to get it from the menu item itself
		$menu = $menus->getActive();

		if ($menu)
		{
			$this->params->def('page_heading', $this->params->get('page_title', $menu->title));
		}
		else
		{
			$this->params->def('page_heading', Text::_('COM_SOCIALADS_DEFAULT_PAGE_TITLE'));
		}

		$title = $this->params->get('page_title', '');

		if (empty($title))
		{
			$title = $app->get('sitename');
		}
		elseif ($app->get('sitename_pagetitles', 0) == 1)
		{
			$title = Text::sprintf('JPAGETITLE', $app->get('sitename'), $title);
		}
		elseif ($app->get('sitename_pagetitles', 0) == 2)
		{
			$title = Text::sprintf('JPAGETITLE', $title, $app->get('sitename'));
		}

		$this->document->setTitle($title);

		if ($this->params->get('menu-meta_description'))
		{
			$this->document->setDescription($this->params->get('menu-meta_description'));
		}

		if ($this->params->get('menu-meta_keywords'))
		{
			$this->document->setMetadata('keywords', $this->params->get('menu-meta_keywords'));
		}

		if ($this->params->get('robots'))
		{
			$this->document->setMetadata('robots', $this->params->get('robots'));
		}
	}

	/**
	 * Check if state is set
	 *
	 * @param   mixed  $state  State
	 *
	 * @return bool
	 */
	public function getState($state)
	{
		return isset($this->state->{$state}) ? $this->state->{$state} : false;
	}
}
