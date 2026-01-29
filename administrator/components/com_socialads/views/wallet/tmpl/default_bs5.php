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
use Joomla\CMS\Language\Text;

HTMLHelper::_('behavior.formvalidator');

$adTitle = $this->wallet[3];
$coupon_code = $this->wallet[2];
?>
<div class="<?php echo SA_WRAPPER_CLASS; ?>" id="sa-wallet">
	<form action="" method="post" name="adminForm3" id="adminForm3">
		<div class="page-header">
			<h2><?php echo Text::_('COM_SOCIALADS_WALLET_DETAIL_TITLE');?></h2>
		</div>
		<div class="btn-toolbar wallet_filter">
			<div class = "pull-left btn-group">
				<?php
				echo HTMLHelper::_('select.genericlist', $this->month, 'month', 'name="filter_order" class = "input-small form-select"', "value", "text", $this->lists['month']);
				echo HTMLHelper::_('select.genericlist', $this->year, 'year', 'name="filter_order" class = "input-small ms-2 form-select"', "value", "text", $this->lists['year']);?>
			</div>
			<div class = "pull-left btn-group">
				<button type="button" name="go" title="<?php echo Text::_('COM_SOCIALADS_GO'); ?>" class="btn btn-success ms-2" id="go" onclick="this.form.submit();">
					<?php echo Text::_('COM_SOCIALADS_GO'); ?>
				</button>
			</div>
		</div>

		<div class = "clearfix">&nbsp;</div>
		<div>

		<?php
			echo HTMLHelper::_('uitab.startTabSet', 'AdWalletTab', ['active' => 'spent_table', 'recall' => true, 'breakpoint' => 768]);
			echo HTMLHelper::_('uitab.addTab', 'AdWalletTab', 'pay_table', Text::_('COM_SOCIALADS_WALLET_PAYMENT_CREDTIS_ONLY'));
			?>
				<div class="tab-pane table-responsive" id="pay_table">
					<div id='no-more-tables'>
						<?php
						if (empty($this->wallet)) : ?>
							<div class="clearfix">&nbsp;</div>
							<div class="alert alert-no-items">
								<?php echo Text::_('COM_SOCIALADS_FILTER_SEARCH_NOT_FOUND'); ?>
							</div>

						<?php
						else : ?>
						<table class="table table-condensed ">
							<thead>
								<tr>
									<th>
										<?php echo HTMLHelper::tooltip(Text::_('COM_SOCIALADS_WALLET_DATE_WISE_RECORD'), '', '', Text::_('COM_SOCIALADS_WALLET_DATE')); ?>
									</th>
									<th>
										<?php echo HTMLHelper::tooltip(Text::_('COM_SOCIALADS_WALLET_PAYMENT_DONE'), '', '', Text::_('COM_SOCIALADS_WALLET_DESCRIPTION')); ?>
									</th>
									<th class="sa-text-right">
										<?php echo HTMLHelper::tooltip(Text::_('COM_SOCIALADS_WALLET_PAYMENT_AMOUNT'), '', '',
										Text::_('COM_SOCIALADS_WALLET_PAYMENT')); ?>
									</th>
								</tr>
							</thead>

							<?php
							$walletstatesticsInformation = $this->wallet[0];
							$totalCredits = 0;

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
									<td class="sa-text-right" colspan="2">
										<div class="pull-right">
											<strong><?php echo Text::_('COM_SOCIALADS_TOTAL_PAYMENT');?></strong>
										</div>
									</td>
									<td class="sa-text-right" data-title="<?php echo Text::_('COM_SOCIALADS_TOTAL_PAYMENT');?>">
										<strong>
											<?php echo SaCommonHelper::getFormattedPrice($totalCredits, '', $this->params->get('decimals_count', 2)); ?>
										</strong>
									</td>
								</tr>
							<?php endif;?>
						</table>
					<?php
					endif; ?>
					</div>
				</div>
			<?php
			echo HTMLHelper::_('uitab.endTab');
			echo HTMLHelper::_('uitab.addTab', 'AdWalletTab', 'pay_table', Text::_('COM_SOCIALADS_WALLET_ACCOUNT_HISTORY')); 
			?>
				<div class="tab-pane active table-responsive" id="pay_table">
					<div id='no-more-tables'>
						<?php
						if (empty($this->wallet)) : ?>
							<div class="clearfix">&nbsp;</div>
							<div class="alert alert-no-items">
								<?php echo Text::_('COM_SOCIALADS_FILTER_SEARCH_NOT_FOUND'); ?>
							</div>

						<?php
						else : ?>
							<table class="table table-condensed ">
								<thead>
									<tr>
										<th>
											<?php echo HTMLHelper::tooltip(Text::_('COM_SOCIALADS_WALLET_DATE_WISE_RECORD'), '', '', Text::_('COM_SOCIALADS_WALLET_DATE'));?>
										</th>
										<th>
											<?php echo HTMLHelper::tooltip(Text::_('COM_SOCIALADS_WALLET_DESCRIPTION'), '', '', Text::_('COM_SOCIALADS_WALLET_DESCRIPTION'));?>
										</th>
										<th class="sa-text-right">
											<?php echo HTMLHelper::tooltip(Text::_('COM_SOCIALADS_WALLET_PAYMENT_AMOUNT'), '', '',
												Text::_('COM_SOCIALADS_WALLET_PAYMENT')); ?>
										</th>
										<th class="sa-text-right">
											<?php echo HTMLHelper::tooltip(Text::_('COM_SOCIALADS_WALLET_TOTAL_SPENT'),'', '', Text::_('COM_SOCIALADS_WALLET_TOTAL_SPENT')); ?>
										</th>

										<th class="sa-text-right">
											<?php echo HTMLHelper::tooltip(Text::_('COM_SOCIALADS_WALLET_BALANCE_REMAINING'), '', '', Text::_('COM_SOCIALADS_WALLET_BALANCE')); ?>
										</th>
									</tr>
								</thead>
								<?php
								$balance = 0;
								$statesticsInformation = $this->wallet[0];

								foreach ($statesticsInformation as $key)
								{
									$comment = explode('|', $key->comment); ?>
									<tr>
										<td style="width:15%" data-title="<?php echo Text::_('COM_SOCIALADS_WALLET_DATE_WISE_RECORD');?>">
											<?php echo $key->time; ?>
										</td>
										<td data-title="<?php echo Text::_('COM_SOCIALADS_WALLET_DESCRIPTION');?>">
											<?php
											$adTitle = $this->wallet[3];

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
											elseif ($comment[0] == 'COM_SOCIALADS_WALLET_ADS_PAYMENT')
											{
												echo Text::_('COM_SOCIALADS_WALLET_ADS_PAYMENT');
											}
											elseif ($comment[0] == 'COM_SOCIALADS_INITIAL_FEE_MESSAGE')
											{
												echo Text::_('COM_SOCIALADS_INITIAL_FEE_MESSAGE');
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
												$coupon_code = $this->wallet[2]; // get coupon code array

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
												$campaignName = $this->wallet[1];

												foreach ($campaignName as $index => $value)
												{
													if ($index == $key->type_id)
													{
														$camp = (isset($value[0]) && isset($value[0]->campaign)) ? $value[0]->campaign : $value;
														$spent_msg = Text::sprintf('COM_SOCIALADS_WALLET_DAILY_CLICK_IMP', $camp);
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
											<?php echo SaCommonHelper::getFormattedPrice($key->spent, '', $this->params->get('decimals_count', 2)); ?>
										</td>

										<td style="width:10%" class="sa-text-right" data-title="<?php echo Text::_('COM_SOCIALADS_WALLET_BALANCE');?>">
											<?php echo SaCommonHelper::getFormattedPrice($key->balance, '', $this->params->get('decimals_count', 2)); ?>
										</td>
									</tr>
									<?php
								}
								?>
							</table>
						<?php
						endif; ?>
					</div>
				</div>
			<?php
			echo HTMLHelper::_('uitab.endTab');
		?>
		<input type="hidden" name="option" value="com_socialads" />
	</form>
</div>
