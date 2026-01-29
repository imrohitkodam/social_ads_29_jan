<?php
/**
 * @version    SVN: <svn_id>
 * @package    Tjfields
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2016 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Toolbar\ToolbarHelper;

jimport('joomla.application.component.view');

/**
 * View class for editing field.
 *
 * @package     Tjfields
 * @subpackage  com_tjfields
 * @since       2.2
 */
class TjfieldsViewField extends HtmlView
{
	protected $state;

	protected $item;

	protected $form;

	/**
	 * Display the view
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  void
	 */
	public function display($tpl = null)
	{
		$this->state = $this->get('State');
		$this->item  = $this->get('Item');
		$this->form  = $this->get('Form');

		if (in_array($this->form->getValue('type'), array('radio', 'single_select', 'multi_select', 'tjlist')))
		{
			$this->form->setFieldAttribute('fieldoption', 'required', true);
		}

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			throw new Exception(implode("\n", $errors));
		}

		$this->addToolbar();
		parent::display($tpl);
	}

	/**
	 * Add the page title and toolbar.
	 *
	 * @return  void
	 *
	 * @since   1.6
	 */
	protected function addToolbar()
	{
		$input           = Factory::getApplication()->input;
		$input->set('hidemainmenu', true);
		$user  = Factory::getUser();
		$isNew = ($this->item->id == 0);

		if (isset($this->item->checked_out))
		{
			$checkedOut = !($this->item->checked_out == 0 || $this->item->checked_out == $user->get('id'));
		}
		else
		{
			$checkedOut = false;
		}

		$client          = $input->get('client');
		$extention = explode('.', $client);
		$canDo           = TjfieldsHelper::getActions($extention[0], 'field');
		$component_title = Text::_('COM_TJFIELDS_TITLE_COMPONENT');

		if ($isNew)
		{
			$viewTitle = Text::_('COM_TJFIELDS_ADD_FIELD');
		}
		else
		{
			$viewTitle = Text::_('COM_TJFIELDS_EDIT_FIELD');
		}

		if (JVERSION >= '3.0')
		{
			ToolbarHelper::title($component_title . $viewTitle, 'pencil-2');
		}
		else
		{
			ToolbarHelper::title($component_title . $viewTitle, 'field.png');
		}

		// If not checked out, can save the item.
		if (!$checkedOut && ($canDo->get('core.edit') || ($canDo->get('core.create'))))
		{
			JToolBarHelper::apply('field.apply', 'JTOOLBAR_APPLY');
			JToolBarHelper::save('field.save', 'JTOOLBAR_SAVE');
		}

		if (!$checkedOut && ($canDo->get('core.create')))
		{
			JToolBarHelper::custom('field.newsave', 'save-new.png', 'save-new_f2.png', 'JTOOLBAR_SAVE_AND_NEW', false);
		}

		// If an existing item, can save to a copy.
		if (!$isNew && $canDo->get('core.create'))
		{
			JToolBarHelper::custom('field.save2copy', 'save-copy.png', 'save-copy_f2.png', 'JTOOLBAR_SAVE_AS_COPY', false);
		}

		if (empty($this->item->id))
		{
			JToolBarHelper::cancel('field.cancel', 'JTOOLBAR_CANCEL');
		}
		else
		{
			JToolBarHelper::cancel('field.cancel', 'JTOOLBAR_CLOSE');
		}
	}
}
