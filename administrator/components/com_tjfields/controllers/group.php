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
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Router\Route;

/**
 * Group controller class.
 *
 * @since  1.0
 */
class TjfieldsControllerGroup extends FormController
{
	/**
	 * Constructor
	 *
	 */
	public function __construct()
	{
		$this->view_list = 'groups';
		parent::__construct();
	}

	/**
	 * Method to apply changes group details
	 *
	 * @return  null
	 */
	public function apply()
	{
		$input    = Factory::getApplication()->input;
		$data     = $input->post->get('jform', '', 'ARRAY');
		$model    = $this->getModel('group');
		$if_saved = $model->save($data);

		if ($if_saved)
		{
			$msg  = Text::_('COMTJFILEDS_GROUP_CREATED_SUCCESSFULLY');
			$link = Route::_('index.php?option=com_tjfields&view=group&layout=edit', false);
			$link .= '&client=' . $input->get('client', '', 'STRING') . '&id=' . $if_saved;
			$this->setRedirect($link, $msg);
		}
		else
		{
			$msg = Text::_('TJFIELDS_ERROR_MSG');
			$this->setMessage(Text::plural($msg, 1));
			$link = Route::_('index.php?option=com_tjfields&view=group&layout=edit', false);
			$link .= '&client=' . $input->get('client', '', 'STRING') . '&id=' . $input->get('id');
			$this->setRedirect($link, $msg, 'error');
		}
	}

	/**
	 * Method to save group details
	 *
	 * @return  null
	 */
	public function save($key = null, $urlVar = null)
	{
		$input = Factory::getApplication()->input;
		$task  = $input->get('task', '', 'STRING');
		$data  = $input->post->get('jform', '', 'ARRAY');
		$model = $this->getModel('group');

		if ($task == 'apply' or $task == 'save2copy')
		{
			$this->apply();

			return;
		}

		$if_saved = $model->save($data);

		if ($task == 'newsave')
		{
			$this->newsave();

			return;
		}

		if ($if_saved)
		{
			$msg  = Text::_('COMTJFILEDS_GROUP_CREATED_SUCCESSFULLY');
			$link = Route::_('index.php?option=com_tjfields&view=groups&client=' . $input->get('client', '', 'STRING'), false);
			$this->setRedirect($link, $msg);
		}
		else
		{
			$msg  = Text::_('TJFIELDS_ERROR_MSG');
			$link = Route::_('index.php?option=com_tjfields&view=groups&client=' . $input->get('client', '', 'STRING'), false);
			$this->setRedirect($link, $msg, 'error');
		}
	}

	/**
	 * Method to save group details
	 *
	 * @return  null
	 */
	public function newsave()
	{
		$input    = Factory::getApplication()->input;
		$data     = $input->post->get('jform', '', 'ARRAY');
		$model    = $this->getModel('group');
		$group_id = $model->save($data);

		if ($group_id)
		{
			$msg  = Text::_('COMTJFILEDS_GROUP_CREATED_SUCCESSFULLY');
			$link = Route::_('index.php?option=com_tjfields&view=group&layout=edit&client=' . $input->get('client', '', 'STRING'), false);
			$this->setRedirect($link, $msg, 'success');
		}
		else
		{
			$msg  = Text::_('TJFIELDS_ERROR_MSG');
			$link = Route::_('index.php?option=com_tjfields&view=group&layout=edit&client=' . $input->get('client', '', 'STRING'), false);
			$this->setRedirect($link, $msg, 'error');
		}
	}

	/**
	 * Method to add group
	 *
	 * @return  null
	 */
	public function add()
	{
		$input = Factory::getApplication()->input;
		$link  = Route::_('index.php?option=com_tjfields&view=group&layout=edit&client=' . $input->get('client', '', 'STRING'), false);
		$this->setRedirect($link);
	}

	/**
	 * Method to edit group details
	 *
	 * @return  null
	 */
	public function edit($key = null, $urlVar = null)
	{
		$input    = Factory::getApplication()->input;
		$cid      = $input->post->get('cid', array(), 'array');
		$recordId = (int) (count($cid) ? $cid[0] : $input->getInt('id'));
		$link     = Route::_('index.php?option=com_tjfields&view=group&layout=edit&id=' . $recordId, false);
		$link .= '&client=' . $input->get('client', '', 'STRING');
		$this->setRedirect($link);
	}

	/**
	 * Method to cancel group creation
	 *
	 * @return  null
	 */
	public function cancel($key = null)
	{
		$input = Factory::getApplication()->input;
		$link = Route::_('index.php?option=com_tjfields&view=groups&client=' . $input->get('client', '', 'STRING'), false);
		$this->setRedirect($link);
	}
}
