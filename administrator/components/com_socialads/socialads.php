<?php
/**
 * @version    SVN:<SVN_ID>
 * @package    SocialAds
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2015 TechJoomla. All rights reserved
 * @license    GNU General Public License version 2, or later
 */

// No direct access
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\MVC\Controller\BaseController;

// Include dependancies

// Access check.
if (!Factory::getUser()->authorise('core.manage', 'com_socialads'))
{
	throw new Exception(Text::_('JERROR_ALERTNOAUTHOR'));
}

// Load defines.php
require_once JPATH_SITE . '/components/com_socialads/defines.php';

// Lib load
// Define wrapper class
define('SA_WRAPPER_CLASS', "sa-wrapper");

// Tabstate
if (JVERSION < '4.0.0')
{
	HTMLHelper::_('behavior.tabstate');
}

// Bootstrap tooltip and chosen js
HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('behavior.multiselect');

// Load other assets via strapper
$tjStrapperPath = JPATH_SITE . '/media/techjoomla_strapper/tjstrapper.php';

if (File::exists($tjStrapperPath))
{
	require_once $tjStrapperPath;
	TjStrapper::loadTjAssets('com_socialads');
}

// Load backend helper
if (!class_exists('SocialadsHelper'))
{
	JLoader::register('SocialadsHelper', JPATH_COMPONENT . '/helpers/socialads.php');
	JLoader::load('SocialadsHelper');
}

$helperPath = JPATH_SITE . '/components/com_socialads/helpers';

if (!class_exists('SaCommonHelper'))
{
	require_once $helperPath . '/common.php';

	// JLoader::register('SaCommonHelper', $helperPath . '/common.php' );
	// JLoader::load('SaCommonHelper');
}

if (!class_exists('SaWalletHelper'))
{
	JLoader::register('SaWalletHelper', $helperPath . '/wallet.php');
	JLoader::load('SaWalletHelper');
}

if (!class_exists('SocialadsPaymentHelper'))
{
	JLoader::register('SocialadsPaymentHelper', $helperPath . '/payment.php');
	JLoader::load('SocialadsPaymentHelper');
}

// Load all required classes
$saInitClassPath = JPATH_SITE . '/components/com_socialads/init.php';

if (!class_exists('SaInit'))
{
	JLoader::register('SaInit', $saInitClassPath);
	JLoader::load('SaInit');
}

// Define autoload function
spl_autoload_register('SaInit::autoLoadHelpers');

// Load assets
$versionObj = new SaVersion;
$options = array("version" => $versionObj->getMediaVersion());
HTMLHelper::stylesheet('media/com_sa/css/sa_admin.min.css', $options);
HTMLHelper::stylesheet('media/com_sa/css/sa-tables.min.css', $options);
HTMLHelper::script('media/com_sa/js/sa.min.js', $options);

// Import helper for declaring language constant
// JLoader::import('SocialadsHelper', JUri::root().'administrator/components/com_socialads/helpers/socialads.php');

// Load common lang. file
$lang = Factory::getLanguage();
$lang->load('com_socialads_common', JPATH_SITE, $lang->getTag(), true);

// Call helper function
SocialadsHelper::getLanguageConstant();

// Execute task
$controller = BaseController::getInstance('Socialads');
$controller->execute(Factory::getApplication()->input->get('task'));
$controller->redirect();
