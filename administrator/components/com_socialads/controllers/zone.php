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
use Joomla\CMS\Language\Text;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Uri\Uri;

/**
 * Zone controller class.
 *
 * @since  1.0
 */
class SocialadsControllerZone extends FormController
{
	/**
	 *Function to construct a zones view
	 *
	 * @since  3.0
	 */
	public function __construct()
	{
		$this->view_list = 'zones';
		parent::__construct();
	}

	/**
	 * Overrides parent save method.
	 *
	 * @param   string  $key     The name of the primary key of the URL variable.
	 * @param   string  $urlVar  The name of the URL variable if different from the primary key (sometimes required to avoid router collisions).
	 *
	 * @return  boolean  True if successful, false otherwise.
	 *
	 * @since   3.2
	 */
	public function save($key = null, $urlVar = null)
	{
		// Check for request forgeries.
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

		$task = $this->getTask();

		// Initialise variables.
		$app   = Factory::getApplication();
		$model = $this->getModel('Zone', 'SocialadsModel');

		// Get the user data.
		$data = $app->input->get('jform', array(), 'array');

		// Attempt to save the data.
		$return = $model->save($data);
		$id     = $return;

		// Check for errors.
		if ($return === false)
		{
			// Save the data in the session.
			$app->setUserState('com_socialads.edit.zone.data', $data);

			// Tweak *important.
			$app->setUserState('com_socialads.edit.zone.id', $data['id']);

			// Redirect back to the edit screen.
			$id = (int) $app->getUserState('com_socialads.edit.zone.id');
			$this->setMessage(Text::sprintf('COM_SOCIALADS_SAVE_MSG_ERROR', $model->getError()), 'warning');
			$this->setRedirect('index.php?option=com_socialads&&view=zone&layout=edit&id=' . $id);

			return false;
		}

		if (!$id)
		{
			$id = (int) $app->getUserState('com_socialads.edit.zone.id');
		}

		if ($task === 'apply')
		{
			$redirect = 'index.php?option=com_socialads&task=zone.edit&id=' . $id;
		}
		else
		{
			// Clean the session data and redirect.
			$model->checkin($id);

			// Clear the profile id from the session.
			$app->setUserState('com_socialads.edit.zone.id', null);

			// Flush the data from the session.
			$app->setUserState('com_socialads.edit.zone.data', null);

			// Redirect to the list screen.
			$redirect = 'index.php?option=com_socialads&view=zones';
		}

		$msg = Text::_('COM_SOCIALADS_SAVE_SUCCESS');
		$this->setRedirect($redirect, $msg);
	}

	/**
	 * Function to call the plugin from view
	 *
	 * @return  void
	 *
	 * @since  3.0
	 */
	public function getSelectedLayouts()
	{
		$input            = Factory::getApplication()->input;
		$addtype          = $input->get('addtype');
		$zonlay           = $input->get('zonelayout', '', 'array');
		$zoneTypes        = $zonlay[0];
		$selected_layout1 = array();

		if ($zoneTypes)
		{
			$input->set('layout', $zoneTypes);
			$selected_layout_arr = explode('|', $zoneTypes);
			$i                   = 0;

			foreach ($selected_layout_arr as $selected_layout)
			{
				$selected_layout1[$i] = $selected_layout;
				$i++;
			}
		}

		if ($addtype == 'text_media')
		{
			$addtype = 'Text and Media';
		} 
		else if($addtype == 'text')
		{
			$addtype = 'Text';
		} 
		else if($addtype == 'media')
		{
			$addtype = 'Media';
		} 
		else if($addtype == 'html5_zip')
		{
			$addtype = 'HTML5 Zip';
		} 
		else
		{
			$layout_type = "";
			$add_type[] = HTMLHelper::_('select.option', '0', 'select');
			HTMLHelper::_('select.genericlist', $add_type, 'layout_select', 'class = "inputbox" size=1', 'value', '');
			exit;
		}

		$layout_type = $addtype;
		$add_type    = '';
		$newvar      = PluginHelper::getPlugin('socialadslayout');
		$sel_layout1 = array_values($selected_layout1);

		foreach ($newvar as $k => $v)
		{
			$params = explode("\n", $v->params);

			foreach ($params as $pa => $p)
			{
				$lay = json_decode($p);

				if (isset($lay->layout_type))
				{
					$layout_type = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $layout_type));
					$lay->layout_type = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $lay->layout_type));

					if ($layout_type == $lay->layout_type)
					{
						$chk = '';
						$nam      = $v->name;

						if (in_array($nam, $sel_layout1))
						{
							$chk = 'checked = "checked"';
						}
						elseif($layout_type == 'Media')
							$chk = 'checked="yes"';
							$add_type .= '<span style = "vertical-align:text-top;">
										<input type="checkbox" ' . $chk . ' name="layout_select[]" class="inputbox" value="' . $nam . '" />
										<img src="' . Uri::root() . 'plugins/socialadslayout/' . $nam . '/' . $nam . '/layout.png" >
										</span>&nbsp;&nbsp;&nbsp;';
					}
				}
			}
		}

		if ($add_type == '')
		{
			echo Text::_('COM_SOCIALADS_ZONES_NO_LAYOUT');
		}
		else
		{
			echo $add_type;
		}

		exit;
	}
}
