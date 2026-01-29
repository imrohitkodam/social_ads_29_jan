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

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
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
	$mode = Text::_('COM_SOCIALADS_PAY_IMP');
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
<div class="<?php echo SA_WRAPPER_CLASS;?>" id="showcamp">
	<div class="form-horizontal">
		<form action="" method="post" name="adminForm" id="adminForm" enctype="multipart/form-data" class="" >
			<fieldset class="sa-fieldset">
				<legend class="hidden-md hidden-lg text-center fw-bolder"><?php echo Text::_('COM_SOCIALADS_REVIEW_AD_TAB'); ?></legend>
				<!-- for ad detail and preview -->
				<div class="container-fluid mt-3">
					<div class=" row">
						<!--ad detai start -->
						<div class="col-lg-6 col-md-6 col-sm-6 col-xs-6 sa-overflow-x">
							<h6><?php echo Text::_('COM_SOCIALADS_AD_SHOW_ADS_DETAILS');?></h6>
							<div class="row ">

								<div class="col-sm-12 col-md-4"> <strong><?php echo Text::_('COM_SOCIALADS_AD_SHOW_TYPE'); ?> </strong> </div>
								<div class="col-sm-12 col-md-8">
									<div ><?php echo $displayData->displayAdsInfo['ad_type']; ?></div>
								</div>
							</div>
							<div class="row ">
								<div class="col-sm-12 col-md-4"> <strong><?php echo Text::_('COM_SOCIALADS_AD_SHOW_TITLE'); ?> </strong> </div>
								<div class="col-sm-12 col-md-8">
									<div><?php echo $displayData->displayAdsInfo['ad_title']; ?></div>
								</div>
							</div>
							<div class="row ">
								<div class="col-sm-12 col-md-4"> <strong><?php echo Text::_('COM_SOCIALADS_CAMPAIGN_NAME'); ?> </strong> </div>
								<div class="col-sm-12 col-md-8">
									<div><?php echo $displayData->AdPreviewData->campaign; ?></div>
								</div>
							</div>
							<div class="row ">
								<div class="col-sm-12 col-md-4"> <strong><?php echo Text::_('COM_SOCIALADS_AD_SHOW_DESTINATION_URL'); ?> </strong> </div>
								<div class="col-sm-12 col-md-8">
									<div><?php echo $displayData->displayAdsInfo['ad_url']; ?></div>
								</div>

							</div>
							<div class="row ">
								<?php if (!$displayData->AdPreviewData->pay_initial_fee && $displayData->sa_params->get('payment_mode') == 'wallet_mode' && $displayData->sa_params->get('need_to_pay_initial_fee') && $displayData->sa_params->get('initial_fee_for_ad_placement'))
								{?>
									<div class="col-sm-12 col-md-4"> <strong><?php echo Text::_('COM_SOCIALADS_SETUP_PLACEMENT_FEE'); ?> </strong> </div>
									<div class="col-sm-12 col-md-8">
									<div><?php echo SaCommonHelper::getCurrencySymbol() . ' ' . $displayData->sa_params->get('initial_fee_for_ad_placement') .' (' . Text::_('COM_SOCIALADS_ONE_TIME') . ')'; ?></div>
									</div>
								<?php
								}
								?>
							</div>
							<div class="row ">	
								<div class="col-sm-12 col-md-4"> <strong><?php echo Text::_('COM_SOCIALADS_PRICING_MODE'); ?> </strong> </div>
								<div class="col-sm-12 col-md-8">
									<div id="modecamp"><?php echo $mode; ?></div>
								</div>
								
								<div class="col-sm-12 col-md-4"> <strong><?php echo Text::_('COM_SOCIALADS_AD_SHOW_COST'); ?> </strong> </div>
								<div class="col-sm-12 col-md-8">
									<div><?php echo $displayData->displayAdsInfo['cost'] .' ' . $mode; ?></div>
								</div>
								<?php if(count($displayData->displayAdsInfo['geo']))
								{
								?> 
									<div class="col-sm-12 col-md-4"> <strong><?php echo Text::_('COM_SOCIALADS_AD_SHOW_GEO_TARGETING'); ?> </strong> </div>
									<div class="col-sm-12 col-md-8">
										<div><?php echo $displayData->displayAdsInfo['geo']['country']  ?></div>
										<div><?php echo $displayData->displayAdsInfo['geo']['region']  ?></div>
										<div><?php echo $displayData->displayAdsInfo['geo']['city']  ?></div>
									</div>
								<?php
								}
								
								if ($displayData->displayAdsInfo['context_targett'])
								{
								?> 
									<div class="col-sm-12 col-md-4"> <strong><?php echo Text::_('COM_SOCIALADS_AD_SHOW_CONTEXT_TARGETING'); ?> </strong> </div>
									<div class="col-sm-12 col-md-8">
										<div><?php echo $displayData->displayAdsInfo['keywords']  ?></div>
									</div>
								<?php
								}
								
								if ($displayData->displayAdsInfo['social_targett'] && count($displayData->displayAdsInfo['mapdata']))
								{
								    ?> 
									<div class="col-sm-12 col-md-4"> <strong><?php echo Text::_('COM_SOCIALADS_AD_SHOW_SOCIAL_TARGETING'); ?> </strong> </div>
									<div class="col-sm-12 col-md-8">
										<?php

										foreach ($displayData->displayAdsInfo['mapdata'] as $fields)
										{
										    foreach ($fields as $key => $value)
										    {
										        ?>
												<div><?php echo $key . ' => ' . $value; ?></div>
											<?php
										    }
										} ?>
									</div>
								<?php
								}
									/*if($socialads_config['bidding']==1 && $displayData->bid_value)
									{
										?>
										<div class="col-sm-12 col-md-8"><?php echo Text::_('BID_VALUE'); ?></div>
										<div class="col-sm-12 col-md-8">
											<div id="bid"><?php echo $displayData->bid_value; echo " "; echo Text::_('USD'); ?></div>
										</div>
										<?php
									}
									*/
								?>
							</div>
						</div>
						<div class="col-lg-6 col-md-6 col-sm-6 col-xs-6">
							<h6><?php echo Text::_('COM_SOCIALADS_AD_PREVIEW');?></h6>
							<?php echo SaAdEngineHelper::getAdHtml($displayData->ad_id, 1); ?>
						</div>
					</div>

					<input type="hidden" name="option" value="com_socialads"/>
					<!--
					<input type="hidden" name="controller" value="ad"/>
					-->
					<input type="hidden" name="task" value=""/>
				</div>
			</fieldset>
			<div class="clearfix">&nbsp;</div>
			<div class="form-actions">
				<button id="btnWizardPrev" type="button" class="btn btn-default btn-primary float-start" onclick="techjoomla.jQuery('#MyWizard').wizard('previous');">
					<span class="glyphicon glyphicon-circle-arrow-left" aria-hidden="true"></span> <?php echo Text::_('COM_SOCIALADS_PREV'); ?>
				</button>
				<button id="buy" type="button" class="btn btn-default btn-success float-end" style="margin-right:1%;" onclick="Joomla.submitform('create.activateAd')">
					<?php echo Text::_('COM_SOCIALADS_SAVE_ACTIVATE');?>
				</button>
				<button id="draft" type="button" class="btn btn-default btn-info float-end" style="margin-right:1%;" onclick="Joomla.submitform('create.draftAd');">
					<?php echo Text::_('COM_SOCIALADS_SHOWAD_DRAFT');?>
				</button>
				<button id="sa_cancel" type="button" class="btn btn-default btn-danger float-end" style="margin-right:1%;" onclick="sa.create.cancelCreate()">
					<?php echo Text::_('COM_SOCIALADS_CANCEL'); ?>
				</button>
			</div>
		</form>
	</div>
</div>
