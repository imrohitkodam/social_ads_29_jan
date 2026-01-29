<?php
/**
 * @version    SVN: <svn_id>
 * @package    TJ-Fields
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2016 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

// No direct access.
defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\MVC\Controller\AdminController;
use Joomla\CMS\Session\Session;
use Joomla\Utilities\ArrayHelper;

JLoader::import('TjfieldsHelper', JPATH_ADMINISTRATOR . '/components/com_tjfields/helpers');
JLoader::register('TjControllerHouseKeeping', JPATH_SITE . "/libraries/techjoomla/controller/houseKeeping.php");

/**
 * Fields list controller class.
 *
 * @since  1.0
 */
class TjfieldsControllerFields extends AdminController
{
	use TjControllerHouseKeeping;

	/**
	 * Proxy for getModel.
	 *
	 * @param   string  $name    key
	 * @param   string  $prefix  urlVar
	 *
	 * @since   1.0
	 *
	 * @return  object
	 */
	public function getModel($name = 'field', $prefix = 'TjfieldsModel', $config = array())
	{
		$model = parent::getModel($name, $prefix, array('ignore_request' => true));

		return $model;
	}

	/**
	 * Method to save the submitted ordering values for records via AJAX.
	 *
	 * @return  void
	 *
	 * @since   3.0
	 */
	public function saveOrderAjax()
	{
		// Get the input
		$app   = Factory::getApplication();
		$input = $app->input;
		$pks   = $input->post->get('cid', array(), 'array');
		$order = $input->post->get('order', array(), 'array');

		// Sanitize the input
		ArrayHelper::toInteger($pks);
		ArrayHelper::toInteger($order);

		// Get the model
		$model = $this->getModel();

		// Save the ordering
		$return = $model->saveorder($pks, $order);

		if ($return)
		{
			echo "1";
		}

		// Close the application
		$app->close();
	}

	/**
	 * Function to publish fields
	 *
	 * @return  void
	 */
	public function publish()
	{
		$app    = Factory::getApplication();
		$input  = $app->input;
		$post   = $input->post;
		$client = $input->get('client', '', 'STRING');
		$cid    = $app->input->get('cid', array(), 'array');
		$data   = array('publish' => 1, 'unpublish' => 0, 'archive' => 2, 'trash' => -2, 'report' => -3);
		$task   = $this->getTask();
		$value  = ArrayHelper::getValue($data, $task, 0, 'int');

		// Get some variables from the request

		if (empty($cid))
		{
			Log::add(Text::_($this->text_prefix . '_NO_ITEM_SELECTED'), Log::WARNING, 'jerror');
		}
		else
		{
			// Get the model.
			$model = $this->getModel('fields');

			// Make sure the item ids are integers
			ArrayHelper::toInteger($cid);

			// Publish the items.
			try
			{
				$model->setItemState($cid, $value);

				if ($value == 1)
				{
					$ntext = $this->text_prefix . '_N_ITEMS_PUBLISHED';
				}
				elseif ($value == 0)
				{
					$ntext = $this->text_prefix . '_N_ITEMS_UNPUBLISHED';
				}
				elseif ($value == 2)
				{
					$ntext = $this->text_prefix . '_N_ITEMS_ARCHIVED';
				}
				else
				{
					$ntext = $this->text_prefix . '_N_ITEMS_TRASHED';
				}

				// Generate xml here
				$TjfieldsHelper = new TjfieldsHelper;
				$client_form    = explode('.', $client);
				$client_type    = $client_form[1];

				$data = array();
				$data['client'] = $client;
				$data['client_type'] = $client_type;
				$TjfieldsHelper->generateXml($data);

				$this->setMessage(Text::plural($ntext, count($cid)));
			}
			catch (Exception $e)
			{
				$this->setMessage(Text::_('JLIB_DATABASE_ERROR_ANCESTOR_NODES_LOWER_STATE'), 'error');
			}
		}

		$this->setRedirect('index.php?option=com_tjfields&view=fields&client=' . $client, $msg);
	}

	/**
	 * Function to deete fields
	 *
	 * @return  void
	 */
	public function delete()
	{
		// Check for request forgeries
		Session::checkToken() or die(Text::_('JINVALID_TOKEN'));

		// GET CLIENT AND CLIENT TYPE
		$app         = Factory::getApplication();
		$input       = $app->input;
		$client      = $input->get('client', '', 'STRING');
		$client_form = explode('.', $client);
		$client_type = $client_form[1];

		// Get items to remove from the request.
		$cid = $app->input->get('cid', array(), 'array');

		if (!is_array($cid) || count($cid) < 1)
		{
			Log::add(Text::_($this->text_prefix . '_NO_ITEM_SELECTED'), Log::WARNING, 'jerror');
		}
		else
		{
			// Get the model.
			$model = $this->getModel('fields');

			// Make sure the item ids are integers
			ArrayHelper::toInteger($cid);

			// Remove the items.
			if ($model->deletefield($cid))
			{
				$model_field = $this->getModel('field');
				$model_field->deleteFieldCategoriesMapping($cid);
				$TjfieldsHelper = new TjfieldsHelper;

				$data                = array();
				$data['client']      = $client;
				$data['client_type'] = $client_type;
				$TjfieldsHelper->generateXml($data);
				$ntext = $this->text_prefix . '_N_ITEMS_DELETED';
			}
			else
			{
				$this->setMessage($model->getError());
			}
		}

		$this->setMessage(Text::plural($ntext, count($cid)));
		$this->setRedirect('index.php?option=com_tjfields&view=fields&client=' . $client, false);
	}
}
