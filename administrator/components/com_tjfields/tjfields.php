<?php
/**
 * @version    SVN: <svn_id>
 * @package    Tjfields
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2015 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

// No direct access
defined('_JEXEC') or die();
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\MVC\Controller\BaseController;

if (!defined('DS'))
{
	define('DS', DIRECTORY_SEPARATOR);
}

// Access check.
if (!Factory::getUser()->authorise('core.manage', 'com_tjfields'))
{
	throw new Exception(Text::_('JERROR_ALERTNOAUTHOR'));
}

// Define constants
if (JVERSION < '3.0')
{
	// Define wrapper class
	define('TJFIELDS_WRAPPER_CLASS', "tjfields-wrapper techjoomla-bootstrap");

	// Other
	HTMLHelper::_('bootstrap.tooltip');
}
else
{
	// Define wrapper class
	define('TJFIELDS_WRAPPER_CLASS', "tjfields-wrapper");

	// Tabstate
	if (JVERSION < '4.0.0')
	{
		HTMLHelper::_('behavior.tabstate');
		HTMLHelper::_('formbehavior.chosen', 'select');
	}

	// Other
	HTMLHelper::_('bootstrap.tooltip');

	// Bootstrap tooltip and chosen js
	HTMLHelper::_('bootstrap.tooltip');
	HTMLHelper::_('behavior.multiselect');
}

$document = Factory::getDocument();
$document->addStyleSheet(Uri::base() . 'components/com_tjfields/assets/css/tjfields.css');

// Include helper file
$helperPath = dirname(__FILE__) . '/helpers/tjfields.php';

if (!class_exists('TjfieldsHelper'))
{
	JLoader::register('TjfieldsHelper', $helperPath);
	JLoader::load('TjfieldsHelper');
}

// Load techjoomla strapper
if (file_exists(JPATH_ROOT . '/media/techjoomla_strapper/tjstrapper.php'))
{
	require_once JPATH_ROOT . '/media/techjoomla_strapper/tjstrapper.php';
	TjStrapper::loadTjAssets('com_tjfields');
}

$controller	= BaseController::getInstance('Tjfields');
$controller->execute(Factory::getApplication()->input->get('task'));
$controller->redirect();
