<?php
/**
 * @version    SVN: <svn_id>
 * @package    TJField
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2014-2016 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Uri\Uri;

/**
 * TJ Fields Controller
 *
 * @since  2.5
 */
class TjfieldsController extends BaseController
{
	/**
	 * The return URL.
	 *
	 * @var    mixed
	 */
	protected $returnURL;

	/**
	 * Constructor
	 *
	 */
	public function __construct()
	{
		$this->returnURL = Uri::base();

		parent::__construct();
	}

	/**
	 * Method to display a view.
	 *
	 * @param   Boolean  $cachable   If true, the view output will be cached
	 * @param   Array    $urlparams  An array of safe url parameters and their variable types, for valid values see
	 *
	 * @return	JController		This object to support chaining.
	 *
	 * @since	1.5
	 */
	public function display($cachable = false, $urlparams = false)
	{
		require_once JPATH_COMPONENT . '/helpers/tjfields.php';

		$view = Factory::getApplication()->input->getCmd('view', 'fields');
		Factory::getApplication()->input->set('view', $view);

		parent::display($cachable, $urlparams);

		return $this;
	}

	/**
	 * Fuction to get download media file
	 *
	 * @return object
	 */
	public function getMediaFile()
	{
		(Session::checkToken() or Session::checkToken('get')) or jexit(Text::_('JINVALID_TOKEN'));
		JLoader::import("/techjoomla/media/storage/local", JPATH_LIBRARIES);
		$app = Factory::getApplication();
		$jinput = $app->input;
		$mediaLocal = TJMediaStorageLocal::getInstance();

		// Here, fpht means file encoded name
		$encodedFileName = $jinput->get('fpht', '', 'STRING');
		$decodedFileName = base64_decode($encodedFileName);

		// Subform File field Id for checking authorization for specific field under subform
		$subformFileFieldId = $jinput->get('subFormFileFieldId', '', 'INT');

		// Get media storage path
		JLoader::import('components.com_tjfields.models.fields', JPATH_SITE);
		$fieldsModel     = BaseDatabaseModel::getInstance('Fields', 'TjfieldsModel', array('ignore_request' => true));
		$data = $fieldsModel->getMediaStoragePath($jinput->get('id', '', 'INT'), $subformFileFieldId);

		if ($data->tjFieldFieldTable->type == "file")
		{
			$extraFieldParams = json_decode($data->tjFieldFieldTable->params);
			$storagePath = $extraFieldParams->uploadpath;
			$decodedPath = $storagePath . '/' . $decodedFileName;
		}
		else
		{
			$fieldType = $data->tjFieldFieldTable->type;
			$decodedPath = JPATH_SITE . '/' . $fieldType . 's/tjmedia/' . str_replace(".", "/", $data->tjFieldFieldTable->client) . '/' . $decodedFileName;
		}

		if ($data->tjFieldFieldTable->fieldValueId)
		{
			$user = Factory::getUser();

			if ($subformFileFieldId)
			{
				$canView = $user->authorise('core.field.viewfieldvalue', 'com_tjfields.field.' . $subformFileFieldId);
			}
			else
			{
				$canView = $user->authorise('core.field.viewfieldvalue', 'com_tjfields.field.' . $data->tjFieldFieldTable->field_id);
			}

			$canDownload = 0;

			// Allow to view own data
			if ($data->tjFieldFieldTable->user_id != null && ($user->id == $data->tjFieldFieldTable->user_id))
			{
				$canDownload = true;
			}

			if ($canView || $canDownload)
			{
				$down_status = $mediaLocal->downloadMedia($decodedPath, '', '', 0);

				if ($down_status === 2)
				{
					$app->enqueueMessage(Text::_('COM_TJFIELDS_FILE_NOT_FOUND'), 'error');
					$app->redirect($this->returnURL);
				}

				return;
			}
			else
			{
				$app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'error');
				$app->redirect($this->returnURL);
			}
		}
		else
		{
			$app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'error');
			$app->redirect($this->returnURL);
		}

		jexit();
	}
}
