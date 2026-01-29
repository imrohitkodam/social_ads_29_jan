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

use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;

$versionObj      = new SaVersion;
$baseUrl         = Uri::root();
$version         = $versionObj->getMediaVersion();
$rotationJsUrl   = $baseUrl . 'media/com_sa/js/rotation.min.js?' . $version;
$cssUrl          = $baseUrl . 'modules/mod_socialads/assets/css/style.min.css?' . $version;
$flowplayerJsUrl = $baseUrl . 'media/com_sa/vendors/flowplayer/flowplayer-3.2.13.min.js?' . $version;

$lang          = Factory::getLanguage();
$lang->load('mod_socialads', JPATH_ROOT);
?>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.3/jquery.min.js"></script>

<link rel="stylesheet" href="<?php echo $cssUrl; ?>" />

<?php
if ($this->ad_rotation == 1)
{
?>
	<script src="<?php echo $flowplayerJsUrl; ?>"></script>
	<script src="<?php echo $rotationJsUrl; ?>"></script>
	<script>
		var site_link = "";
		var user_id   = "";

		jQuery(document).ready(function(){
			var countdown;
			var module_id = '<?php echo $this->moduleid; ?>';
			var ad_rotationdelay = <?php echo $this->ad_rotation_delay; ?>;
			jQuery(".sa_mod_<?php echo $this->moduleid; ?> .ad_prev_main").each(function(){
				if(jQuery(this).attr('ad_entry_number')){
					sa_init(this,module_id, ad_rotationdelay);
				}
			});
		});
	</script>
	<?php
}
?>

<div class="sa_mod_<?php echo $this->moduleid; ?>" havezone="<?php echo $this->zone; ?>">
	<?php

	foreach ($this->ads as $ad)
	{
		$addata = RemoteSaAdEngineHelper::getInstance()->getAdDetails($ad);
		echo RemoteSaAdEngineHelper::getInstance()->getAdHtml($addata, 0, $this->ad_rotation, $this->moduleid);
	}
	?>
</div>

<script>
window.parent.postMessage(JSON.stringify({ad_unit:"<?php echo $this->moduleid; ?>", ad_count: "<?php echo count($this->ads); ?>"}), "*");
</script>
