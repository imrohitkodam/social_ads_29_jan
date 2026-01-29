<?php
/**
 * @version    SVN:<SVN_ID>
 * @package    SocialAds
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2018 TechJoomla. All rights reserved
 * @license    GNU General Public License version 2, or later
 */

// No direct access
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\HTML\HTMLHelper;

use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Plugin\PluginHelper;

HTMLHelper::_('behavior.formvalidator');

if (JVERSION < '4.0')
{
    JHtmlBehavior::framework();
}

require_once JPATH_ROOT . '/components/com_socialads/helpers/engine.php';

// Fetch ad detail
if (empty($displayData->order_id))
{
?>
	<div>
		<div class="alert input-group-addon">
			<span ><?php echo Text::_('COM_SOCIALADS_AD_UNABLE_TO_TRACK_ORDER_ID'); ?> </span>
		</div>
	</div>
	<?php
	return false;
}

$socialadsPaymentHelper = new SocialadsPaymentHelper;
$adDetail = $socialadsPaymentHelper->getOrderAndAdDetail($displayData->order_id, 1);

// VM:: hv to add and code for jomsical points ( we are looking later for jomscial points)
$gatwayName  = 'bycheck';
$plugin      = PluginHelper::getPlugin('payment', $gatwayName);
$paymentMode = ($displayData->sa_params->get('payment_mode'));

if (0 && $paymentMode == 'pay_per_ad_mode')
{
	$pluginParams = json_decode($plugin->params);
	$this->assignRef('ad_gateway', $pluginParams->plugin_name);
	$arb_enforce  = '';
	$this->assignRef('arb_enforce', $pluginParams->arb_enforce);
	$arb_enforce  = '';
	$this->assignRef('arb_support', $pluginParams->arb_support);
	$points1      = 0;

	if (isset($pluginParams->points))
	{
		if ($pluginParams->points == 'point')
		{
			$points1 = 1;

			// $points1=$this->get('JomSocialPoints');
			$this->assignRef('ad_points', $points1);
			$this->assignRef('ad_jconver', $pluginParams->conversion);
		}
	}
}
// If ends

// Get ad preview
$preview = SaAdEngineHelper::getAdHtml($adDetail['ad_id'], 1);

$chargeoption = $adDetail['ad_payment_type'];

// No of clicks or impression
$ad_totaldisplay = $adDetail['ad_credits_qty'];

// Getting selected payment gateway list form component config
$selected_gateways = (array) $displayData->sa_params->get('gateways', 'paypal', 'STRING');

// Getting GETWAYS

PluginHelper::importPlugin('payment');

if (!is_array($selected_gateways))
{
	$gateway_param[] = $selected_gateways;
}
else
{
	$gateway_param = $selected_gateways;
}

if (!empty($gateway_param))
{
	$gateways = Factory::getApplication()->triggerEvent('onTP_GetInfo', array($gateway_param));
}

$ad_gateways = $gateways;
?>

