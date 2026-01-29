<?php
/**
 * @package     Joomla.Platform
 * @subpackage  Form
 *
 * @copyright   Copyright (C) 2005 - 2019 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('JPATH_PLATFORM') or die;

JFormHelper::loadFieldClass('radio');

use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\Component\ComponentHelper;

/**
 * Form Field class for the Joomla Platform.
 * Supports a generic list of options.
 *
 * @since  1.7.0
 */
class JFormFieldClusterAware extends JFormFieldRadio
{
	/**
	 * The form field type.
	 *
	 * @var    string
	 * @since  1.7.0
	 */
	protected $type = 'Clusteraware';

	/**
	 * Method to get a control group with label and input.
	 *
	 * @param   array  $options  Options to be passed into the rendering of the field
	 *
	 * @return  string  A string containing the html for the control group
	 *
	 * @since   3.2
	 */
	public function renderField($options = array())
	{
		$fieldHtml = '';

		// Check if com_cluster component is installed
		if (ComponentHelper::getComponent('com_cluster', true)->enabled)
		{
			$fieldHtml = parent::renderField($options);
		}

		return $fieldHtml;
	}
}
