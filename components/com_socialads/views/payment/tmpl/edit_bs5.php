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
			    techjoomla . jQuery('#nav-tab #payment_infor_tab') . tab('show');

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
	<div class="page-header mb-4">
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
			<div class="tabbable">
				<nav>
					<div class="nav nav-tabs" id="nav-tab" role="tablist">
						<button class="nav-link active" id="amount_tab" data-bs-toggle="tab" data-bs-target="#amount" type="button" role="tab" aria-controls="amount" aria-selected="true"><?php echo Text::_('COM_SOCIALADS_WALLET_PAYMENT_AMOUNT'); ?></button>
						<button class="nav-link showNextTab" id="payment_infor_tab" data-bs-toggle="tab" data-bs-target="#payment_infor" type="button" role="tab" aria-controls="payment_infor" aria-selected="false"><?php echo Text::_('COM_SOCIALADS_WALLET_PAYMENT_GATEWAY'); ?></button>
					</div>
				</nav>
				<div class="tab-content mt-3" id="nav-tabContent">
					<div class="tab-pane fade show active" id="amount" role="tabpanel" aria-labelledby="amount_tab">
						<div class="row p-2">
							<div class="form-group row mb-4">
								<div class="col-lg-3 col-md-2 col-sm-3 col-xs-12 form-label fw-semibold">
									<?php echo $this->form->getLabel('amount'); ?>
								</div>
								<div class="col-lg-5 col-md-6 col-sm-9 col-xs-12" id="amount">
									<div class="input-group input-large">
										<?php echo $this->form->getInput('amount'); ?>
										<span class="input-group-text"><?php echo $params->get('currency');?></span>
									</div>
								</div>
							</div>
							<div class="form-group row coupon-display" id="showCoupon">
								<div class="col-lg-3 col-md-2 col-sm-3 col-xs-12 form-label fw-semibold"><?php echo $this->form->getLabel('coupon_result'); ?></div>
								<!--
								<div class="col-lg-6 col-md-6 col-sm-9 col-xs-12 controls"><?php echo $this->form->getInput('coupon_result');?></div>
								-->

								<div class="col-lg-5 col-md-6 col-sm-9 col-xs-12 controls">
									<label class="radio">
									<input type="radio" name="coupon_result" id="coupon_result1" value="1"><?php echo Text::_('JYES');?>
									</label>
									<label class="radio px-3">
									<input type="radio" name="coupon_result" id="coupon_result0" value="0" checked="checked"><?php echo Text::_('JNO');?>
									</label>
								</div>
							</div>

							<div class="form-group row" id="couponHide">
								<div class="col-lg-2 col-md-2 col-sm-3 col-xs-12 form-label"><?php echo $this->form->getLabel('coupon_code'); ?></div>
								<div class="col-lg-6 col-md-6 col-sm-7 col-xs-12">
									<div class="input-group">
										<?php echo $this->form->getInput('coupon_code'); ?>
										<button type="button" class="btn .btn-default btn-success" onclick="sa.payment.applyCoupon('<?php echo Session::getFormToken();?>')">
											<?php echo Text::_('COM_SOCIALADS_PAYMENT_COUPON_APPLY');?>
										</button>
									</div>
								</div>
							</div>
							<div id="coupon_discount" class="form-group row d-none">
								<label class="col-lg-2 col-md-2 col-sm-3 col-xs-12 form-label"><?php echo Text::_('COM_SOCIALAD_PAYMENT_COUPON_DISCOUNT');?></label>
									<div id="coupon_value" class="col-lg-6 col-md-6 col-sm-7 col-xs-12">
								</div>
							</div>
							<div id="dis_amt1" class="form-group row d-none">
								<label class="col-lg-2 col-md-2 col-sm-3 col-xs-12 form-label"><?php echo Text::_('COM_SOCIALAD_PAYMENT_FINAL_AMOUNT');?></label>
								<div id="dis_amt" class="col-lg-6 col-md-6 col-sm-7 col-xs-12 qtc_text"></div>
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
								<a class="float-end btn btn-primary btn-sm showNextTab make-payment-page p-2"><?php echo Text::_('COM_SOCIALADS_SAVE_AND_NEXT');?></a>
							</div>
						</div>
					</div>
					<div class="tab-pane fade p-3" id="payment_infor" role="tabpanel" aria-labelledby="payment_infor_tab">
						<h4><?php echo strtoupper(Text::_('COM_SOCIALADS_ORDER_SUMMARY')); ?></h4>
						<div class="row mt-2">
							<div class="col-md-12">
								<table class="table table-striped table-bordered table-hover table-light border">
									<tr class="bg-primary">
										<td width="70%"><h6><?php echo Text::_('COM_SOCIALADS_DESCRIPTION'); ?></h6></td>
										<td width="15%"><h6><?php echo Text::_('COM_SOCIALADS_QUANTITY'); ?></h6></td>
										<td width="15%"><h6><?php echo Text::_('COM_SOCIALADS_AMOUNT'); ?></h6></td>
									</tr>
									<tr>
										<td> <?php echo Text::_('COM_SOCIALADS_FUND_ADDED_TO_ACCOUNT'); ?></td>
										<td> 1</td>
										<td> <span class="pull-right formAmount"></span></td>
									</tr>
								</table>
							</div>
						</div>

						<div class="row mt-1 px-2">
							<div class="col-sm-12 col-md-10"><span class="pull-right"> <?php echo Text::_('COM_SOCIALADS_SUBTOTAL'); ?> </span></div>
							<div class="col-sm-12 col-md-2 px-4"><span class="pull-right formAmount"></span></div>
						</div>

						<div class="row mt-1 px-2" id= "dis_cop">
							<div class="col-sm-12 col-md-10"><span class="pull-right"> <?php echo Text::_('COM_SOCIALADS_DISCOUNT'); ?> </span></div>
							<div class="col-sm-12 col-md-2 px-4"><span class="pull-right couponValue"></span></div>
						</div>

						<!-- Tax amount -->
						<div class="row mt-1 px-2" id= "ad_tax" style="">
							<div class="col-sm-12 col-md-10"><span class="pull-right"> <?php echo Text::sprintf('COM_SOCIALADS_TAX_AMT'); ?> </span></div>
							<div class="col-sm-12 col-md-2 px-4"><span class="pull-right taxValue"></span></div>
						</div>

						<div class="row">
							<div class="col-md-12 pull-right">
								<hr>
							</div>
						</div>

						<!-- NET TOTAL AMOUNT after tax and coupon-->
						<div class="row px-2">
							<div class="col-sm-12 col-md-10 fw-semibold"><span class="pull-right"> <?php echo Text::_('COM_SOCIALADS_GROSS_AMOUNT'); ?> </span> </div>
							<div class="col-sm-12 col-md-2 px-4"><span class="pull-right netAmount fw-semibold"></span></div>
						</div>

						<div class="row sa-mt-2">
							<div class="col-sm-12 col-md-10">
								<div class="form-group d-none" id="coupon_div">
									<div id="coupon"> </div>
								</div>
							</div>
						</div>

						<div class="form-group row border shadow-sm px-2 py-3 mb-3 mx-1 bg-white rounded" id="pay_gateway">
							<label class="col-lg-3 col-md-2 col-sm-3 col-xs-12 form-label fw-bold">
								<?php echo Text::_('COM_SOCIALAD_PAYMENT_SELECT_PAYMENT_GATEWAY');?>
							</label>
							<div class="col-lg-9 col-md-10 col-sm-9 col-xs-12 controls">
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
		</div>

	</form>
	<div id="html-container"></div>
</div>
