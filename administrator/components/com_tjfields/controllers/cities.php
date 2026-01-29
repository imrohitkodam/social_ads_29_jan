<?php
/**
 * @version    SVN: <svn_id>
 * @package    Tjfields
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2015 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

// No direct access.
defined('_JEXEC') or die();
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\MVC\Controller\AdminController;
use Joomla\Utilities\ArrayHelper;

/**
 * Cities list controller class.
 *
 * @package     Tjfields
 * @subpackage  com_tjfields
 * @since       2.2
 */
class TjfieldsControllerCities extends AdminController
{
	/**
	 * Proxy for getModel.
	 *
	 * @param   string  $name    The name of the model.
	 * @param   string  $prefix  The prefix for the PHP class name.
	 * @param   array   $config  The array of config values.
	 *
	 * @return  JModel
	 *
	 * @since   1.6
	 */
	public function getModel($name = 'City', $prefix = 'TjfieldsModel', $config = array('ignore_request' => true))
	{
		$model = parent::getModel($name, $prefix, $config);

		return $model;
	}

	/**
	 * Method to publish records.
	 *
	 * @return void
	 *
	 * @since 3.0
	 */
	public function publish()
	{
		$client = Factory::getApplication()->input->get('client', '', 'STRING');
		$cid = Factory::getApplication()->input->get('cid', array(), 'array');
		$data = array(
			'publish' => 1,
			'unpublish' => 0
		);

		$task = $this->getTask();
		$value = ArrayHelper::getValue($data, $task, 0, 'int');

		// Get some variables from the request
		if (empty($cid))
		{
			Log::add(Text::_('COM_TJFIELDS_NO_CITY_SELECTED'), Log::WARNING, 'jerror');
		}
		else
		{
			// Get the model.
			$model = $this->getModel();

			// Make sure the item ids are integers
			ArrayHelper::toInteger($cid);

			// Publish the items.
			try
			{
				$model->publish($cid, $value);

				if ($value === 1)
				{
					$ntext = 'COM_TJFIELDS_N_CITIES_PUBLISHED';
				}
				elseif ($value === 0)
				{
					$ntext = 'COM_TJFIELDS_N_CITIES_UNPUBLISHED';
				}

				$this->setMessage(Text::plural($ntext, count($cid)));
			}
			catch (Exception $e)
			{
				$this->setMessage($e->getMessage(), 'error');
			}
		}

		$this->setRedirect('index.php?option=com_tjfields&view=cities&client=' . $client);
	}
}
