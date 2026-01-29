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

use Joomla\CMS\Layout\FileLayout;
use Joomla\CMS\HTML\HTMLHelper;

$versionObj = new SaVersion;
$options = array("version" => $versionObj->getMediaVersion());
HTMLHelper::stylesheet('media/techjoomla_strapper/bs3/css/bootstrap.min.css', $options);
$saLayout = new FileLayout('bs2.ad.ad_edit', $basePath = JPATH_ROOT . '/components/com_socialads/layouts');
echo $saLayout->render($this);
