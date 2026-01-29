<?php
/**
 * @version    SVN:<SVN_ID>
 * @package    SocialAds
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2015 TechJoomla. All rights reserved
 * @license    GNU General Public License version 2, or later
 */

// No direct access.
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Router\Route;
use Joomla\CMS\MVC\Controller\AdminController;
use Joomla\Utilities\ArrayHelper;

// Load socialads Controller for list views
require_once __DIR__ . '/salist.php';

/**
 * Campaigns list controller class.
 *
 * @since  3.0
 */
class SocialadsControllerCampaigns extends SocialadsControllerSalist
{
	/**
	 * Proxy for getModel.
	 *
	 * @param   string  $name    The model name. Optional.
	 * @param   string  $prefix  The class prefix. Optional.
	 * @param   array   $config  The array of possible config values. Optional.
	 *
	 * @return  JModelLegacy
	 *
	 * @since   1.6
	 */
	public function getModel($name = 'campaign', $prefix = 'SocialadsModel', $config = array())
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
		$input = Factory::getApplication()->input;
		$pks = $input->post->get('cid', array(), 'array');
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
		Factory::getApplication()->close();
	}

	/**
	 * Method to change status
	 *
	 * @return  void
	 *
	 * @since   3.0
	 */
	public function change_status()
	{
		$input = Factory::getApplication()->input;
		$id = $input->get('campid', 0, 'INT');
		$model = $this->getModel('campaign');
		echo $camp = $model->status($id);
		jexit();
	}

	/**
	 * publish campaign.
	 *
	 * @return void
	 *
	 * @since  3.1.15
	 */
	public function publish()
	{
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));
		$app   = Factory::getApplication();
		$cid   = $app->input->get('cid', '', 'array');
		$data  = array('publish' => 1, 'unpublish' => 0);
		$task  = $app->input->get('task', '', 'STRING');
		$value = ArrayHelper::getValue($data, $task, 0, 'int');

		if (!is_array($cid) || count($cid) < 1)
		{
			$this->setMessage(Text::sprintf('COM_SOCIALADS_NO_ITEM_SELECTED'), 'warning');
		}
		else
		{
			$model = $this->getModel();

			// Make sure the item ids are integers
			ArrayHelper::toInteger($cid);

			// Publish the items.
			try
			{
				$model->publish($cid, $value);

				if ($value === 1)
				{
					$ntext = 'COM_SOCIALADS_N_CAMPAIGNS_PUBLISHED';
				}
				else
				{
					$ntext = 'COM_SOCIALADS_N_CAMPAIGNS_UNPUBLISHED';
				}

				$this->setMessage(Text::plural($ntext, count($cid)));
			}
			catch (Exception $e)
			{
				$this->setMessage($e->getMessage(), 'error');
			}
		}

		$this->setRedirect(Route::_('index.php?option=com_socialads&view=campaigns', false));
	}

	/**
	 * Exports campaign data to a CSV file
	 *
	 * @return void
	 *
	 * @since  5.0.2
	 */
	public function adCsvExport()
	{
		$model   = $this->getModel("campaigns");
		$CSVData = $model->adCsvExport();
	}
}
