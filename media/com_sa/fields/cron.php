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

use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Component\ComponentHelper;

/**
 * Class for custom cron element
 *
 * @since  1.0.0
 */
class JFormFieldCron extends JFormField
{
	protected $type = 'cron';

	/**
	 * Function to genarate html of custom element
	 *
	 * @return  string
	 *
	 * @since  1.0.0
	 */
	public function getInput()
	{
		$params = ComponentHelper::getParams('com_socialads');
		$queryString = str_replace("|", "&", $this->hint);
		$cron = Route::_(Uri::root() . 'index.php?option=com_socialads' . $queryString . '&tmpl=component&pkey=' . $params->get('cron_key'));

		return '<input type="text" class="input form-control input-xxlarge" onclick="this.select();" value="' . $cron . '" aria-invalid="false">';
	}
}
