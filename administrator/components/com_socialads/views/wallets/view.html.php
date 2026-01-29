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
use Joomla\CMS\HTML\HTMLHelper;

use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Toolbar\ToolbarHelper;

/**
 * View class for a list of Socialads.
 *
 * @since  1.0
 */
class SocialadsViewWallets extends HtmlView
{
	protected $items;

	protected $params;

	protected $pagination;

	protected $state;

	/**
	 * Display the view
	 *
	 * @param   boolean  $tpl  used to get displayed value
	 *
	 * @return  void
	 */
	public function display($tpl = null)
	{
		$this->state      = $this->get('State');
		$this->items      = $this->get('Items');
		$this->pagination = $this->get('Pagination');
		$this->params     = ComponentHelper::getParams('com_socialads');

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			throw new Exception(implode("\n", $errors));
		}

		SocialadsHelper::addSubmenu('wallets');
		$this->publish_states = array(
			'' => Text::_('JOPTION_SELECT_PUBLISHED'),
			'1'  => Text::_('JPUBLISHED'),
			'0'  => Text::_('JUNPUBLISHED')
		);

		if ($this->params->get('payment_mode') == 'wallet_mode')
		{
			$this->addToolbar();
		}

		$this->sidebar = JHtmlSidebar::render();
		parent::display($tpl);
	}

	/**
	 * Add the page title and toolbar.
	 *
	 * @return  void
	 *
	 * @since  1.6
	 */
	protected function addToolbar()
	{
		require_once JPATH_COMPONENT . '/helpers/socialads.php';

		$state = $this->get('State');
		$canDo = SocialadsHelper::getActions($state->get('filter.category_id'));

		ToolBarHelper::title(Text::_('COM_SOCIALADS') . ': ' . Text::_('COM_SOCIALADS_TITLE_WALETS'), 'list');

		// Check if the form exists before showing the add/edit buttons
		$formPath = JPATH_COMPONENT_ADMINISTRATOR . '/views/adwallet';

		if ($canDo->get('core.admin'))
		{
			ToolBarHelper::preferences('com_socialads');
		}

		// Set sidebar action - New in 3.0
		JHtmlSidebar::setAction('index.php?option=com_socialads&view=wallets');
		$this->extra_sidebar = '';
	}

	/**
	 * Function to get a sorted list
	 *
	 * @return  void
	 */
	protected function getSortFields()
	{
		$params     = ComponentHelper::getParams('com_socialads');

		return array(
			'u.username' => Text::_('COM_SOCIALADS_ADWALETS_USERNAME'),
			'total_spent' => Text::sprintf('COM_SOCIALADS_ADWALETS_SPENT', $params->get('currency', 'USD')),
			'total_earn' => Text::sprintf('COM_SOCIALADS_ADWALETS_EARN', $params->get('currency', 'USD')),
			'total_payment' => Text::sprintf('COM_SOCIALADS_ADWALETS_BALANCE', $params->get('currency', 'USD')),
		);
	}
}
