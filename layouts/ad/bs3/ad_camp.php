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

$input = Factory::getApplication()->input;
$document = Factory::getDocument();

// Generate pricing mode options
$pricingOptions   = array();
$pricingOption = $displayData->sa_params->get('pricing_options');

if (count($pricingOption) > 1)
{
	$pricingOptions[] = HTMLHelper::_('select.option', '0', Text::_('COM_SOCIALADS_SELECT_PRICING_MODE'), ['disable' => 'disabled']);
}

foreach ($pricingOption as $k => $v)
{
	if ($v == 'perimpression')
	{
		$pricingOptions[] = HTMLHelper::_('select.option', '0', Text::_('COM_SOCIALADS_AD_CHARGE_PER_IMP'));
	}
	elseif($v == 'perclick')
	{
		$pricingOptions[] = HTMLHelper::_('select.option', '1', Text::_('COM_SOCIALADS_AD_CHARGE_PER_CLICK'));
	}
	elseif($v == 'chargetogether')
	{
		$pricingOptions[] = HTMLHelper::_('select.option', '4', Text::_('COM_SOCIALADS_CHARGE_ADS_TOGETHER'));
	}
}

// Generate campaign options
$campaignOptions = array();
$re_selectbox = array();
$re_selectbox_json = json_encode($re_selectbox);

if (count($displayData->camp_dd) > 1)
{
	$campaignOptions[] = HTMLHelper::_('select.option', '0', Text::_('COM_SOCIALADS_SELECT_CAMPAIGN'));
}

foreach ($displayData->camp_dd as $camp)
{
	// @TODO - manoj - not sure about first commented line
	// $campname = ucfirst(str_replace('plugpayment', '', $camp->campaign));
	$campname = htmlspecialchars($camp->campaign, ENT_COMPAT, 'UTF-8');
	// Static options($arr, $optKey= 'value', $optText= 'text', $selected=null, $translate=false)

	$campaignOptions[] = HTMLHelper::_('select.option', $camp->id, $campname);
}


/*
if($socialads_config['bidding']==1) { ?>
	$def = $displayData->cname;

	if($input->get('frm','','STRING'))
	{
		$def = $displayData->camp_id;
		$bid = $displayData->bid_value;
	}
}
*/
?>

