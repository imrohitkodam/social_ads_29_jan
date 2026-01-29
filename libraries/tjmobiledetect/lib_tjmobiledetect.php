<?php
/**
 * @package     Techjoomla.Libraries
 * @subpackage  TJMobileDetect
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2022 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

defined('JPATH_PLATFORM') or die;

if (!class_exists('Mobile_Detect'))
{
	require_once JPATH_SITE . '/libraries/tjmobiledetect/lib_tjmobiledetect/vendor/mobiledetect/Mobile_Detect.php';
}

/**
 * TJMobileDetect
 *
 * @package     Techjoomla.Libraries
 * @subpackage  TJMobileDetect
 * @since       1.0
 */
class TjMobieDetect extends Mobile_Detect
{
}
