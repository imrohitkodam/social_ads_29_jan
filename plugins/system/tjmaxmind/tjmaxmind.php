<?php
/**
 * @package     TJMaxmind
 * @subpackage  TJMaxmind
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (c) 2009-2019 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later.
 */
// No direct access
defined('_JEXEC') or die();

use Joomla\CMS\Plugin\CMSPlugin;

JLoader::import('joomla.application.plugin');

/**
 * TJMaxmind System Plugin.
 *
 * @since  1.0.0
 */
class PlgSystemTJMaxmind extends CMSPlugin
{
	/**
	 * Public constructor
	 *
	 * @param   object  $subject  The onject to observe
	 * @param   array   $config   Configuration parameters to the plugin
	 */
	public function __construct($subject, $config = array())
	{
		$this->loadLanguage('plg_system_tjmaxmind');

		return parent::__construct($subject, $config);
	}
}
