<?php
/**
 * @version    SVN:<SVN_ID>
 * @package    SocialAds
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2015 TechJoomla. All rights reserved
 * @license    GNU General Public License version 2, or later
 */

// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\Toolbar\Toolbar;

use \Joomla\CMS\Language\Text;
use \Joomla\CMS\Uri\Uri;
use \Joomla\CMS\Factory;
use \Joomla\CMS\Router\Route;
use Joomla\CMS\MVC\View\HtmlView;



/**
 * View class for a list of Socialads.
 *
 * @since  1.6
 */
class SocialadsViewCampaigns extends HtmlView
{
	protected $items;

	protected $pagination;

	protected $state;

	protected $params;

	/**
	 * Display the view
	 *
	 * @param   STRING  $tpl  layout
	 *
	 * @return view
	 */
	public function display($tpl = null)
	{
		$user = Factory::getUser();
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

		if (! Factory::getUser($user->id)->authorise('core.create', 'com_socialads'))
		{
			$app->enqueueMessage(Text::_('COM_SOCIALADS_WALLET_NO_AUTH_SEE'), 'warning');

			return false;
		}

		$app = Factory::getApplication();
		$this->state      = $this->get('State');
		$this->items      = $this->get('Items');
		$this->pagination = $this->get('Pagination');
		$this->params     = $app->getParams('com_socialads');

		$currentBSViews = $this->params->get('bootstrap_version', "bs3");
		$this->bsVersion = $currentBSViews;
		$this->campaignsItemId = SaCommonHelper::getSocialadsItemid('campaigns');

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			throw new Exception(implode("\n", $errors));
		}

		// Setup toolbar
		$this->addTJtoolbar();

		$this->_prepareDocument();
		parent::display($tpl);
	}

	/**
	 * Setup ACL based tjtoolbar
	 *
	 * @return  void
	 *
	 * @since   2.2
	 */
	protected function addTJtoolbar()
	{
		require_once JPATH_ADMINISTRATOR . '/components/com_socialads/helpers/socialads.php';
		$canDo = SocialadsHelper::getActions();

		// Add toolbar buttons
		jimport('techjoomla.tjtoolbar.toolbar');
		$class = $this->bsVersion == 'bs3' ? 'pull-right' : 'float-end';
		$tjbar = TJToolbar::getInstance('tjtoolbar', $class);

		if ($canDo->get('core.create'))
		{
			$tjbar->appendButton('campaignform.addNew', 'TJTOOLBAR_NEW', '', 'class="btn btn-sm btn-success"');
		}

		if ($canDo->get('core.edit') && isset($this->items[0]))
		{
			$tjbar->appendButton('campaignForm.edit', 'TJTOOLBAR_EDIT', '', 'class="btn btn-sm btn-success"');
		}

		if ($canDo->get('core.edit.state'))
		{
			if (isset($this->items[0]))
			{
				$tjbar->appendButton('campaigns.publish', 'TJTOOLBAR_PUBLISH', '', 'class="btn btn-sm btn-success"');
				$tjbar->appendButton('campaigns.unpublish', 'TJTOOLBAR_UNPUBLISH', '', 'class="btn btn-sm btn-warning"');
			}
		}

		if ($canDo->get('core.delete'))
		{
			if (isset($this->items[0]))
			{
				$tjbar->appendButton('campaigns.delete', 'TJTOOLBAR_DELETE', '', 'class="btn btn-sm btn-danger"');
			}
		}

		$this->toolbarHTML = $tjbar->render();
	}

	/**
	 * Prepares the document
	 *
	 * @return void
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
}
