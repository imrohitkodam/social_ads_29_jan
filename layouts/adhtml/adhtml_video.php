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

<video id="vid_player_<?php echo $displayData->ad_id; ?>" style="display:block;" controls controlsList="nofullscreen nodownload noremoteplayback noPlayButton noPlauseButton" width="<?php echo $displayData->zone_d->img_width; ?>px" height="<?php echo $displayData->zone_d->img_height; ?>px" autoplay muted loop="true">
	<source src="<?php echo Uri::root() . $displayData->ad_image; ?>" type="video/mp4">
	<?php Text::_('COM_SOCIALADS_AD_BROWSER_NOT_SUPPORT_VIDEO_MSG'); ?>
</video>


