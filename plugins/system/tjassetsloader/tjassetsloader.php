<?php
/**
 * @package    Tjassetsloader
 *
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

// No direct access.
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\CMSPlugin;

if (!defined('DS'))
{
	define('DS', '/');
}

// Load language file for plugin
$lang = Factory::getLanguage();
$lang->load('plg_system_tjassetsloader', JPATH_ADMINISTRATOR);

/**
 * Class for TJ assets loader plugin
 *
 * @package     JBolo
 * @subpackage  tjassetsloader
 * @since       3.1.4
 */
class PlgSystemTjassetsloader extends CMSPlugin
{
}
