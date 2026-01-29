<?php
/**
 * @version    SVN:<SVN_ID>
 * @package    SocialAds
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2015 TechJoomla. All rights reserved
 * @license    GNU General Public License version 2, or later
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;

// Include dependancies


// Load defines.php
require_once JPATH_SITE . '/components/com_socialads/defines.php';

// Tabstate
if (JVERSION < '4.0.0')
{
	HTMLHelper::_('behavior.tabstate');
}

// Bootstrap tooltip and chosen js
HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('behavior.multiselect');

// Load strapper
$tjStrapperPath = JPATH_SITE . '/media/techjoomla_strapper/tjstrapper.php';

if (File::exists($tjStrapperPath))
{
	require_once $tjStrapperPath;
	TjStrapper::loadTjAssets('com_socialads');
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

$versionObj = new SaVersion;
$options = array("version" => $versionObj->getMediaVersion());

$sa_params = ComponentHelper::getParams('com_socialads');
$loadBootstrap = $sa_params->get('boostrap_manually');

if ($loadBootstrap)
{
	// Load bootstrap CSS
	HTMLHelper::stylesheet('media/techjoomla_strapper/bs3/css/bootstrap.min.css', $options);
}

$helperPath = JPATH_SITE . '/components/com_socialads/helpers/payment.php';

if (!class_exists('SocialadsPaymentHelper'))
{
	JLoader::register('SocialadsPaymentHelper', $helperPath);
	JLoader::load('SocialadsPaymentHelper');
}

$helperPath = JPATH_SITE . '/components/com_socialads/helpers/wallet.php';

if (!class_exists('SaWalletHelper'))
{
	JLoader::register('SaWalletHelper', $helperPath);
	JLoader::load('SaWalletHelper');
}

// Import helper for declaring language constant
JLoader::import('common', JPATH_SITE . '/components/com_socialads/helpers');

// Load common lang. file
$lang = Factory::getLanguage();
$lang->load('com_socialads_common', JPATH_SITE, $lang->getTag(), true);

// Call helper function
SaCommonHelper::getLanguageConstant();

HTMLHelper::stylesheet('media/com_sa/css/sa.min.css', $options);
HTMLHelper::script('media/com_sa/js/sa.min.js', $options);
HTMLHelper::stylesheet('media/com_sa/css/sa-tables.min.css', $options);

// Execute the task.
$controller = BaseController::getInstance('Socialads');
$controller->execute(Factory::getApplication()->input->get('task'));
$controller->redirect();
