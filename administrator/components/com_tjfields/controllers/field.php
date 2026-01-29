<?php
/**
 * @version    SVN: <svn_id>
 * @package    TJ-Fields
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
use Joomla\CMS\Session\Session;

/**
 * Field controller class.
 *
 * @since  1.0
 */
class TjfieldsControllerField extends FormController
{
	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->view_list = 'fields';
		parent::__construct();
	}

	/**
	 * Function to save field data
	 *
	 * @return  void
	 */
	public function newsave()
	{
		$app   = Factory::getApplication();
		$input = $app->input;
		$app->setUserState('com_tjfields.edit.field.data', "");

		$data   = $input->post->get('jform', '', 'ARRAY');
		$model  = $this->getModel('field');
		$form   = $model->getForm($data);
		$data   = $model->validate($form, $data);
		$result = $model->save($data);

		if ($result)
		{
			$msg = Text::_('COMTJFILEDS_FIELD_CREATED_SUCCESSFULLY');
			$link = Route::_(
			'index.php?option=com_tjfields&view=field&layout=edit&client=' . $input->get('client', '', 'STRING'), false
			);
			$this->setRedirect($link, $msg);
		}
		else
		{
			$msg = Text::_('TJFIELDS_ERROR_MSG');
			$this->setMessage(Text::plural($msg, 1));
			$link = Route::_(
			'index.php?option=com_tjfields&view=field&layout=edit&client=' . $input->get('client', '', 'STRING'), false
			);

			$this->setRedirect($link, $msg, 'error');
		}
	}

	/**
	 * Function to save field data
	 *
	 * @param   string  $key     key
	 * @param   string  $urlVar  urlVar
	 *
	 * @return  void
	 */
	public function save($key = null, $urlVar = null)
	{
		$input = Factory::getApplication()->input;
		$task  = $input->get('task', '', 'STRING');

		if ($task == 'apply' or $task == 'save2copy')
		{
			$this->apply();

			return;
		}

		if ($task == 'newsave')
		{
			$this->newsave();

			return;
		}

		$data   = $input->post->get('jform', '', 'ARRAY');
		$model  = $this->getModel('field');
		$form   = $model->getForm($data);
		$data   = $model->validate($form, $data);
		$result = $model->save($data);

		if ($result)
		{
			$msg  = Text::_('COMTJFILEDS_FIELD_CREATED_SUCCESSFULLY');
			$link = Route::_('index.php?option=com_tjfields&view=fields&client=' . $input->get('client', '', 'STRING'), false
			);

			$this->setRedirect($link, $msg);
		}
		else
		{
			$msg = Text::_('TJFIELDS_ERROR_MSG');
			$this->setMessage(Text::plural($msg, 1));
			$link = Route::_('index.php?option=com_tjfields&view=fields&client=' . $input->get('client', '', 'STRING'), false
			);

			$this->setRedirect($link, $msg, 'error');
		}
	}

	/**
	 * Function to apply field data changes
	 *
	 * @return  void
	 */
	public function apply()
	{
		$input = Factory::getApplication()->input;
		$data  = $input->post->get('jform', '', 'ARRAY');
		$model = $this->getModel('field');
		$form  = $model->getForm($data);
		$data  = $model->validate($form, $data);
		$field_id = $model->save($data);

		if ($field_id)
		{
			$msg  = Text::_('COMTJFILEDS_FIELD_CREATED_SUCCESSFULLY');
			$link = Route::_(
			'index.php?option=com_tjfields&view=field&layout=edit&id=' . $field_id . '&client='
			. $input->get('client', '', 'STRING'), false
			);
			$this->setRedirect($link, $msg);
		}
		else
		{
			$msg  = Text::_('TJFIELDS_ERROR_MSG');
			$link = Route::_(
			'index.php?option=com_tjfields&view=field&layout=edit&id=' . $field_id . '&client='
			. $input->get('client', '', 'STRING'), false
			);
			$this->setRedirect($link, $msg, 'error');
		}
	}

	/**
	 * Function to add field data
	 *
	 * @return  void
	 */
	public function add()
	{
		$input = Factory::getApplication()->input;

		$app = Factory::getApplication();
		$app->setUserState('com_tjfields.edit.field.data', "");

		$link = Route::_(
		'index.php?option=com_tjfields&view=field&layout=edit&client=' . $input->get('client', '', 'STRING'), false
		);
		$this->setRedirect($link);
	}

	/**
	 * Function to edit field data
	 *
	 * @param   string  $key     key
	 * @param   string  $urlVar  urlVar
	 *
	 * @return  void
	 */
	public function edit($key = null, $urlVar = null)
	{
		$input    = Factory::getApplication()->input;
		$cid      = $input->post->get('cid', array(), 'array');
		$recordId = (int) (count($cid) ? $cid[0] : $input->getInt('id'));
		$link = Route::_(
		'index.php?option=com_tjfields&view=field&layout=edit&id=' . $recordId . '&client='
		. $input->get('client', '', 'STRING'), false
		);
		$this->setRedirect($link);
	}

	/**
	 * Function to cancel the operation on field
	 *
	 * @param   string  $key  key
	 *
	 * @return  void
	 */
	public function cancel($key = null)
	{
		$input = Factory::getApplication()->input;
		$link = Route::_('index.php?option=com_tjfields&view=fields&client=' . $input->get('client', '', 'STRING'), false
		);
		$this->setRedirect($link);
	}

	/**
	 * Function to save field state
	 *
	 * @return  void
	 */
	public function saveFormState()
	{
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

		$app = Factory::getApplication();

		$data = $this->input->get($this->input->get('formcontrol', 'jform'), array(), 'array');

		if (empty($data['id']))
		{
			$app->setUserState('com_tjfields.edit.field.data', $data);
		}

		$link = Route::_(
		'index.php?option=com_tjfields&view=field&layout=edit&id=0&client='
		. $this->input->get('client', '', 'STRING'), false
			);

		$this->setRedirect($link);
	}
}
