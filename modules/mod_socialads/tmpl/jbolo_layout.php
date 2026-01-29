<?php
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

$versionObj = new SaVersion;
$options = array("version" => $versionObj->getMediaVersion());
HTMLHelper::script('modules/mod_socialads/js/jbolo.min.js', $options);

// Sa jbolo integration
$integr_jbolo = $sa_params->get('jbolo_integration');

if (Folder::exists(JPATH_SITE . '/components/com_jbolo'))
{
	$jb_params = ComponentHelper::getParams('com_jbolo');
	$show_username_jbolo = $jb_params->get('chatusertitle');
	$currentuser = Factory::getUser();
	$adCreator= ModSocialadsHelper::getAdcreator($addata->ad_id);
	$adCreatordata = Factory::getUser($adCreator);

	if ($show_username_jbolo == 1)
	{
		$adcreatorname = $adCreatordata->username;
	}
	else
	{
		$adcreatorname = $adCreatordata->name;
	}

	$caltype = 0;
	$caltype = ModSocialadsHelper::getAdChargetype($addata->ad_id);

	if (!$caltype)
	{
		$caltype = 0;
	}

	$adcreatorOnline = ModSocialadsHelper::isOnline($adCreator);
	$currentuseronline = ModSocialadsHelper::isOnline($currentuser->id);

	if ($integr_jbolo and ($currentuser->id!=$adCreator) and $currentuseronline)
	{
		?>
		<div id = "jbolo_sa_intgr_Chat_<?php echo $addata->ad_id;?>" class = "jbolo_sa_intgr_Chat">
		<?php
			/*********** If Ad creator Online the Show Green Icon! **********/
			if ($adcreatorOnline == 1)
			{
				/*********** If Ad creator Online the Show Green Icon! **********/
				?>
				<span class="mf_chaton">
					<a onclick="javascript:returnval=chatFromAnywhere(<?php echo $currentuser->id . ',' . $adCreator; ?>);if(parseInt(returnval)!=0){countClickforchat('<?php echo $addata->ad_id; ?>','<?php echo $caltype; ?>',1);}" href="javascript:void(0)" title="<?php echo Text::_('MOD_SA_CHAT_WITH_AD_CREATOR'); ?>">
						<?php echo Text::_('MOD_SA_CHAT_WITH_AD_CREATOR'); ?>
					</a>
				</span>
					<?php
			}

			/*********** If Ad creator Online the Show Black Icon! **********/
			else
			{
				?>
				<span class="mf_chatoff">
					<a href='javascript:void(0)'><?php echo Text::_('MOD_SA_AD_CREATOR_OFFLINE'); ?></a>
				</span>
				<?php
			}
				?>
		</div>
			<?php
	}
}
// If jbolo not found
