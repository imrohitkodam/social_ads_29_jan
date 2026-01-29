<?php
/**
 * @version    SVN:<SVN_ID>
 * @package    Com_Socialads
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2015 TechJoomla. All rights reserved
 * @license    GNU General Public License version 2, or later
 */

// No direct access.
defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\AdminController;
use Joomla\CMS\Factory;
use Joomla\Utilities\ArrayHelper;

/**
 * Zones list controller class.
 *
 * @since  1.6
 */
class SocialadsControllerZones extends AdminController
{
	/**
	 * Proxy for getModel.
	 *
	 * @param   string  $name    If true, the view output will be cached
	 * @param   string  $prefix  If true, the view output will be cached
	 * @param   array   $config  An optional associative array of configuration settings.
	 *
	 * @return  void
	 *
	 * @since	1.6
	 */
	public function getModel($name = 'zone', $prefix = 'SocialadsModel', $config = array())
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
	 * Method to get zone name
	 *
	 * @return  void
	 *
	 * @since   3.0
	 */
	public function getZonead()
	{
		$model =& $this->getModel('zones');
		$createid = $model->getZoneaddata();
		echo $createid;
		exit();
	}
}
