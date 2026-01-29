<?php
/**
 * @version    SVN: <svn_id>
 * @package    Com_Tjfields
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2017 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

// No direct access
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Session\Session;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Response\JsonResponse;

jimport('joomla.filesystem.file');

require_once JPATH_SITE . "/components/com_tjfields/filterFields.php";

/**
 * Item controller class.
 *
 * @since  1.4
 */
class TjfieldsControllerFields extends FormController
{
	/**
	 * Delete File .
	 *
	 * @return boolean|string
	 *
	 * @since	1.6
	 */

	public function deleteFile()
	{
		// Check for request forgeries.
		Session::checkToken('get') or Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));
		$app = Factory::getApplication();
		$jinput = $app->input;

		$data = array();
		$data['fileName'] = base64_decode($jinput->get('fileName', '', 'BASE64'));
		$data['valueId'] = base64_decode($jinput->get('valueId', '', 'BASE64'));
		$data['subformFileFieldId'] = $jinput->get('subformFileFieldId');
		$data['isSubformField'] = $jinput->get('isSubformField');

		// Get media storage path
		JLoader::import('components.com_tjfields.models.fields', JPATH_SITE);
		$fieldsModel     = BaseDatabaseModel::getInstance('Fields', 'TjfieldsModel', array('ignore_request' => true));
		$fieldData = $fieldsModel->getMediaStoragePath($data['valueId'], $data['subformFileFieldId']);

		$tjFieldFieldTableParamData = json_decode($fieldData->tjFieldFieldTable->params);
		$client = $fieldData->tjFieldFieldTable->client;
		$type = $fieldData->tjFieldFieldTable->type;
		$uploadPath = isset($tjFieldFieldTableParamData->uploadpath) ? $tjFieldFieldTableParamData->uploadpath : '';
		$data['storagePath'] = ($uploadPath != '') ? $uploadPath : JPATH_SITE . '/' . $type . 's/tjmedia/' . str_replace(".", "/", $client . '/');
		$data['storagePath'] = str_replace('/', DIRECTORY_SEPARATOR, $data['storagePath']);
		$data['client'] = $client;

		require_once JPATH_SITE . '/components/com_tjfields/helpers/tjfields.php';

		$tjFieldsHelper = new TjfieldsHelper;
		$returnValue = $tjFieldsHelper->deleteFile($data);
		$msg = $returnValue ? Text::_('COM_TJFIELDS_FILE_DELETE_SUCCESS') : Text::_('COM_TJFIELDS_FILE_DELETE_ERROR');

		echo new JsonResponse($returnValue, $msg);
	}

	/**
	 * Method to get user list.
	 *
	 * @return   null
	 *
	 * @since    1.6
	 */
	public function getAllUsers()
	{
		// Check for request forgeries.
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

		$userOptions = array();

		// Initialize array to store dropdown options
		$userOptions[] = HTMLHelper::_('select.option', "", Text::_('COM_TJFIELDS_OWNERSHIP_USER'));

		BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_users/models');
		$userModel = BaseDatabaseModel::getInstance('Users', 'UsersModel', array('ignore_request' => true));
		$userModel->setState('filter.state', 0);

		$allUsers = $userModel->getItems();

		if (!empty($allUsers))
		{
			foreach ($allUsers as $user)
			{
				$userOptions[] = HTMLHelper::_('select.option', $user->id, trim($user->username));
			}
		}

		echo new JsonResponse($userOptions);
		jexit();
	}
}
