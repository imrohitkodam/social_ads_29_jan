<?php
/**
 *  @package    Social Ads
 *  @copyright  Copyright (c) 2009-2013 TechJoomla. All rights reserved.
 *  @license    GNU General Public License version 2, or later
 */

defined('_JEXEC') or die( 'Restricted access' );
use Joomla\CMS\Uri\Uri;
?>

<!-- @TODO use resized image dimensions here -->
<img class="<?php echo $displayData->ad_layout .'_ad_prev_img'; ?>" alt="" src="<?php echo Uri::root() . $displayData->ad_image; ?>" style="border:0px;" />
