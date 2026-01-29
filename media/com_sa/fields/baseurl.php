<?php
/**
 * @version    SVN: <svn_id>
 * @package    SocialAd
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2019 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

// No direct access
defined('_JEXEC') or die();
use Joomla\CMS\Form\FormField;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;

/**
 * Class for custom cron element
 *
 * @since  __DEPLOY_VERSION_
 */
class JFormFieldBaseUrl extends JFormField
{
	protected $type = 'baseurl';

	/**
	 * Function to genarate html of custom element
	 *
	 * @return  string
	 *
	 * @since  __DEPLOY_VERSION_
	 */
	public function getInput()
	{
		return '<input type="hidden" class="form-control" name="' . $this->name . '" value="' . Uri::root() . '">';
	}

	/**
	 * Function to get the label
	 *
	 * @return  string
	 *
	 * @since  __DEPLOY_VERSION_
	 */
	public function getLabel()
	{
		return "";
	}
}