<!--techjoomla-bootstrap -->
<div class="techjoomla-bootstrap ad_reviewAdmainContainer" >
	<fieldset class="sa-fieldset">
		<legend class="hidden-md hidden-lg fw-bold bg-info bg-opacity-100 text-light p-2 mb-3 rounded-2">
			<?php echo Text::_('COM_SOCIALADS_CKOUT_ADS_SUMMERY'); ?>
		</legend>
		<!-- For ad detail and preview -->
		<div class="container-fluid">
			<div class="row px-4">
				<!--ad details start -->
				<div class="col-sm-6 col-xs-12">
					<div class=" row">
						<h4>
							<?php echo Text::_('COM_SOCIALADS_AD_SHOW_ADS_DETAILS');?>
						</h4>
						<div class = "clearfix">&nbsp;</div>
					</div>
					<div class=" row">
						<div class="col-sm-12 col-md-4"> <strong><?php echo Text::_('COM_SOCIALADS_AD_SHOW_TYPE'); ?> </strong> </div>
						<div class="col-sm-12 col-md-8">
							<div ><?php echo $displayData->displayAdsInfo['ad_type']; ?></div>
						</div>
							
						<div class="col-sm-12 col-md-4"> <strong><?php echo Text::_('COM_SOCIALADS_AD_SHOW_TITLE'); ?> </strong> </div>
						<div class="col-sm-12 col-md-8">
							<div><?php echo $displayData->displayAdsInfo['ad_title']; ?></div>
						</div>

						<div class="col-sm-12 col-md-4"> <strong><?php echo Text::_('COM_SOCIALADS_AD_SHOW_DESTINATION_URL'); ?> </strong> </div>
						<div class="col-sm-12 col-md-8">
							<div><?php echo $displayData->displayAdsInfo['ad_url']; ?></div>
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
								}
								?>
							</div>
						<?php
						}
						$td_value = '';

						if ($chargeoption == 0)
						{
							$td_value = Text::_('COM_SOCIALADS_ADMODE_IMP_TXT');
						}
						elseif ($chargeoption == 1)
						{
							$td_value = Text::_('COM_SOCIALADS_ADMODE_CLK_TXT');
						}
						elseif ($chargeoption == 2)
						{
							$td_value = Text::_('COM_SOCIALADS_ADMODE_DAY_TXT');
						}
						elseif ($chargeoption > 2)
						{
							$td_value = Text::_('COM_SOCIALADS_ADMODE_SLAB_TXT');
						} ?>
						<div class="col-sm-12 col-md-4">
							<strong> <?php echo Text::_('COM_SOCIALADS_ADMODE_DEFAULT_KEY'); ?></strong>
						</div>
						<div class="col-sm-12 col-md-8">
							<?php echo $td_value; ?>
						</div>

						<?php
						if ($chargeoption < 2)
						{
							$ad_chargeOpKey = ($chargeoption == 1) ? Text::_('COM_SOCIALADS_NUMBER_CLICKS') : Text::_('COM_SOCIALADS_NUMBER_IMPRESSIONS');

							// No of clicks or impression
							$ad_chargeOpValue = $ad_totaldisplay;
							?>
							<div class="col-sm-12 col-md-4"><strong><?php echo $ad_chargeOpKey; ?></strong> </div>
							<div class="col-sm-12 col-md-8"><?php echo $ad_chargeOpValue; ?></div>
						<?php
						}

						// If days then show day count
						elseif ($chargeoption == 2)
						{
							$ad_dayOpKey   = Text::_('COM_SOCIALADS_NUMBER_DAYS');

							// No of days
							$ad_dayOpValue = $ad_totaldisplay; ?>
							<div class="col-sm-12 col-md-4"><strong><?php echo $ad_dayOpKey; ?></strong> </div>
							<div class="col-sm-12 col-md-8"><?php echo $ad_dayOpValue; ?></div>
						<?php
						}
						else
						{
							$slabDetails = $socialadsPaymentHelper->getSlabDetails($chargeoption);
							?>
							<div class="col-sm-12 col-md-4">
								<strong><?php echo Text::sprintf('COM_SOCIALADS_ADMODE_DEFAULT_SLAB_KEY', $slabDetails['label']); ?></strong>
							</div>
							<div class="col-sm-12 col-md-8">
								<?php echo SaCommonHelper::getFormattedPrice($slabDetails['price']); ?>
							</div>
						<?php
						}

						// Jomsocial points
						if (!isset($this->ad_points))
						{
						?>
						<?php
						$ad_chargeOpKey   = Text::_('COM_SOCIALADS_TOTAL_AMT');
						$ad_chargeOpValue = SaCommonHelper::getFormattedPrice($adDetail['original_amount']);
						?>
						<div class="col-sm-12 col-md-4"><strong><?php echo $ad_chargeOpKey; ?></strong></div>
						<div class="col-sm-12 col-md-8"><?php echo $ad_chargeOpValue; ?></div>
						<?php
						}

						if (isset($this->ad_points))
						{
						?>
							<div class="col-sm-12 col-md-4"><strong><?php echo Text::_('POINTS'); ?></strong></div>
							<div class="col-sm-12 col-md-8"><?php echo $adDetail['original_amount']; ?></div>
							<?php
							// @TODO - check if this is needed
							$makecal = 'makepayment();';
						}

						$cop_dis = 0;

						if (!empty($adDetail['coupon']))
						{
							// Get payment HTML
							$adcop = $socialadsPaymentHelper->getcoupon($adDetail['coupon']);

							if ($adcop)
							{
								// Discount rate
								if ($adcop[0]->val_type == 1)
								{
									$cop_dis = ($adcop[0]->value / 100) * $adDetail['original_amount'];
								}
								else
								{
									$cop_dis = $adcop[0]->value;
								}
							}
							else
							{
								$cop_dis = 0;
							}
						}

						$discountedPrice = $adDetail['original_amount'] - $cop_dis; ?>

						<!-- Coupon discount display:block-->
					</div>
					<div class="row" id= "dis_cop">
						<div class="col-sm-12 col-md-4"><strong><?php echo Text::_('COM_SOCIALADS_DIS_COP'); ?></strong> </div>
						<div class="col-sm-12 col-md-8"><?php echo  SaCommonHelper::getFormattedPrice($cop_dis); ?></div>
					</div>

					<!-- Tax amount -->
					<div class="row" id= "ad_tax" style="">
						<div class="col-sm-12 col-md-4"><strong><?php echo Text::sprintf('COM_SOCIALADS_TAX_AMT', isset($tax) ? $tax[0] : ''); ?></strong> </div>
						<div class="col-sm-12 col-md-8"><?php echo SaCommonHelper::getFormattedPrice($adDetail['tax']); ?></div>
					</div>

					<!-- NET TOTAL AMOUNT after tax and coupon-->
					<div class="row" id= "dis_amt">
						<div class="col-sm-12 col-md-4"><strong><?php echo Text::_('COM_SOCIALADS_NET_AMT_PAY'); ?></strong> </div>
						<div class="col-sm-12 col-md-8"><?php echo SaCommonHelper::getFormattedPrice($adDetail['amount']); ?>
						</div>
					</div>
					<!-- Table-responsive -->
				</div>
				<!-- ad detail end -->

				<div class="col-sm-6 col-xs-12">
				<h4>
					<?php echo Text::_('COM_SOCIALADS_AD_LOOK');?>
				</h4>
				<div class = "clearfix">&nbsp;</div>
					<?php echo $preview; ?>
				</div>
			</div>
		</div>
		<hr>
		<!-- show payment option start -->
		<div class="row">
			<div class="paymentHTMLWrapper">
				<?php
				if (!empty($adDetail['amount']))
				{
				?>
					<div class="p-4" id="sa_paymentlistWrapper">
						<div class="form-group " id="sa_paymentGatewayList">
							<?php
							$default = "";
							$label   = Text::_('COM_SOCIALADS_AD_SEL_GATEWAY');
							$gateway_div_style = 1;

							// If only one geteway then keep it as selected
							if (!empty($ad_gateways))
							{
								// Id and value is same
								$default = $ad_gateways[0]->id;
							}

							// If only one geteway then keep it as selected
							if (!empty($ad_gateways) && count($ad_gateways) == 1)
							{
								// Id and value is same
								$default = $ad_gateways[0]->id;
								$label   = Text::_('COM_SOCIALADS_AD_SEL_GATEWAY');

								// To show payment radio btn btn-defaulteven if only one payment gateway
								$gateway_div_style = 1;
							} ?>

							<label label-default class="col-lg-4 col-md-4 col-sm-5 col-xs-12">
								<h4><?php echo $label; ?></h4>
							</label>
							<div class="col-md-8 col-sm-7 col-xs-12" style="<?php echo ($gateway_div_style == 1)?"" : "display:none;" ?>">
								<?php
								if (empty($ad_gateways))
								{
									echo Text::_('COM_SOCIALADS_AD_SELECT_PAYMENT_GATEWAY');
								}
								else
								{
									// Removed selected gateway Bug #26993
									$default = '';
									$imgpath = Uri::root() . "media/com_sa/images/ajax.gif";
									$ad_fun = "onchange=\"sa.create.getPaymentGatewayHtml(this.value," . $displayData->order_id . ",'"
									. trim($paymentMode) . "', '" . Text::_('COM_SOCIALADS_PAYMENT_GATEWAY_LOADING_MSG') . "', '"
									. $imgpath . "');\"";

									foreach ($ad_gateways as $gateway)
									{
									?>
										<div class="radio">
										  <label>
											<input type="radio" name="ad_gateways"
												id="<?php echo $gateway->id; ?>"
												value="<?php echo $gateway->id ?>"
												aria-label="..."
												 <?php echo $ad_fun; ?> >
												<?php echo $gateway->name; ?>
										  </label>
										</div>
										<?php
									}
								} ?>
							</div>

							<?php
							if (empty($gateway_div_style))
							{
							?>
								<div class="col-lg-10 col-md-10 col-sm-9 col-xs-12 qtc_left_top">
									<?php
									// Id and value is same
									echo $ad_gateways[0]->name;
									?>
								</div>
							<?php
							}
							?>
						</div>
						<!-- END OF form-group-->
					</div>
				<?php
				}
				else
				{
				?>
					<div id="sa_payHtmlDiv">
						<form method="post" name="sa_freePlaceOrder" class="" id="sa_freePlaceOrder">
							<div class="techjoomla-bootstrap" >
								<input type="hidden" name="option" value="com_sa">
								<input type="hidden" id="task" name="task" value="payment.sa_processFreeOrder">
								<input type="hidden" name="order_id" value="<?php echo $displayData->order_id; ?>">

								<div class="form-actions">
									<input type="submit" class="btn btn-default btn-success btn-lg" value="<?php echo Text::_('COM_SOCIALADS_AD_CONFORM_ORDER'); ?>">
								</div>
							</div>
						</form>
					</div>
					<?php
				}
				?>
			</div>
			<!-- end of paymentHTMLWrapper-->
		<div>
		<!-- show payment option end -->
	</fieldset>
</div>
<?php
if (!empty($adDetail['amount']))
{
    ?>
	<!-- show payment hmtl form-->
	<div id="sa_payHtmlDiv">
		<div class="form-actions">
			<button id="btnWizardPrev" type="button" style="display:none" class="btn btn-primary pull-left" >
				<i class="icon-circle-arrow-left icon-white" ></i><?php echo Text::_('COM_SOCIALADS_PREV'); ?>
			</button>
			<button id="sa_cancel" type="button" class="btn btn-danger float-end" style="margin-right:1%;" onclick="sa.create.cancelCreate()">
				<?php echo Text::_('COM_SOCIALADS_CANCEL'); ?>
			</button>
		</div>
	</div>
<?php
}
?>
