<?php
/**
 * @version    SVN:<SVN_ID>
 * @package    Socialads
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2015 TechJoomla. All rights reserved
 * @license    GNU General Public License version 2, or later
 */

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;

HTMLHelper::_('behavior.formvalidator');
HTMLHelper::_('jquery.token');

$versionObj = new SaVersion;
$options = array("version" => $versionObj->getMediaVersion());
?>

<div class="<?php echo SA_WRAPPER_CLASS;?>" id="sa-wallet">
	<?php
	$payment_mode = $this->params->get('payment_mode');

	if ($payment_mode == 'pay_per_ad_mode')
	{
		?>
		<div class="alert alert-warning" role="alert">
			<?php echo Text::_('COM_SOCIALADS_WALLET_NO_AUTH_SEE'); ?>
		</div>
		<?php
		return false;
	}

	$statesticsInformation = $this->wallet[0];

	// $pay_info = $this->wallet[0];
	$campaignName = $this->wallet[1];
	$coupon_code = $this->wallet[2];

	// Get coupon code array
	$adTitle = $this->wallet[3];

	// Newly added for JS toolbar inclusion
	$jomsocialToolbarExist = $this->params->get('jomsocial_toolbar');

	if (file_exists(JPATH_SITE . '/components/com_community') and $jomsocialToolbarExist == 1)
	{
		require_once JPATH_ROOT . '/components/com_community/libraries/toolbar.php';
		$toolbar = CFactory::getToolbar();
		$tool    = CToolbarLibrary::getInstance();
		HTMLHelper::script('components/com_community/assets/bootstrap/bootstrap.min.js', $options);
		?>
		<div id="proimport-wrap">
			<div id="community-wrap">
				<?php echo $tool->getHTML(); ?>
			</div>
		</div>
		<!-- End of JS tool bar import div -->
		<?php
	}
	// End for JS toolbar inclusion
	?>
	<form action="" method="post" name="adminForm3" id="adminForm3">
		<div class="page-header">
			<h2><?php echo Text::_('COM_SOCIALADS_WALLET');?></h2>
		</div>
		<div class="btn-toolbar wallet_filter form-inline row">
			<div class="col-md-12">
				<div class="float-end btn-group ms-2">
					<button type="button" name="go" title="<?php echo Text::_('COM_SOCIALADS_GO'); ?>" class="btn btn-success" id="go" onclick="this.form.submit();">
						<?php echo Text::_('COM_SOCIALADS_GO'); ?>
					</button>
				</div>
				<div class="float-end btn-group">
					<?php
					echo HTMLHelper::_('select.genericlist', $this->month, 'month', 'name="filter_order" class = "input-small form-select ms-2"', "value", "text", $this->lists['month']);
					echo HTMLHelper::_('select.genericlist', $this->year, 'year', 'name="filter_order" class = "input-small form-select ms-2"', "value", "text", $this->lists['year']);
					?>
				</div>
			</div>
		</div>
		<br/>
		<div>

		<div class="tabbable">
			<nav>
				<div class="nav nav-tabs" id="nav-tab" role="tablist">
					<button class="nav-link active" id="spent_table_tab" data-bs-toggle="tab" data-bs-target="#spent_table" type="button" role="tab" aria-controls="spent_table" aria-selected="true"><?php echo Text::_('COM_SOCIALADS_WALLET_ACCOUNT_HISTORY'); ?></button>
					<button class="nav-link" id="pay_table_tab" data-bs-toggle="tab" data-bs-target="#pay_table" type="button" role="tab" aria-controls="pay_table" aria-selected="false"><?php echo Text::_('COM_SOCIALADS_WALLET_PAYMENT_CREDTIS_ONLY'); ?></button>
				</div>
			</nav>

			<div class="tab-content mt-3" id="nav-tabContent">
				<div class="tab-pane fade show active" id="spent_table" role="tabpanel" aria-labelledby="spent_table_tab">
					<div class="row">
						<div id='no-more-tables'>
							<?php
							if (empty($statesticsInformation)) : ?>
								<div class="clearfix">&nbsp;</div>
								<div class="alert alert-no-items">
									<?php echo Text::_('COM_SOCIALADS_FILTER_SEARCH_NOT_FOUND'); ?>
								</div>
							<?php
							else : ?>
								<div class="btn-group float-end hidden-phone social-ads-filter-margin-left">
									<?php echo $this->pagination->getLimitBox(); ?>
								</div>
								<table class="table table-condensed table-responsive table-striped table-hover table-light border">
									<thead class="table-primary">
										<tr>
											<th>
												<?php echo HTMLHelper::tooltip(Text::_('COM_SOCIALADS_WALLET_DATE_WISE_RECORD'), '', '', Text::_('COM_SOCIALADS_WALLET_DATE'));?>
											</th>
											<th>
												<?php echo HTMLHelper::tooltip(Text::_('COM_SOCIALADS_WALLET_DESCRIPTION'), '', '', Text::_('COM_SOCIALADS_WALLET_DESCRIPTION'));?>
											</th>
											<th class="sa-text-left">
												<?php echo HTMLHelper::tooltip(Text::_('COM_SOCIALADS_WALLET_PAYMENT_AMOUNT'), '', '',
													Text::_('COM_SOCIALADS_WALLET_PAYMENT')); ?>
											</th>
											<th class="sa-text-left">
												<?php echo HTMLHelper::tooltip(Text::_('COM_SOCIALADS_WALLET_TOTAL_SPENT'),'', '', Text::_('COM_SOCIALADS_WALLET_TOTAL_SPENT')); ?>
											</th>
											<th class="sa-text-left">
												<?php echo HTMLHelper::tooltip(Text::_('COM_SOCIALADS_WALLET_BALANCE_REMAINING'), '', '', Text::_('COM_SOCIALADS_WALLET_BALANCE')); ?>
											</th>
										</tr>
									</thead>
									<?php
									$balance = 0;

									foreach ($statesticsInformation as $key)
									{
										$comment = explode('|', $key->comment); ?>
										<tr>
											<td style="width:15%" data-title="<?php echo Text::_('COM_SOCIALADS_WALLET_DATE_WISE_RECORD');?>">
												<?php echo $key->time; ?>
											</td>
											<td data-title="<?php echo Text::_('COM_SOCIALADS_WALLET_DESCRIPTION');?>">
												<?php
												if ($comment[0] == 'COM_SOCIALADS_WALLET_SPENT_DONE_FROM_MIGRATION' || $comment[0] == Text::_('COM_SOCIALADS_WALLET_SPENT_DONE_FROM_MIGRATION') || $comment[0] == 'SPENT_DONE_FROM_MIGRATION')
												{
													foreach ($adTitle as $index => $value)
													{
														if (isset($comment[1]) && $index == $comment[1])
														{
															echo Text::sprintf('COM_SOCIALADS_WALLET_SPENT_DONE_FROM_MIGRATION', $value);
														}
													}
												}
												elseif ($comment[0] == 'COM_SOCIALADS_INITIAL_FEE_MESSAGE')
												{
													echo Text::_('COM_SOCIALADS_INITIAL_FEE_MESSAGE');
												}
												elseif ($comment[0] == 'COM_SOCIALADS_WALLET_ADS_PAYMENT')
												{
													echo Text::_('COM_SOCIALADS_WALLET_ADS_PAYMENT');
												}
												elseif ($comment[0] == 'COM_SOCIALADS_WALLET_VIA_MIGRATTION')
												{
													foreach($adTitle as $index => $value)
													{
														if (isset($comment[1]) && $index == $comment[1])
														{
															echo Text::sprintf('COM_SOCIALADS_WALLET_VIA_MIGRATTION', $value);
														}
													}
												}
												elseif($comment[0] == 'COM_SOCIALADS_WALLET_COUPON_ADDED')
												{
													foreach ($coupon_code as $index => $value)
													{
														if ($index == $key->type_id)
														{
															$coupon_msg = Text::sprintf('COM_SOCIALADS_WALLET_COUPON_ADDED', $value[0]->coupon);
															echo $coupon_msg;
														}
													}
												}
												elseif($comment[0] == 'DAILY_CLICK_IMP')
												{
													foreach ($campaignName as $index => $value)
													{
														if ($index == $key->type_id)
														{
															$spent_msg = Text::sprintf('COM_SOCIALADS_WALLET_DAILY_CLICK_IMP', $value[0]->campaign);
															echo $spent_msg;
														}
													}
												}
											?>
											</td>

											<td style="width:10%" class="sa-text-right" data-title="<?php echo Text::_('COM_SOCIALADS_WALLET_PAYMENT_AMOUNT');?>">
												<?php echo SaCommonHelper::getFormattedPrice($key->credits, '', $this->params->get('decimals_count', 2)); ?>
											</td>

											<td style="width:10%" class="sa-text-right" data-title="<?php echo Text::_('COM_SOCIALADS_WALLET_TOTAL_SPENT');?>">
												<?php echo SaCommonHelper::getFormattedPrice($key->spent, '', $this->params->get('decimals_count', 2));?>
											</td>

											<td style="width:10%" class="sa-text-right" data-title="<?php echo Text::_('COM_SOCIALADS_WALLET_BALANCE');?>">
												<?php echo SaCommonHelper::getFormattedPrice($key->balance, '', $this->params->get('decimals_count', 2)); ?>
											</td>
										</tr>
										<?php
									}
									?>
								</table>
								<div class="float-end clearfix">
									<?php echo $this->pagination->getListFooter(); ?>
								</div>
							<?php
							endif; ?>
						</div>
					</div>
				</div>

				<div class="tab-pane fade" id="pay_table" role="tabpanel" aria-labelledby="pay_table_tab">
					<div class="row invitex_templates">
						<div id='no-more-tables'>
							<?php
							if (empty($statesticsInformation)) : ?>
								<div class="clearfix">&nbsp;</div>
								<div class="alert alert-no-items">
									<?php echo Text::_('COM_SOCIALADS_FILTER_SEARCH_NOT_FOUND'); ?>
								</div>
							<?php
							else : ?>
								<table class="table table-condensed table-responsive table-striped table-hover table-light border mt-1">
									<thead class="table-primary">
										<tr>
											<th>
												<?php echo HTMLHelper::tooltip(Text::_('COM_SOCIALADS_WALLET_DATE_WISE_RECORD'), '', '', Text::_('COM_SOCIALADS_WALLET_DATE')); ?>
											</th>
											<th>
												<?php echo HTMLHelper::tooltip(Text::_('COM_SOCIALADS_WALLET_PAYMENT_DONE'), '', '', Text::_('COM_SOCIALADS_WALLET_DESCRIPTION')); ?>
											</th>
											<th class="sa-text-left">
												<?php echo HTMLHelper::tooltip(Text::_('COM_SOCIALADS_WALLET_PAYMENT_AMOUNT'), '', '',
												Text::sprintf('COM_SOCIALADS_WALLET_PAYMENT', $this->params->get('currency'))); ?>
											</th>
										</tr>
									</thead>
									<tbody>
									<?php
									$totalCredits = 0;
									$walletstatesticsInformation = $this->walletTrasaction[0];
									foreach ($walletstatesticsInformation as $key)
									{
										$comment = explode('|', $key->comment);

										if (!empty($key->credits) && $key->credits != 0.00)
										{
											?>
											<tr>
												<td data-title="<?php echo Text::_('COM_SOCIALADS_WALLET_DATE_WISE_RECORD');?>">
													<?php echo $key->time; ?>
												</td>
												<td data-title="<?php echo Text::_('COM_SOCIALADS_WALLET_DESCRIPTION');?>">
													<?php
													if ($comment[0] == 'COM_SOCIALADS_WALLET_ADS_PAYMENT')
													{
														echo Text::_('COM_SOCIALADS_WALLET_ADS_PAYMENT');
													}
													elseif ($comment[0] == 'COM_SOCIALADS_WALLET_VIA_MIGRATTION')
													{
														foreach ($adTitle as $index => $value)
														{
															if (isset($comment[1]) && $index == $comment[1])
															{
																echo Text::sprintf('COM_SOCIALADS_WALLET_VIA_MIGRATTION', $value);
															}
														}
													}
													elseif ($comment[0] == 'COM_SOCIALADS_WALLET_COUPON_ADDED')
													{
														foreach ($coupon_code as $index => $value)
														{
															if ($index == $key->type_id)
															{
																$coupon_msg = Text::sprintf('COM_SOCIALADS_WALLET_COUPON_ADDED', $value[0]->coupon);
																echo $coupon_msg;
															}
														}
													}
													?>
												</td >
												<td class="sa-text-right" data-title="<?php echo Text::_('COM_SOCIALADS_WALLET_PAYMENT_AMOUNT');?>">
													<?php
													echo SaCommonHelper::getFormattedPrice($key->credits, '', $this->params->get('decimals_count', 2));
													$totalCredits = $totalCredits + $key->credits;
													?>
												</td>
											</tr>
										<?php
										}
									}
									?>
										<?php if ($totalCredits > 0):?>
											<tr>
												<td colspan="2" class="sa-text-right">
													<div class="pull-right">
														<strong><?php echo Text::_('COM_SOCIALADS_TOTAL_PAYMENT');?></strong>
													</div>
												</td>
												<td class="sa-text-right" data-title="<?php echo Text::_('COM_SOCIALADS_TOTAL_PAYMENT');?>">
													<strong><?php echo SaCommonHelper::getFormattedPrice($totalCredits, '', $this->params->get('decimals_count', 2));?></strong>
												</td>
											</tr>
										<?php endif;?>
									</tbody>
								</table>
							<?php
							endif; ?>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="row mt-3 mb-2">
			<div class="col-md-6 col-sm-12">
				<div class="form-actions" >	
					<div class="input-group row">
						<label class="form-label col-md-4 mt-2" title="<?php echo Text::_('COM_SOCIALADS_WALLET_DESC_REDEEM_COUPON'); ?>">
						<?php echo Text::_('COM_SOCIALADS_WALLET_REDEEM_COUPON'); ?></label>
						<input id="coupon_code" type="text" class="form-control col-md-5" name="coupon" placeholder="code" />
						<input type="button" class="btn btn-primary col-md-3" id="add_coupon" value="<?php echo Text::_('COM_SOCIALADS_SUBMIT'); ?>" onclick="sa.wallet.applyCouponCode('<?php echo Session::getFormToken(); ?>'); "/>
					</div>
				</div>
			</div>

			<div class="col-md-6 col-sm-12">
				<a href="<?php echo Route::_('index.php?option=com_socialads&view=payment&Itemid='.$this->paymentitemid); ?>" class="btn btn-success btn-sm float-end p-2"><?php echo Text::_('COM_SOCIALADS_AD_PAYMENT'); ?></a>
			</div>
		</div>
		<input type="hidden" name="option" value="com_socialads" />
	</form>
</div>
