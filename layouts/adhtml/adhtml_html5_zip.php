<?php
/**
 *  @package    Social Ads
 *  @copyright  Copyright (c) 2009-2013 TechJoomla. All rights reserved.
 *  @license    GNU General Public License version 2, or later
 */

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Component\ComponentHelper;

$params = ComponentHelper::getParams('com_socialads');
// $controls = {
// 	'Play' => false,
// 	'Pause' => false,
// 	'Seeking' => false,
// 	'Volume' => true,
// 	'Fullscreen toggle' => false,
// 	'Captions/Subtitles (when available)' => false,
// 	'Track (when available)' => false
// };
// die($controls);
?>

<iframe id="html_object" type="text/html" data-js-attr="sa-html5-iframe" src="<?php echo Uri::root() . $displayData->ad_image; ?>"
	frameborder="0" width="<?php echo $displayData->zone_d->img_width; ?>px" height="<?php echo $displayData->zone_d->img_height; ?>px"></iframe>


