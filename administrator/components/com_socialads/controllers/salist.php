<?php
/**
 * @version    SVN: <svn_id>
 * @package    Socialads
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2015 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

// No direct access
defined('_JEXEC') or die();

use Joomla\CMS\MVC\Controller\AdminController;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Session\Session;
use Joomla\Utilities\ArrayHelper;

/**
 * Lengths list controller class.
 *
 * @package     Socialads
 * @subpackage  com_socialads
 * @since       1.7
 */
class SocialadsControllerSalist extends AdminController
{
	/**
	 * Constructor.
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 *
	 * @see     JController
	 * @since   1.6
	 */
	public function __construct($config = array())
	{
		parent::__construct($config);

		$this->registerTask('unfeatured', 'featured');
	}

	/**
	 * Method to publish records.
	 *
	 * @return void
	 *
	 * @since 1.7
	 */
	public function publish()
	{
		$id = Factory::getApplication()->input->get('cid', array(), 'array');

		$data = array(
			'publish' => 1,
			'unpublish' => 0,
			'archive' => 2,
			'trash' => -2,
			'report' => -3
		);

		$task = $this->getTask();
		$value = ArrayHelper::getValue($data, $task, 0, 'int');

		// Get called controller name
		$controllerName = get_called_class();
		$controllerName = str_split($controllerName, strlen('SocialadsController'));
		$currentController = $controllerName[1];
		$currentListView = strtolower($currentController);

		// Get called controller's - singular and plural names
		$singular_name = Text::_('COM_SOCIALADS_SINGULAR_' . strtoupper($currentController));
		$plural_name   = Text::_('COM_SOCIALADS_PLURAL_' . strtoupper($currentController));

		// Get some variables from the request
		if (empty($id))
		{
			Log::add(Text::sprintf('COM_SOCIALADS_NO_ITEMS_SELECTED', $plural_name), Log::WARNING, 'jerror');
		}
		else
		{
			// Get the model.
			$model = $this->getModel();

			// Make sure the item ids are integers
			ArrayHelper::toInteger($id);

			// Publish the items.
			try
			{
				$model->publish($id, $value);
				$count = count($id);

				// Multiple records.
				if ($count > 1)
				{
					if ($value === 1)
					{
						$ntext = Text::sprintf('COM_SOCIALADS_N_ITEMS_PUBLISHED_1', $count, $plural_name);
					}
					elseif ($value === 0)
					{
						$ntext = Text::sprintf('COM_SOCIALADS_N_ITEMS_UNPUBLISHED_1', $count, $plural_name);
					}
					elseif ($value == 2)
					{
						$ntext = Text::sprintf('COM_SOCIALADS_N_ITEMS_ARCHIVED', $count, $plural_name);
					}
					else
					{
						$ntext = Text::sprintf('COM_SOCIALADS_N_ITEMS_TRASHED', $count, $plural_name);
					}
				}
				// Single record.
				else
				{
					if ($value === 1)
					{
						$ntext = Text::sprintf('COM_SOCIALADS_N_ITEMS_PUBLISHED', $count, $singular_name);
					}
					elseif ($value === 0)
					{
						$ntext = Text::sprintf('COM_SOCIALADS_N_ITEMS_UNPUBLISHED', $count, $singular_name);
					}
					elseif ($value == 2)
					{
						$ntext = Text::sprintf('COM_SOCIALADS_N_ITEMS_ARCHIVED', $count, $singular_name);
					}
					else
					{
						$ntext = Text::sprintf('COM_SOCIALADS_N_ITEMS_TRASHED', $count, $singular_name);
					}
				}

				$this->setMessage($ntext);
			}

			catch (Exception $e)
			{
				$this->setMessage($e->getMessage(), 'error');
			}
		}

		$this->setRedirect('index.php?option=com_socialads&view=' . $currentListView);
	}

