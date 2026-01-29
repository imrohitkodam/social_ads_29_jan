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
use Joomla\CMS\HTML\HTMLHelper;

use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Plugin\PluginHelper;

HTMLHelper::_('behavior.formvalidator');
JHtmlBehavior::framework();
require_once JPATH_ROOT . '/components/com_socialads/helpers/engine.php';

// Fetch ad detail
if (empty($displayData->order_id))
{
?>
	<div>
		<div class="alert alert-error">
			<span ><?php echo Text::_('COM_SOCIALADS_AD_UNABLE_TO_TRACK_ORDER_ID'); ?> </span>
		</div>
	</div>
	<?php
	return false;
}

$socialadsPaymentHelper = new SocialadsPaymentHelper;
$adDetail               = $socialadsPaymentHelper->getOrderAndAdDetail($displayData->order_id, 1);

// VM:: hv to add and code for jomsical points ( we are looking later for jomscial points)
$gatwayName = 'bycheck';
$plugin = PluginHelper::getPlugin('payment', $gatwayName);
$paymentMode = $displayData->sa_params->get('payment_mode');

if (0 && $paymentMode == 'pay_per_ad_mode')
{
	$pluginParams = json_decode($plugin->params);
	$this->assignRef('ad_gateway', $pluginParams->plugin_name);
	$arb_enforce = '';
	$this->assignRef('arb_enforce', $pluginParams->arb_enforce);
	$arb_enforce = '';
	$this->assignRef('arb_support', $pluginParams->arb_support);
	$points1 = 0;

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
$adTotalDisplay = $adDetail['ad_credits_qty'];

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

$adGateways = $gateways;

// Getting payment list END
?>

<!--techjoomla-bootstrap -->
<div class="techjoomla-bootstrap ad_reviewAdmainContainer" >
	<fieldset class="sa-fieldset">
		<legend class="hidden-desktop">
			<?php echo Text::_('COM_SOCIALADS_CKOUT_ADS_SUMMERY'); ?>
		</legend>
		<!-- For ad detail and preview -->
		<div class="row-fluid">
			<!--ad details start -->
			<div class="span6">
				<h4>
					<?php echo Text::_('COM_SOCIALADS_AD_SHOW_ADS_DETAILS');?>
				</h4>
				<div class = "clearfix">&nbsp;</div>
				<div class="table-responsive">
					<table class=" table table-bordered">
						<tr>
							
							<td class="" width="30%"> <strong><?php echo Text::_('COM_SOCIALADS_AD_SHOW_TYPE'); ?> </strong> </td>
							<td class="" width="30%">
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
							<td> <strong><?php echo Text::_('COM_SOCIALADS_AD_SHOW_DESTINATION_URL'); ?> </strong> </td>
							<td>
								<div><?php echo $displayData->displayAdsInfo['ad_url']; ?></div>
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
							<td>
								<strong> <?php echo Text::_('COM_SOCIALADS_ADMODE_DEFAULT_KEY'); ?> </strong>
							</td>
							<td>
								<?php echo $td_value; ?>
							</td>
						</tr>

						<?php
						if ($chargeoption < 2)
						{
							$ad_chargeOpKey = ($chargeoption == 1) ? Text::_('COM_SOCIALADS_NUMBER_CLICKS') : Text::_('COM_SOCIALADS_NUMBER_IMPRESSIONS');

							// No of clicks or impression
							$ad_chargeOpValue = $adTotalDisplay;
							?>
							<tr>
								<td class=""> <strong> <?php echo $ad_chargeOpKey; ?> </strong> </td>
								<td class=""><?php echo $ad_chargeOpValue; ?></td>
							</tr>
						<?php
						}

						// If days then show day count
						elseif ($chargeoption == 2)
						{
							$ad_dayOpKey   = Text::_('COM_SOCIALADS_NUMBER_DAYS');

							// No of days
							$ad_dayOpValue = $adTotalDisplay; ?>
							<tr>
								<td class=""><strong>  <?php echo $ad_dayOpKey; ?> </strong> </td>
								<td class=""><?php echo $ad_dayOpValue; ?></td>
							</tr>
						<?php
						}
						else
						{
							$slabDetails = $socialadsPaymentHelper->getSlabDetails($chargeoption);
							?>
							<tr>
								<td class="">
									<strong> <?php echo Text::sprintf('COM_SOCIALADS_ADMODE_DEFAULT_SLAB_KEY', $slabDetails['label']); ?> </strong>
								</td>
								<td class="">
									<?php echo SaCommonHelper::getFormattedPrice($slabDetails['price']); ?>
								</td>
							</tr>
						<?php
						}

						// Jomsocial points
						if (!isset($this->ad_points))
						{
						?>
							<tr>
								<?php
								$ad_chargeOpKey   = Text::_('COM_SOCIALADS_TOTAL_AMT');
								$ad_chargeOpValue = SaCommonHelper::getFormattedPrice($adDetail['original_amount']);
								?>
								<td class=""> <strong><?php echo $ad_chargeOpKey; ?> </strong></td>
								<td class=""><?php echo $ad_chargeOpValue; ?></td>
							</tr>
						<?php
						}

						if (isset($this->ad_points))
						{
						?>
							<tr>
								<td class=""><strong><?php echo Text::_('POINTS'); ?></strong></td>
								<td class=""><?php echo $adDetail['original_amount']; ?></td>
							</tr>
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
						<tr id= "dis_cop">
							<td class=""><strong><?php echo Text::_('COM_SOCIALADS_DIS_COP'); ?><strong> </td>
							<td class=""><?php echo  SaCommonHelper::getFormattedPrice($cop_dis); ?></td>
						</tr>

						<!-- Tax amount -->
						<tr id= "ad_tax" style="">
							<td class=""><strong><?php echo Text::sprintf('COM_SOCIALADS_TAX_AMT', isset($tax) ? $tax[0] : ''); ?></strong></td>
							<td class=""><?php echo SaCommonHelper::getFormattedPrice($adDetail['tax']); ?></td>
						</tr>

						<!-- NET TOTAL AMOUNT after tax and coupon-->
						<tr id= "dis_amt">
							<td class=""><strong><?php echo Text::_('COM_SOCIALADS_NET_AMT_PAY'); ?></strong></td>
							<td class=""><?php echo SaCommonHelper::getFormattedPrice($adDetail['amount']); ?>
							</td>
						</tr>
					</table>
				</div>
				<!-- Table-responsive -->
			</div>
			<!-- ad detail end -->

			<div class="span6">
				<h4>
					<?php echo Text::_('COM_SOCIALADS_AD_LOOK');?>
				</h4>
				<div class = "clearfix">&nbsp;</div>
				<?php echo $preview; ?>
			</div>
		</div>
		<hr>
		<!-- show payment option start -->
		<div class="row-fluid">
			<div class="paymentHTMLWrapper">
				<?php
				$paymentListStyle = '';

				if (!empty($adDetail['amount']))
				{
				?>
					<div class="" id="sa_paymentlistWrapper" style="<?php echo $paymentListStyle; ?>">
						<div class="control-group " id="sa_paymentGatewayList">
							<?php
							$default = "";
							$lable   = Text::_('COM_SOCIALADS_AD_SEL_GATEWAY');
							$gateway_div_style = 1;

							// If only one geteway then keep it as selected
							if (!empty($adGateways))
							{
								// Id and value is same
								$default = $adGateways[0]->id;
							}

							// If only one geteway then keep it as selected
							if (!empty($adGateways) && count($adGateways) == 1)
							{
								// Id and value is same
								$default = $adGateways[0]->id;
								$lable = Text::_('COM_SOCIALADS_AD_SEL_GATEWAY');

								// To show payment radio btn even if only one payment gateway
								$gateway_div_style = 1;
							} ?>

							<label for="" class="control-label">
								<h4><?php echo $lable; ?></h4>
							</label>
							<div class="controls" style="<?php echo ($gateway_div_style == 1) ? "" : "display:none;" ?>">
								<?php
								if (empty($adGateways))
								{
									echo Text::_('COM_SOCIALADS_AD_SELECT_PAYMENT_GATEWAY');
								}
								else
								{
									// Removed selected gateway Bug #26993
									$default = '';
									$imgpath = Uri::root() . "media/com_sa/images/ajax.gif";
									$ad_fun  = "onchange=\"sa.create.getPaymentGatewayHtml(this.value," . $displayData->order_id .
									",'" . trim($displayData->sa_params->get('payment_mode', 'pay_per_ad_mode')) . "', '" .
									Text::_('COM_SOCIALADS_PAYMENT_GATEWAY_LOADING_MSG') . "', '" . $imgpath . "');\"";
									$pg_list = HTMLHelper::_('select.radiolist', $adGateways, 'ad_gateways', 'class="inputbox required" ' .
									$ad_fun . ' ', 'id', 'name', $default, false
									);
									echo $pg_list;
								} ?>
							</div>

							<?php
							if (empty($gateway_div_style))
							{
							?>
								<div class="controls qtc_left_top">
									<?php
									// Id and value is same
									echo $adGateways[0]->name;
									?>
								</div>
							<?php
							}
							?>
						</div>
						<!-- END OF control-group-->
				<?php
				}
				else
				{
				?>
					<div id="sa_payHtmlDiv">
						<form method="post" name="sa_freePlaceOrder" class="" id="sa_freePlaceOrder">
							<input type="hidden" name="option" value="com_sa">
							<input type="hidden" id="task" name="task" value="payment.sa_processFreeOrder">
							<input type="hidden" name="order_id" value="<?php echo $displayData->order_id; ?>">

							<div class="form-actions">
								<input type="submit" class="btn btn-success btn-large" value="<?php echo Text::_('COM_SOCIALADS_AD_CONFORM_ORDER'); ?>">
							</div>
						</form>
					</div>
				<?php
				}
				?>
				</div>

			</div>
			<!-- end of paymentHTMLWrapper-->
		</div>
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
			<button id="sa_cancel" type="button" class="btn btn-danger pull-right" style="margin-right:1%;" onclick="sa.create.cancelCreate()">
				<?php echo Text::_('COM_SOCIALADS_CANCEL'); ?>
			</button>
		</div>
	</div>
<?php
}
?>