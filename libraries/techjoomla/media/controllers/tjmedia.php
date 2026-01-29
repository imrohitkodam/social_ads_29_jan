<?php
/**
 * @package    Techjoomla_Library
 *
 * @copyright  Copyright (C) 2009 - 2018 Techjoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

defined('JPATH_PLATFORM') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Language\Text;

JLoader::import("/techjoomla/media/storage/local", JPATH_LIBRARIES);
JLoader::import("/techjoomla/media/xref", JPATH_LIBRARIES);

/**
 * TjMedia trait.
 *
 * @since  1.0.0
 */
trait TJMediaController
{
	/**
	 * Method to Add record on respective data source
	 *
	 * @return  mixed
	 *
	 * @since   1.0.0
	 */
	public function uploadMedia()
	{
		Session::checkToken() or die('Invalid Token');
		$app      = Factory::getApplication();
		$input    = $app->input;
		$files    = $input->files->get('file', '', 'array');
		$fileType = array();

		if (isset($files[0]))
		{
			$fileType = explode("/", $files[0]['type']);
		}

		// Image, video, application specific validation
		if (!empty($fileType[0]) && in_array($fileType[0], array('video', 'image', 'application')))
		{
			$config               = array();
			$extension            = $input->get('extension', '', 'string');
			$acl                  = $input->get('acl', '', 'string');
			$config['id']         = $input->get('media_id', '', 'int');
			$config['title']      = $input->get('title', '', 'string');
			$config['uploadPath'] = JPATH_SITE . '/' . $input->get('uploadPath', '', 'string');
			$config['oldData']    = $input->get('oldData', '', 'int');
			$config['saveData']   = $input->get('saveData', '', 'int');
			$config['size']       = $input->get('size', '', 'int');
			$config['storage']    = $input->get('storage', '', 'string');
			$config['state']      = $input->get('state', '', 'int');
			$config['access']     = $input->get('access', '', 'int');
			$config['params']     = $input->get('params', '', 'string');
			$allowedType          = $input->get('allowedType', '', 'string');
			$allowedext           = $input->get('allowedext', '', 'string');

			if (!empty($allowedType))
			{
				$config['type'] = array_map('trim', explode(',', $allowedType));
			}

			if (!empty($allowedext))
			{
				$config['allowedExtension'] = array_map('trim', explode(',', $allowedext));
			}

			$config['auth'] = 0;

			if (!empty($acl))
			{
				if (Factory::getUser()->authorise($acl, $extension))
				{
					$config['auth'] = 1;
				}
			}
			else
			{
				if (Factory::getUser()->authorise('core.edit', $extension) && Factory::getUser()->authorise('core.create', $extension))
				{
					$config['auth'] = 1;
				}
			}

			$mediaLib = TJMediaStorageLocal::getInstance($config);
			$return   = '';

			if (is_array($files))
			{
				$return   = $mediaLib->upload($files);
			}

			// Check for errors
			if (count($errors = $mediaLib->getErrors()))
			{
				echo new JsonResponse(null, implode("\n", $errors), true);
				$app->close();
			}

			echo new JsonResponse($return, Text::_('LIB_TECHJOOMLAMEDIA_FILE_UPLOADED'));
			$app->close();
		}
		else
		{
			echo new JsonResponse(null, Text::_('LIB_TECHJOOMLA_MEDIA_INVALID_FILE_TYPE_ERROR'), true);
			$app->close();
		}
	}
}