<div class="<?php echo SA_WRAPPER_CLASS;?>" id="adcamp">
	<fieldset class="sa-fieldset mt-sm-3 mt-xs-5">
		<legend class="hidden-md hidden-lg"><?php echo Text::_('PRICING'); ?></legend>
		<div class="form-horizontal">
			<div class="container-fluid">
			
				<?php
				$publish1 = $publish2 = $publish1_label = $publish1_label = '' ;

				if ($displayData->unlimited_ad_create_access)
				{
					if (!empty($displayData->addata_for_adsumary_edit->ad_noexpiry))
					{
						if ($displayData->addata_for_adsumary_edit->ad_noexpiry)
						{
							$publish1 = 'checked';
							$publish1_label =' btn-success ';
						}
						else
						{
							$publish2 = 'checked';
							$publish2_label = 'btn-danger';
						}
					}
					else
					{
						$publish2 = 'checked';
						$publish2_label = 'btn-danger';
					}
					?>

					<div class="form-group">
						<div class="unlimited_adtext alert alert-info">
							<?php echo Text::_('COM_SOCIALADS_AD_UNLIMITED_AD_MSG'); ?>
						</div>
					</div>

					<div class="form-group">
						<label label-default class="col-lg-4 col-md-4 col-sm-4 col-xs-12" for="type">
							<?php echo Text::_('COM_SOCIALADS_AD_UNLIMITED_AD'); ?>
						</label>

						<div id="review" class="col-lg-4 col-md-4 col-sm-8 col-xs-12 input-group unlimited_yes_no">
							<div class="ml-24">
								<input type="radio" name="unlimited_ad" id="unlimited_ad1" value="1" <?php echo $publish1; ?> />
								<label label-default class="first btn btn-default <?php echo $publish1_label; ?>" for="unlimited_ad1">
									<?php echo Text::_('JYES'); ?>
								</label>
								<input type="radio" name="unlimited_ad" id="unlimited_ad2" value="0" <?php echo  $publish2; ?> />
								<label label-default class="last btn btn-default <?php echo $publish2_label; ?>" for="unlimited_ad2">
									<?php echo Text::_('JNO'); ?>
								</label>
							</div>
						</div>
					</div>
					<?php
				}
				?>

			<div class="form-group">
				<label label-default class="col-lg-4 col-md-4 col-sm-4 col-xs-12" for="">
					<?php echo HTMLHelper::tooltip(Text::_('COM_SOCIALADS_SELECT_CAMP_TOOLTIP'), Text::_('COM_SOCIALADS_SELECT_CAMP'), '', Text::_('COM_SOCIALADS_SELECT_CAMP')); ?>
				</label>
				<div class="col-lg-8 col-md-8 col-sm-8 col-xs-12 form-inline">
					<?php
					echo HTMLHelper::_('select.genericlist', $campaignOptions, "ad_campaign", 'class="chzn-done ml-18" onchange="sa.create.hideNewCampaign()"', "value", "text", $displayData->camp_id);

					if (empty($displayData->cname))
					{
						?>
						<button type="button" class="btn btn-default newCampBtnText btn-primary sa-ml-2" onclick="sa.create.showNewCamp()"><?php echo HTMLHelper::tooltip(Text::_('COM_SOCIALADS_CREATE_CAMP_TOOLTIP'), Text::_('COM_SOCIALADS_NEW'), '', Text::_('COM_SOCIALADS_NEW')); ?></button>
						<button type="button" class="btn btn-primary sa-ml-2 existCampBtnText d-none" onclick="sa.create.showNewCamp()"><?php echo HTMLHelper::tooltip(Text::_('COM_SOCIALADS_SELECT_EXISTING_CAMP_TOOLTIP'), Text::_('COM_SOCIALADS_SELECT_EXISTING_CAMP'), '', Text::_('COM_SOCIALADS_SELECT_EXISTING_CAMP')); ?></button>
						<p class="text-info"><span id="no_campaign"></span></p>
						<?php
					}
					?>
				</div>
			</div>

			<?php
			// If edit ad-- show the campaign name and value box if stored earlier
			// if (empty($displayData->cname) && $displayData->camp_id && $displayData->ad_value)
			if (empty($displayData->cname) && $displayData->camp_id)
			{
				$show_new_campaign_box = '';
			}
			else
			{
				$show_new_campaign_box = 'd-none';
			}
			?>

			<div id="new_campaign" class="form-group row <?php echo $show_new_campaign_box; ?>">
				<label label-default class="col-lg-4 col-md-4 col-sm-4 col-xs-12" for="">
				</label>
				<div class="col-lg-8 col-md-8 col-sm-8 col-xs-12">
					<div class="row-fluid form-inline ms-2 ml-18">
						<input type="text" class="input-small form-control col-md-8" id="camp_name" name="camp_name" placeholder="<?php echo JText::_('COM_SOCIALADS_ENTER_CAMPAIGN_NAME'); ?>" title="<?php echo JText::_('COM_SOCIALADS_ENTER_CAMPAIGN_NAME'); ?>" value="<?php // echo $displayData->cname; ?>">
						<div class="input-group sa-ml-2 col-md-4">
							<?php
							if($displayData->sa_params->get('currency_display_format') == '{CURRENCY_SYMBOL}{AMOUNT}')
							{
								?>
								<span class="input-group-addon "><?php echo SaCommonHelper::getCurrencySymbol(); ?></span>
								<input type="number" min="0" max="100000" class="form-control " id="camp_amount" name="camp_amount" placeholder="<?php echo JText::_('COM_SOCIALADS_CAMPAIGNS_DAILY_BUDGET'); ?>" title="<?php echo JText::_('COM_SOCIALADS_CAMPAIGNS_DAILY_BUDGET'); ?>" value="<?php // echo $displayData->ad_value; ?>">
								<?php
							}
							else
							{
								?>
								<input type="number" min="0" max="100000" class="form-control " id="camp_amount" name="camp_amount" placeholder="<?php echo JText::_('COM_SOCIALADS_CAMPAIGNS_DAILY_BUDGET'); ?>" title="<?php echo JText::_('COM_SOCIALADS_CAMPAIGNS_DAILY_BUDGET'); ?>" value="<?php // echo $displayData->ad_value; ?>">
								<span class="input-group-addon"><?php echo SaCommonHelper::getCurrencySymbol(); ?></span>
								<?php
							}
							?>
						</div>
					</div>
				</div>
			</div>

			<div class="form-group">
				<label label-default class="col-lg-4 col-md-4 col-sm-4 col-xs-12" for="">
					<?php echo HTMLHelper::tooltip(Text::_('COM_SOCIALADS_SELECT_METHOD_TOOLTIP'), Text::_('COM_SOCIALADS_AD_AD_CHARGE_METHOD'), '', Text::_('COM_SOCIALADS_AD_AD_CHARGE_METHOD')); ?>
				</label>

				<div class="col-lg-4 col-md-4 col-sm-8 col-xs-12">
					<?php echo HTMLHelper::_('select.genericlist', $pricingOptions, "pricing_opt", 'class="chzn-done ml-18"  onchange="sa.create.getZonePricing()"', "value", "text", $displayData->ad_payment_type); ?>
				</div>

				<div class="col-lg-4 col-md-4 col-sm-8 col-xs-12">
					<div id="click" style="display:none">
						<p class="text-info sa-mt-2"><span id="click_span"></span></p>
					</div>
					<div id="imps" style="display:none">
						<p class="text-info sa-mt-2"><span id="imps_span">
					</div>
				</div>
			</div>

			<?php
			/* if($socialads_config['bidding']==1) { ?>
			<div class="form-group" id="bid_div">
				<label label-default class="col-lg-2 col-md-2 col-sm-3 col-xs-12" for=""><?php echo Text::_('BID_VALUE'); ?></label>
				<div class="col-lg-10 col-md-10 col-sm-9 col-xs-12">
					<div class="input-group ">
						<input type="text" class="input-mini" id="bid_value" name="bid_value" value="<?php echo (Factory::getApplication()->input->get('frm')=='editad')? $bid : ''; ?>" placeholder="<?php echo Text::_('VALUE'); ?>">
						<span class="input-group-addon"><?php echo Text::_('USD'); ?></span>
					</div>
				</div>
			</div>
			<?php } */?>
		</div>
		</div>
	</fieldset>
</div>
<script>
	var re_jsondata='<?php echo $re_selectbox_json;?>';
	var camp_id='<?php echo $displayData->camp_id;?>';
</script>