	/**
	 * Removes an item.
	 *
	 * @return  void
	 *
	 * @since   1.7
	 */
	public function delete()
	{
		// Check for request forgeries
		Session::checkToken() or die(Text::_('JINVALID_TOKEN'));

		// Get items to remove from the request.
		$id = Factory::getApplication()->input->get('cid', array(), 'array');

		// Get called controller name
		$controllerName = get_called_class();
		$controllerName = str_split($controllerName, strlen('SocialadsController'));
		$currentController = $controllerName[1];
		$currentListView = strtolower($currentController);

		// Get called controller's - singular and plural names
		$singular_name = Text::_('COM_SOCIALADS_SINGULAR_' . strtoupper($currentController));
		$plural_name   = Text::_('COM_SOCIALADS_PLURAL_' . strtoupper($currentController));

		// Get some variables from the request

		if (!is_array($id) || count($id) < 1)
		{
			Log::add(Text::sprintf('COM_SOCIALADS_NO_ITEMS_SELECTED', $plural_name), Log::WARNING, 'jerror');
		}
		else
		{
			// Get the model.
			$model = $this->getModel();

			// Make sure the item ids are integers
			ArrayHelper::toInteger($id);

			// Remove the items.
			try
			{
				$status = $model->delete($id);
				$count = count($id);

				if ($status)
				{
					// Multiple records.
					if ($count > 1)
					{
						$ntext = Text::sprintf('COM_SOCIALADS_N_ITEMS_DELETED', $count, $plural_name);
					}
					// Single record.
					else
					{
						$ntext = Text::sprintf('COM_SOCIALADS_N_ITEMS_DELETED', $count, $singular_name);
					}

					$this->setMessage($ntext);
				}
			}
			catch (Exception $e)
			{
				$this->setMessage($e->getMessage(), 'error');
			}
		}

		// Invoke the postDelete method to allow for the child class to access the model.
		// $this->postDeleteHook($model, $id);

		$this->setRedirect('index.php?option=com_socialads&view=' . $currentListView);
	}

	/**
	 * Method to feature records.
	 *
	 * @return void
	 *
	 * @since 1.7
	 */
	public function featured()
	{
		$id = Factory::getApplication()->input->get('cid', array(), 'array');
		$data = array(
			'featured' => 1,
			'unfeatured' => 0
		);

		$task = $this->getTask();
		$value = ArrayHelper::getValue($data, $task, 0, 'int');

		// Get called controller name
		$controllerName = get_called_class();
		$controllerName = str_split($controllerName, strlen('JgiveController'));
		$currentController = $controllerName[1];
		$currentListView = strtolower($currentController);

		// Get called controller's - singular and plural names
		$singular_name = Text::_('COM_SOCIALADS_SINGULAR_' . strtoupper($currentController));
		$plural_name   = Text::_('COM_SOCIALADS_PLURAL_' . strtoupper($currentController));

		// Get some variables from the request
		if (empty($id))
		{
			Log::add(Text::sprintf('COM_SOCIALADS_N_ITEMS_SELECTED', $plural_name), Log::WARNING, 'jerror');
		}
		else
		{
			// Get the model.
			$model = $this->getModel();

			// Make sure the item ids are integers
			ArrayHelper::toInteger($id);

			// Feature the items.
			try
			{
				$model->featured($id, $value);
				$count = count($id);

				// Multiple records.
				if ($count > 1)
				{
					if ($value === 1)
					{
						$ntext = Text::sprintf('COM_SOCIALADS_N_ITEMS_FEATURED', $count, $plural_name);
					}
					elseif ($value === 0)
					{
						$ntext = Text::sprintf('COM_SOCIALADS_N_ITEMS_UNFEATURED', $count, $plural_name);
					}
				}
				// Single record.
				else
				{
					if ($value === 1)
					{
						$ntext = Text::sprintf('COM_SOCIALADS_N_ITEMS_FEATURED', $count, $singular_name);
					}
					elseif ($value === 0)
					{
						$ntext = Text::sprintf('COM_SOCIALADS_N_ITEMS_UNFEATURED', $count, $singular_name);
					}
				}

				$this->setMessage($ntext);
			}
			catch (Exception $e)
			{
				$this->setMessage($e->getMessage(), 'error');
			}
		}

		$this->setRedirect('index.php?option=com_socialads&view=' . $currentListView);
	}

	/**
	 * Method to save the submitted ordering values for records via AJAX.
	 *
	 * @return  void
	 *
	 * @since   1.7
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
}
