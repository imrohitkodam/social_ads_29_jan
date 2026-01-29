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
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Toolbar\ToolbarHelper;

/**
 * View to edit
 *
 * @since  1.6
 */
class SocialadsViewCampaign extends HtmlView
{
	protected $state;

	protected $item;

	protected $form;

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
		$this->state = $this->get('State');
		$this->item = $this->get('Item');
		$this->form = $this->get('Form');
		$user = Factory::getUser();

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			throw new Exception(implode("\n", $errors));
		}

		if (! Factory::getUser($user->id)->authorise('core.edit', 'com_socialads'))
		{
			$app = Factory::getApplication();
			$app->enqueueMessage(Text::_('COM_SOCIALADS_AUTH_ERROR'), 'warning');
		}

		$this->addToolbar();
		parent::display($tpl);
	}

	/**
	 * Add the page title and toolbar.
	 *
	 * @return  array
	 *
	 * @since 1.6
	 */
	protected function addToolbar()
	{
		Factory::getApplication()->input->set('hidemainmenu', true);
		$user = Factory::getUser();
		$isNew = ($this->item->id == 0);

		if ($isNew)
		{
			$viewTitle = Text::_('COM_SOCIALADS_ADS_AD_CAMPAGIN');
		}
		else
		{
			$viewTitle = Text::_('COM_SOCIALADS_ADS_EDIT_CAMPAGIN');
		}

		if (isset($this->item->checked_out))
		{
			$checkedOut = !($this->item->checked_out == 0 || $this->item->checked_out == $user->get('id'));
		}
		else
		{
			$checkedOut = false;
		}

		$canDo = SocialadsHelper::getActions();

		ToolbarHelper::title($viewTitle, 'pencil-2');

		// If not checked out, can save the item.
		if (!$checkedOut && ($canDo->get('core.edit') || ($canDo->get('core.create'))))
		{
			ToolBarHelper::apply('campaign.apply', 'JTOOLBAR_APPLY');
			ToolBarHelper::save('campaign.save', 'JTOOLBAR_SAVE');
		}

		if (!$checkedOut && ($canDo->get('core.create')))
		{
			ToolBarHelper::custom('campaign.save2new', 'save-new.png', 'save-new_f2.png', 'JTOOLBAR_SAVE_AND_NEW', false);
		}

		if (empty($this->item->id))
		{
			ToolBarHelper::cancel('campaign.cancel', 'JTOOLBAR_CANCEL');
		}
		else
		{
			ToolBarHelper::cancel('campaign.cancel', 'JTOOLBAR_CLOSE');
		}
	}
}
