<?php
/**
 * @version    SVN:<SVN_ID>
 * @package    SocialAds
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2015 TechJoomla. All rights reserved
 * @license    GNU General Public License version 2, or later
 */

// No direct access
defined( '_JEXEC' ) or die( ';)' );
use Joomla\CMS\Language\Text;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;

HTMLHelper::_('behavior.formvalidator');
$document = Factory::getDocument();

// Get ad preview
// Get payment HTML
// JLoader::import('showad',JPATH_SITE.DS.'components'.DS.'com_socialads'.DS.'models');
// JLoader::import('showad', JPATH_ADMINISTRATOR.DS.'components'.DS.'com_socialads'.DS.'models');
// $showadmodel = new socialadsModelShowad();
// $preview = $showadmodel->getAds($displayData->ad_id);

require_once JPATH_ROOT . '/components/com_socialads/helpers/engine.php';
// $displayData->preview = SaAdEngineHelper::getAdHtml($displayData->ad_id, 1); // $preview

if ($displayData->AdPreviewData->ad_payment_type == 0)
{
	$mode =  Text::_('COM_SOCIALADS_PAY_IMP');
}
elseif($displayData->AdPreviewData->ad_payment_type == 1)
{
	$mode = Text::_('COM_SOCIALADS_PAY_CLICK');
}
elseif($displayData->AdPreviewData->ad_payment_type == 3)
{
	$mode = Text::_('SELL_THROUGH');
}
?>

	<form action="" method="post" name="adminForm" id="adminForm" enctype="multipart/form-data" class="" >
		<fieldset class="sa-fieldset">
			<legend class="hidden-desktop"><?php echo Text::_('COM_SOCIALADS_REVIEW_AD_TAB'); ?></legend>
			<!-- for ad detail and preview -->
			<div class=" row-fluid show-grid">
				<!--ad detai start -->
				<div class="span6 sa-overflow-x">
					<h4><?php echo Text::_('COM_SOCIALADS_AD_SHOW_ADS_DETAILS');?></h4>
					<table class="table table-hover sa-table-borderless">
						<tr>
							
							<td> <strong><?php echo Text::_('COM_SOCIALADS_AD_SHOW_TYPE'); ?> </strong> </td>
							<td>
								<div ><?php echo $displayData->displayAdsInfo['ad_type']; ?></div>
							</td>
						</tr>
						<tr>
							
							<td> <strong><?php echo Text::_('COM_SOCIALADS_AD_SHOW_TITLE'); ?> </strong> </td>
							<td>
								<div><?php echo $displayData->displayAdsInfo['ad_title']; ?></div>
							</td>
						</tr>
						<tr>
							<td> <strong><?php echo Text::_('COM_SOCIALADS_CAMPAIGN_NAME'); ?> </strong> </td>
							<td>
								<div><?php echo $displayData->AdPreviewData->campaign; ?></div>
							</td>
						</tr>
						<tr>
							<td> <strong><?php echo Text::_('COM_SOCIALADS_AD_SHOW_DESTINATION_URL'); ?> </strong> </td>
							<td>
								<div><?php echo $displayData->displayAdsInfo['ad_url']; ?></div>
							</td>
						</tr>
						<?php if (!$displayData->AdPreviewData->pay_initial_fee && $displayData->sa_params->get('payment_mode') == 'wallet_mode' && $displayData->sa_params->get('need_to_pay_initial_fee') && $displayData->sa_params->get('initial_fee_for_ad_placement'))
						{?>
							<tr>
								<td> <strong><?php echo Text::_('COM_SOCIALADS_SETUP_PLACEMENT_FEE'); ?> </strong> </td>
								<td>
									<?php echo SaCommonHelper::getCurrencySymbol() . ' ' . $displayData->sa_params->get('initial_fee_for_ad_placement') .' (' . Text::_('COM_SOCIALADS_ONE_TIME') . ')'; ?>
								</td>
							</tr>
						<?php
						}
						?>
						<tr>
							<td> <strong><?php echo Text::_('COM_SOCIALADS_PRICING_MODE'); ?> </strong> </td>
							<td>
								<div id="modecamp"><?php echo $mode; ?></div>
							</td>
						</tr>
						<tr>
							<td> <strong><?php echo Text::_('COM_SOCIALADS_AD_SHOW_COST'); ?> </strong> </td>
							<td>
								<div><?php echo $displayData->displayAdsInfo['cost'] .' ' . $mode; ?></div>
							</td>
						</tr>
						<?php if(count($displayData->displayAdsInfo['geo']))
						{
						?> 
							<tr>
								<td> <strong><?php echo Text::_('COM_SOCIALADS_AD_SHOW_GEO_TARGETING'); ?> </strong> </td>
								<td>
									<div><?php echo $displayData->displayAdsInfo['geo']['country']  ?></div>
									<div><?php echo $displayData->displayAdsInfo['geo']['region']  ?></div>
									<div><?php echo $displayData->displayAdsInfo['geo']['city']  ?></div>
								</td>
							</tr>
						<?php
						}
						
						if ($displayData->displayAdsInfo['context_targett'])
						{
						?> 
							<tr>
								<td> <strong><?php echo Text::_('COM_SOCIALADS_AD_SHOW_CONTEXT_TARGETING'); ?> </strong> </td>
								<td>
									<div><?php echo $displayData->displayAdsInfo['keywords']  ?></div>
								</td>
							</tr>
						<?php
						}
						
						if ($displayData->displayAdsInfo['social_targett'] && count($displayData->displayAdsInfo['mapdata']))
						{
						?> 
							<tr>
								<td> <strong><?php echo Text::_('COM_SOCIALADS_AD_SHOW_SOCIAL_TARGETING'); ?> </strong> </td>
								<td>
									<?php 

									foreach ($displayData->displayAdsInfo['mapdata'] as $fields)
									{
										foreach ($fields as $key => $value)
										{
											?>
											<div><?php echo $key . ' => ' . $value; ?></div>
										<?php
										}
									}
									?>
								</td>
							</tr>
						<?php
						}
						?>
						<tr>
							<?php
							/*if($socialads_config['bidding']==1 && $displayData->bid_value)
							{
								?>
								<td><?php echo Text::_('BID_VALUE'); ?></td>
								<td>
									<div id="bid"><?php echo $displayData->bid_value; echo " "; echo Text::_('USD'); ?></div>
								</td>
								<?php
							}
							*/
							?>
						</tr>
					</table>
				</div>
				<div class="span6">
					<h4><?php echo Text::_('COM_SOCIALADS_ADS_AD_PREVIEW');?></h4>
					<?php echo SaAdEngineHelper::getAdHtml($displayData->ad_id, 1); ?>
				</div>
			</div>

			<input type="hidden" name="option" value="com_socialads"/>
			<!--
			<input type="hidden" name="controller" value="ad"/>
			-->
			<input type="hidden" name="task" value=""/>

		</fieldset>
		<div class="clearfix">&nbsp;</div>
		<div class="form-actions">
			<button id="btnWizardPrev" type="button" onclick="techjoomla.jQuery('#MyWizard').wizard('previous');" class="btn btn-primary pull-left" >
				<i class="icon-circle-arrow-left icon-white" ></i><?php echo Text::_('COM_SOCIALADS_PREV'); ?>
			</button>
			<button id="buy" type="button" class="btn btn-success pull-right sa-mr-1" onclick="submitbutton('create.activateAd')">
				<?php echo Text::_('COM_SOCIALADS_SAVE_ACTIVATE');?>
			</button>
			<button id="draft" type="button" class="btn btn-info pull-right sa-mr-1" onclick="submitbutton('create.draftAd');">
				<?php echo Text::_('COM_SOCIALADS_SHOWAD_DRAFT');?>
			</button>
			<button id="sa_cancel" type="button" class="btn btn-default btn-danger pull-right sa-mr-1" onclick="sa.create.cancelCreate()">
				<?php echo Text::_('COM_SOCIALADS_CANCEL'); ?>
			</button>
		</div>
	</form>
</div>
