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

use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Session\Session;

/**
 * Campaign controller class.
 *
 * @since  1.6
 */
class SocialadsControllerCampaign extends FormController
{
	/**
	 * Constructor.
	 *
	 * @see  JController
	 *
	 * @since  1.6
	 */
	public function __construct()
	{
		$this->view_list = 'campaigns';
		parent::__construct();
	}

	/**
	 * Redirect to create new campaign view
	 *
	 * @return  void
	 *
	 * @since  1.6
	 */
	public function addNew()
	{
		$input = Factory::getApplication()->input;
		$redirect = Route::_('index.php?option=com_socialads&view=campaign&layout=edit', false);
		$this->setRedirect($redirect, '');
	}

	/**
	 * Method to save a campaign data.
	 *
	 * @param   string  $key     The name of the primary key of the URL variable.
	 * @param   string  $urlVar  The name of the URL variable if different from the primary key (sometimes required to avoid router collisions).
	 *
	 * @return  void|boolean
	 *
	 * @since    3.1.13
	 */
	public function save($key = null, $urlVar = null)
	{
		// Check for request forgeries.
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

		// Initialise variables.
		$app        = Factory::getApplication();
		$model      = $this->getModel('campaign', 'SocialadsModel');
		$CampaignId = $app->input->get('id', 0, 'INT');

		// Get the Campaign data.
		$allJformData = $app->input->get('jform', array(), 'array');
		$form         = $model->getForm();

		if (!$form)
		{
			$app->enqueueMessage($model->getError(), 'error');

			return false;
		}

		// Validate the posted data.
		$data       = $model->validate($form, $allJformData);
		$data['id'] = $CampaignId;
		$return     = $model->save($data);

		// Check for errors.
		if ($return === false)
		{
			/* Save the data in the session.*/
			$app->setUserState('com_socialads.edit.campaign.data', $allJformData);

			// Redirect back to the edit screen.
			$id = (int) $app->getUserState('com_socialads.edit.campaign.id');
			$this->setMessage(Text::sprintf('COM_SOCIALADS_CAMPAIGN_ERROR_MSG_SAVE', $model->getError()), 'error');
			$this->setRedirect(Route::_('index.php?option=com_socialads&view=campaign&layout=edit&id=' . $id, false));

			return false;
		}

		$msg   = Text::_('COM_SOCIALADS_CAMPAIGN_SUCCESS_MSG_SAVE');
		$input = Factory::getApplication()->input;
		$id    = $input->get('id');

		if (empty($id))
		{
			$id = $return;
		}

		$task = $input->get('task');

		if ($task == 'apply')
		{
			$redirect = Route::_('index.php?option=com_socialads&view=campaign&layout=edit&id=' . $id, false);
			$app->enqueueMessage($msg, 'success');
			$app->redirect($redirect);
		}

		if ($task == 'save2new')
		{
			$redirect = Route::_('index.php?option=com_socialads&view=campaign&layout=edit', false);
			$app->enqueueMessage($msg, 'success');
			$app->redirect($redirect);			
		}

		// Clear the campaign id from the session.
		$app->setUserState('com_socialads.edit.campaign.id', null);

		// Check in the campaign.
		if ($return)
		{
			$model->checkin($return);
		}

		// Redirect to the list screen.
		$redirect = Route::_('index.php?option=com_socialads&view=campaigns', false);
		$app->enqueueMessage($msg, 'success');
		$app->redirect($redirect);

		// Flush the data from the session.
		$app->setUserState('com_jmailalerts.edit.subscriber.data', null);
	}
}
