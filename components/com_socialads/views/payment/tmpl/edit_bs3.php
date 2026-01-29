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
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Session\Session;

if (JVERSION < '4.0.0')
{
	HTMLHelper::_('bootstrap.tooltip');
	HTMLHelper::_('behavior.formvalidator');
	HTMLHelper::_('formbehavior.chosen', 'select');
	HTMLHelper::_('behavior.keepalive');
}

$params = ComponentHelper::getParams('com_socialads');
$user = Factory::getUser();
?>

<div class="<?php echo SA_WRAPPER_CLASS;?>" id="sa-payment">
	<script type="text/javascript">
		var currency    = "<?php echo SaCommonHelper::getCurrencySymbol(); ?>";
		var imgpath     = "<?php echo Uri::root(true) . '/media/com_sa/images/ajax.gif'; ?>";
		var submit      = "<?php echo Text::_('COM_SOCIALADS_SUBMIT');?>";
		var min_pre_balance = "<?php echo $params->get('min_pre_balance')?>";

		techjoomla.jQuery(document).ready(function()
		{
			techjoomla.jQuery("#couponHide").hide();

			techjoomla.jQuery("#jform_amount").val(min_pre_balance);

			techjoomla.jQuery(".alphaCheck").keyup(function()
			{
				sa.checkForZeroAndAlpha(this,'46', Joomla.Text._('COM_SOCIALAD_PAYMENT_ENTER_NUMERICS'));
			});

			techjoomla.jQuery("#showCoupon").click(function()
			{
				var rad = techjoomla.jQuery('input[name=coupon_result]:checked').val()
				sa.payment.showCoupon(rad, '<?php $params->get('min_pre_balance')?>');
			});
			techjoomla.jQuery(".pluginShow").click(function()
			{
				sa.payment.makePayment(this.value)
			});


			let totalPaymentGateways = techjoomla.jQuery('input[name ="payment_gateway"]').length;
			if (totalPaymentGateways === 1) {
				techjoomla.jQuery('input[name ="payment_gateway"]')[0].checked = true;
				techjoomla.jQuery('input[name ="payment_gateway"]').attr('checked', 'checked').trigger('change');
			}

			techjoomla.jQuery(".showNextTab").click(function() {
				techjoomla.jQuery('a[href="#amount"]').parent().removeClass('active');
				techjoomla.jQuery('#amount').parent().removeClass('active');

				techjoomla.jQuery('a[href="#payment_infor"]').parent().addClass('active');
				techjoomla.jQuery('#payment_infor').parent().addClass('active');

				var amount = techjoomla.jQuery('#jform_amount').val();
				var originalAmount = amount;
				var isCouponApplied = techjoomla.jQuery('input[name="coupon_result"]:checked').val();
				var couponValue = 0;

				if (Number(isCouponApplied))
				{
					if (techjoomla.jQuery('#dis_amt').text())
					{
						couponValue = techjoomla.jQuery('#coupon_value').text();
						amount = techjoomla.jQuery('#dis_amt').text();
					}
				}
				else
				{
					couponValue = currency + ' ' + couponValue;
				}

				if(amount)
				{
					techjoomla.jQuery.ajax({
						url: Joomla.getOptions('system.paths').base + '/index.php?option=com_sa&task=payment.getPaymentTax&amount=' + amount + '&tmpl=component',
						/*url: '?option=com_socialads&controller=buildad&task=autoSave&stepId=' + stepId + '&tmpl=component&format=raw&sa_sentApproveMail=' + sa_sentApproveMail,*/
						type: 'GET',
						dataType: 'json',
						success: function (response) {
							techjoomla.jQuery('.netAmount').text(currency + ' ' + response.amountAfterTax)
							techjoomla.jQuery('.couponValue').text(couponValue)
							techjoomla.jQuery('.taxValue').text(currency + ' ' + response.appliedTax)
							techjoomla.jQuery('.formAmount').text(currency + ' ' +originalAmount)
						},
						error: function (jqXHR, textStatus, errorThrown) {
							// Error
						}
					});
				}
			});
		});
	</script>
	<div class="page-header">
		<h2><?php echo Text::_('COM_SOCIALAD_PAYMENT_MAKE_PAYMENT'); ?></h2>
		<span>
			<?php echo Text::_('COM_SOCIALADS_WALLET_PAYMENT_AMOUNT_PAGE_DESCRIPTION'); ?>
		</span>
	</div>
	<form name="adminForm" class="form-validate form-horizontal" id="hello" action="" method="post" class="form-validate" enctype="multipart/form-data">
		<?php
		$gatewayselect = array();
		$gateways = $params->get('gateways');
		$gateways = (array) $gateways;

		foreach ($this->gatewayplugin as $gateway)
		{
			if (!in_array($gateway->element, $gateways))
				continue;
			$gatewayname = ucfirst(str_replace('plugpayment', '', $gateway->name));
			$gatewayselect[] = HTMLHelper::_('select.option', $gateway->element, $gatewayname);
		}
		?>
		<div class="container" >
			<ul class="nav nav-tabs" id="AdWalletTab">
				<li class="active" ><a href="#amount" data-toggle="tab"><?php echo Text::_('COM_SOCIALADS_WALLET_PAYMENT_AMOUNT'); ?></a></li>
				<li class="showNextTab"><a href="#payment_infor" data-toggle="tab"><?php echo Text::_('COM_SOCIALADS_WALLET_PAYMENT_GATEWAY'); ?></a></li>
			</ul>
			<div class="tab-content mt-3">
				<div class="tab-pane active" id="amount">
					<div class="row">
						<div class="form-group row">
							<div class="col-lg-2 col-md-2 col-sm-3 col-xs-12 form-label">
								<?php echo $this->form->getLabel('amount'); ?>
							</div>
							<div class="col-lg-4 col-md-4 col-sm-9 col-xs-12" id="amount">
								<div class="input-group">
									<?php echo $this->form->getInput('amount'); ?>
									<span class="input-group-addon"><?php echo $params->get('currency');?></span>
								</div>
							</div>
						</div>
						<div class="form-group row coupon-display" id="showCoupon">
							<div class="col-lg-2 col-md-2 col-sm-3 col-xs-12 form-label"><?php echo $this->form->getLabel('coupon_result'); ?></div>
							<!--
							<div class="col-lg-9 col-md-9 col-sm-9 col-xs-12"><?php echo $this->form->getInput('coupon_result');?></div>
							-->

							<div class="col-lg-9 col-md-9 col-sm-9 col-xs-12">
								<label class="radio-inline">
									<input type="radio" name="coupon_result" id="coupon_result1" value="1"><?php echo Text::_('JYES');?>
								</label>

								<label class="radio-inline">
									<input type="radio" name="coupon_result" id="coupon_result0" value="0" checked="checked"><?php echo Text::_('JNO');?>
								</label>
							</div>

						</div>
						<div class="form-group row" id="couponHide">
							<div class="col-lg-2 col-md-2 col-sm-3 col-xs-12 form-label"><?php echo $this->form->getLabel('coupon_code'); ?></div>
							<div class="col-lg-6 col-md-6 col-sm-7 col-xs-12"><?php echo $this->form->getInput('coupon_code'); ?>
								<button type="button" class="btn .btn-default btn-success pymentCoupon" onclick="sa.payment.applyCoupon('<?php echo Session::getFormToken();?>')">
									<?php echo Text::_('COM_SOCIALADS_PAYMENT_COUPON_APPLY');?>
								</button>
							</div>
						</div>
						<div id="coupon_discount" class="form-group row hidePaymentFields">
							<label class="col-lg-2 col-md-2 col-sm-3 col-xs-12 form-label"><?php echo Text::_('COM_SOCIALAD_PAYMENT_COUPON_DISCOUNT');?></label>
								<div id="coupon_value" class="col-lg-9 col-md-9 col-sm-9 col-xs-12">
							</div>
						</div>
						<div id="dis_amt1" class="form-group row hidePaymentFields">
							<label class="col-lg-2 col-md-2 col-sm-3 col-xs-12 form-label"><?php echo Text::_('COM_SOCIALAD_PAYMENT_FINAL_AMOUNT');?></label>
							<div id="dis_amt" class="col-lg-9 col-md-9 col-sm-9 col-xs-12 qtc_col-lg-9 col-md-9 col-sm-9 col-xs-12_text"></div>
						</div>
						<!--
						//@TODO- CHANGES NEED FOR RECURRING
						<input type="hidden" name="arb_flag" id="arb_flag" value="<?php //echo  ($this->sa_recuring == '1' || $params->get('recurring_payments') == '1')? '1': '0'; ?>">
						-->
						<input type="hidden" name="option" value="com_socialads" />
						<input type="hidden" name="controller" value="" />
						<input type="hidden" name="task" value="save" />
						<!--
						//@TODO- cHANGES NEED FOR RECURRING
							<input type="hidden" name="arb_flag" id="arb_flag" value="<?php //echo  ($this->sa_recuring == '1' || $params->get('recurring_payments')== '1')? '1': '0'; ?>">
						-->
						<?php echo HTMLHelper::_('form.token'); ?>
					</div>

					<div class="row">
						<div class="col-md-12">
							<a class="float-end btn btn-primary btn-sm showNextTab make-payment-page" href="#payment_infor" data-toggle="tab"><?php echo Text::_('COM_SOCIALADS_SAVE_AND_NEXT');?></a>
						</div>
					</div>
				</div>
				<div class="tab-pane" id="payment_infor">
					<h4><?php echo strtoupper(Text::_('COM_SOCIALADS_ORDER_SUMMARY')); ?></h4>
					<div class="row mt-1">
						<div class="col-md-12">
							<table class="table">
								<tr class="bg-primary">
									<td width="70%"><h5><?php echo Text::_('COM_SOCIALADS_DESCRIPTION'); ?></h5></td>
									<td width="15%"><h5><?php echo Text::_('COM_SOCIALADS_QUANTITY'); ?></h5></td>
									<td width="15%"><h5><?php echo Text::_('COM_SOCIALADS_AMOUNT'); ?></h5></td>
								</tr>
								<tr>
									<td> <?php echo Text::_('COM_SOCIALADS_FUND_ADDED_TO_ACCOUNT'); ?></td>
									<td> 1</td>
									<td> <span class="pull-right formAmount"></span></td>
								</tr>
							</table>
						</div>
					</div>

					<div class="row mt-1">
						<div class="col-sm-12 col-md-10"><span class="pull-right"> <?php echo Text::_('COM_SOCIALADS_SUBTOTAL'); ?> </span></div>
						<div class="col-sm-12 col-md-2"><span class="pull-right formAmount"></span></div>
					</div>

					<div class="row mt-1" id= "dis_cop">
						<div class="col-sm-12 col-md-10"><span class="pull-right"> <?php echo Text::_('COM_SOCIALADS_DISCOUNT'); ?> </span></div>
						<div class="col-sm-12 col-md-2"><span class="pull-right couponValue"></span></div>
					</div>

					<!-- Tax amount -->
					<div class="row mt-1" id= "ad_tax" style="">
						<div class="col-sm-12 col-md-10"><span class="pull-right"> <?php echo Text::sprintf('COM_SOCIALADS_TAX_AMT'); ?> </span></div>
						<div class="col-sm-12 col-md-2"><span class="pull-right taxValue"></span></div>
					</div>

					<div class="row">
						<div class="col-md-5 pull-right">
							<hr>
						</div>
					</div>

					<!-- NET TOTAL AMOUNT after tax and coupon-->
					<div class="row">
						<div class="col-sm-12 col-md-10"><span class="pull-right"> <?php echo Text::_('COM_SOCIALADS_GROSS_AMOUNT'); ?> </span> </div>
						<div class="col-sm-12 col-md-2"><span class="pull-right netAmount"></span></div>
					</div>

					<div class="row sa-mt-2">
						<div class="col-sm-12 col-md-10">
							<div class="form-group d-none" id="coupon_div">
								<div id="coupon"> </div>
							</div>
						</div>
					</div>

					<div class="form-group row sa-mt-2" id="pay_gateway">
						<label class="col-lg-2 col-md-2 col-sm-3 col-xs-12 form-label">
							<?php echo Text::_('COM_SOCIALAD_PAYMENT_SELECT_PAYMENT_GATEWAY');?>
						</label>
						<div class="col-lg-9 col-md-9 col-sm-9 col-xs-12">
							<?php
							if (empty($gatewayselect))
							{
								echo Text::_('COM_SOCIALADS_AD_SELECT_PAYMENT_GATEWAY');
							}
							else
							{
								// Removed selected gateway Bug #26993
								$default = '';
								$imgpath = Uri::root() . "media/com_sa/images/ajax.gif";
								$ad_fun = "onclick='sa.payment.makePayment(this.value)'";

								foreach ($gatewayselect as $gateway)
								{
								?>
									<div class="radio">
									<label>
										<input type="radio" name="ad_gateways"
											id="<?php echo $gateway->value; ?>"
											value="<?php echo $gateway->value ?>"
											aria-label="..."
											<?php echo $ad_fun; ?> >
											<?php echo $gateway->text; ?>
									</label>
									</div>
									<?php
								}
							} ?>
						</div>
					</div>
				</div>
			</div>
		</div>
	</form>
	<div id="html-container"></div>
</div>
