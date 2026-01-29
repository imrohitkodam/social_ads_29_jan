<?php
/**
 * @version     SVN:<SVN_ID>
 * @package     Com_Socialads
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2015 TechJoomla. All rights reserved
 * @license     GNU General Public License version 2, or later
 */

// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\HTML\HTMLHelper;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

HTMLHelper::_('bootstrap.renderModal', 'a.modal');
JHtml::addIncludePath(JPATH_COMPONENT . '/helpers/html');

if (JVERSION < '4.0')
{
	HTMLHelper::_('formbehavior.chosen', 'select');
}

HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('bootstrap.renderModal', 'a.modal');

$versionObj = new SaVersion;
$options = array("version" => $versionObj->getMediaVersion());

if (JVERSION < '5.0.0')
{
	HTMLHelper::stylesheet('media/com_sa/vendors/font-awesome/css/font-awesome.min.css', $options);
}
else 
{
	HTMLHelper::stylesheet('media/com_sa/vendors/font-awesome/css/font-awesome-6-5-1.min.css', $options);
}

$model        = $this->getModel('ads');
$ad_params    = ComponentHelper::getParams('com_socialads');
$payment_mode = $ad_params->get('payment_mode');
$userId       = $this->user->get('id');
$listOrder    = $this->state->get('list.ordering');
$listDirn     = $this->state->get('list.direction');
$canOrder     = $this->user->authorise('core.edit.state', 'com_socialads');
$saveOrder    = $listOrder == 'a.ordering';
$statsdataforlinechart = $this->statsforbar;
$totalclicks  = 0;
$totalimpressions = 0;
$totalctr = 0;
$canDo = SocialadsHelper::getActions();
?>
<style>
	.sa-wrapper .fromTodateFields .field-calendar .btn {
    border-radius: 0 !important; 
}
</style>
<div class="<?php echo SA_WRAPPER_CLASS;?>" id="sa-ads">
	<form action="" method="post" name="adminForm" id="adminForm">
		<div>
			<h1><?php echo Text::_('COM_SOCIALADS_MANAGE_ADS');?></h1>
		</div>
		<div id="container-fluid">
			<div class="row">
			<?php
			if (JVERSION >= '3.0'):
			?>
				<div class="col-md-12">
					<div>
						<?php echo $this->toolbarHTML;?>
					</div>
					<div class="clearfix"> </div>
					<hr class="hr-condensed" />
					<div class="filter-search btn-group float-start">
						<label for="filter_search" class="element-invisible">
							<?php echo Text::_('JSEARCH_FILTER'); ?>
						</label>
						<input type="text" name="filter_search" id="filter_search" class="form-control"
							placeholder="<?php echo Text::_('COM_SOCIALADS_ADS_SEARCH'); ?>"
							value="<?php echo $this->escape($this->state->get('filter.search')); ?>"
							title="<?php echo Text::_('JSEARCH_FILTER'); ?>" />
							<button class="btn btn-md btn-primary border-0 border-start hasTooltip" type="submit" title="<?php echo Text::_('JSEARCH_FILTER_SUBMIT'); ?>">
							<i class="fa fa-search"></i>
							</button>
							<button class="btn btn-md btn-secondary hasTooltip" id="clear-search-button" type="button" title="<?php echo Text::_('JSEARCH_FILTER_CLEAR'); ?>">
									<i class="fa fa-remove"></i>
							</button>
					</div>
					<div class="btn-group float-end col-12 col-sm-12 col-md-2 col-lg-1 hidden-phone">
						<?php echo $this->pagination->getLimitBox(); ?>
					</div>
				</div>
			<?php
			endif; ?>
			</div>
			<div class="row">
				<div class="col-md-12">
					<div class="row mt-3">
						<div class="col-md-6 col-sm-12 col-xs-6 d-flex p-0 mb-4">
							<div class="form-inline float-start fromTodateFields ">
								<div class="col-md-5 col-sm-4 col-12  float-start ms-2">
									<label for="to" class="hidden-xs"><?php echo Text::_("COM_SOCIALADS_STATS_TO_DATE"); ?></label>
									<div class="d-flex">
										<?php
										echo HTMLHelper::_('calendar',$this->state->get('filter.to'), 'to', 'to', '%Y-%m-%d', array(
											'class' => 'inputbox form-control input-xs sa-dashboard-calender', 'placeholder' => Text::_('COM_SOCIALADS_STATS_TO_DATE')
										));
										?>
										<button 
											class="btn btn-secondary hasTooltip rounded-start-0" 
											id="clear-all" 
											onclick="jQuery('#to').val(''); jQuery('#adminForm').submit();" 
											type="button" 
											title="<?php echo Text::_('JSEARCH_FILTER_CLEAR'); ?>">
											<i class="fa fa-remove"></i>
										</button>
									</div>
								</div>
								<div class="col-md-5 col-sm-4 col-12 float-start ms-2">
									<label for="from" class="hidden-xs"><?php echo Text::_('COM_SOCIALADS_STATS_FROM_DATE'); ?></label>
									<div class="d-flex">
										<?php
										echo HTMLHelper::_('calendar', $this->state->get('filter.from'), 'from', 'from', '%Y-%m-%d', array(
											'class' => 'inputbox form-control input-xs sa-dashboard-calender', 'placeholder' => Text::_('COM_SOCIALADS_STATS_FROM_DATE')
										));
										?>
										<button 
											class="btn btn-secondary hasTooltip rounded-start-0" 
											id="clear-all" 
											onclick="jQuery('#from').val(''); jQuery('#adminForm').submit();" 
											type="button" 
											title="<?php echo Text::_('JSEARCH_FILTER_CLEAR'); ?>">
											<i class="fa fa-remove"></i>
										</button>
									</div>
								</div>
							</div>
						</div>
						<div class="col-md-6 col-sm-12 col-xs-6 mt-6 p-0 pt-4">
							<?php
								if ($ad_params->get('payment_mode') == 'wallet_mode')
								{
									?>
									<div class="btn-group float-end col-12 col-sm-12 col-md-2 col-lg-4 hidden-phone social-ads-filter-margin-left me-2">
										<?php
										echo HTMLHelper::_('select.genericlist', $this->campaignsoptions, "filter_campaignslist", 'class="ad-status inputbox form-select input-medium" size="1"
										onchange="document.adminForm.submit();" name="campaignslist"', "value", "text", $this->state->get('filter.campaignslist'));
										?>
									</div>
									<?php
								} 
							?>
							<div class="btn-group float-end col-12 col-sm-12 col-md-2 col-lg-3 hidden-phone social-ads-filter-margin-left me-2">
								<?php echo HTMLHelper::_('select.genericlist', $this->zonesoptions, "filter_zoneslist", 'class="ad-status inputbox form-select input-medium" size="1" onchange="document.adminForm.submit();" name="zoneslist"', "value", "text", $this->state->get('filter.zoneslist')); ?>
							</div>
							<div class="btn-group ms-2 float-end col-12 col-sm-12 col-md-2 col-lg-3 hidden-phone hidden-tablet social-ads-filter-margin-left me-2">
								<?php
									echo HTMLHelper::_('select.genericlist', $this->adstatus, "filter_adstatus", 'class="ad-status inputbox form-select input-medium" size="1"
									onchange="document.adminForm.submit();" name="adstatus"', "value", "text", $this->state->get('filter.adstatus'));
								?>
							</div>
						</div>
					</div>
					<div class="row mt-2">
						
					</div>
				</div>
			</div>
			<div class="clearfix"></div>
			<?php
			if (empty($this->items)):
			?>
				<div class="clearfix">&nbsp;</div>
					<div class="alert">
						<?php
						echo Text::_('COM_SOCIALADS_FILTER_SEARCH_NOT_FOUND');
						?>
					</div>
			<?php
			else:
			?>
				<div class="col-md-12">
					<div id="no-more-tables" class="table-responsive ads-list">
						<table class="table table-striped table-bordered" id="dataList">
							<thead class="text-break table-primary">
								<tr>
									<th class="hidden-phone text-start">
										<input type="checkbox" name="checkall-toggle" value="" title="<?php echo Text::_('JGLOBAL_CHECK_ALL');?>" onclick="Joomla.checkAll(this)" />
									</th>
									<th>
										<?php echo HTMLHelper::_('grid.sort', 'COM_SOCIALADS_ADS_AD_TITLE', 'a.ad_title', $listDirn, $listOrder); ?>
									</th>
									<?php
									if (isset($this->items[0]->state)): ?>
										<th class="nowrap text-start">
											<?php echo HTMLHelper::_('grid.sort', 'JSTATUS', 'a.state', $listDirn, $listOrder);  ?>
										</th>
									<?php
									endif;
									if ($ad_params->get('payment_mode') == 'wallet_mode')
									{ ?>
										<th class="text-start">
											<?php echo HTMLHelper::_('grid.sort', 'COM_SOCIALADS_ADS_AD_CAMPAGIN', 'c.campaign', $listDirn, $listOrder); ?>
										</th>
									<?php
									} ?>
									<th class="text-start">
										<?php echo HTMLHelper::_('grid.sort', 'COM_SOCIALADS_ADS_AD_ZONE', 'a.ad_zone', $listDirn, $listOrder); ?>
									</th>
									<th class="text-start">
										<?php echo HTMLHelper::_('grid.sort', 'COM_SOCIALADS_ADS_AD_TYPE', 'a.ad_payment_type', $listDirn, $listOrder); ?>
									</th>
									<th class="text-start">
										<?php echo Text::_('COM_SOCIALADS_ADS_APPROVAL_STATUS'); ?>
									</th>
									<?php
									if ($payment_mode == 'pay_per_ad_mode')
									{
									?>
										<th class="text-start">
											<?php echo Text::_('COM_SOCIALADS_PAYMENT_STATUS'); ?>
										</th>
									<?php
									}
									?>
									<th class="sa-text-start">
										<?php echo HTMLHelper::_('grid.sort', 'COM_SOCIALADS_ADS_AD_TYPE_IMPRS', 'impressions', $listDirn, $listOrder); ?>
									</th>
									<th class="sa-text-start">
										<?php echo HTMLHelper::_('grid.sort', 'COM_SOCIALADS_ADS_AD_NO_OF_CLICKS', 'clicks', $listDirn, $listOrder); ?>
									</th>
									<th class="text-start">
										<?php echo Text::_('COM_SOCIALADS_ADS_AD_TYPE_C_T_R'); ?>
									</th>
									<th class="text-start">
										<?php echo Text::_('COM_SOCIALADS_ADS_IGNORES'); ?>
									</th>
									<th class="text-start">
										<?php echo Text::_('COM_SOCIALADS_ADS_AD_ACTIONS'); ?>
									</th>
								</tr>
							</thead>
							<tbody>
								<?php
								foreach ($this->items as $i => $item): ?>
									<tr class="row<?php echo $i % 2;?>">
										<td class="">
											<?php echo HTMLHelper::_('grid.id', $i, $item->ad_id); ?>
										</td>
										<td data-title="<?php echo Text::_('COM_SOCIALADS_ADS_AD_TITLE');?>" class="">
											<?php
											if ($canDo->get("core.edit")): ?>
												<a href="<?php echo Route::_('index.php?option=com_socialads&view=adform&ad_id='.(int) $item->ad_id). '&Itemid=' . $this->socialAdsitemId, false; ?>" class="text-dark text-decoration-none">
													<span class="ad-type-img">
														<?php
														if ($item->ad_guest == 1)
														{ ?>
															<i class="fa fa-user"></i>
														<?php
														}
														elseif (($item->ad_guest == 0) && ($item->ad_alternative == 0))
														{ ?>
															<i class="fa fa-users"></i>
														<?php
														}?>
													</span>
													<?php echo $this->escape($item->ad_title); ?>
												</a>
											<?php
											else: ?>
												<?php echo $this->escape($item->ad_title); ?>
											<?php
											endif;?>
										</td>
										<td class="text-center" data-title="<?php echo Text::_('JSTATUS');?>">
											<div>
												<a class="btn btn-micro hasTooltip" href="javascript:void(0);" title="<?php echo ($item->state) ? Text::_('COM_SOCIALADS_ADS_UNPUBLISH') : Text::_('COM_SOCIALADS_ADS_PUBLISH');?>"
												onclick="document.adminForm.cb<?php echo $i; ?>.checked=1; document.adminForm.boxchecked.value=1; Joomla.submitbutton('<?php echo ($item->state) ? 'ads.unpublish' : 'ads.publish';?>');">
													<img src="<?php echo Uri::root(true); ?>/media/com_sa/images/<?php echo ($item->state) ? 'publish.png' : 'unpublish.png';?>"/>
												</a>
											</div>
										</td>
										<?php
										if ($ad_params->get('payment_mode') == 'wallet_mode')
										{?>
											<td data-title="<?php echo Text::_('COM_SOCIALADS_ADS_AD_CAMPAGIN');?>">
												<?php
												if($item->campaign == "")
												{
													echo Text::_("COM_SOCIALADS_NA");
												}
												else
												{
													echo htmlspecialchars($item->campaign, ENT_COMPAT, 'UTF-8');
												} ?>
											</td>
										<?php
										}?>
										<td data-title="<?php echo Text::_('COM_SOCIALADS_ADS_AD_ZONE');?>">
											<?php echo htmlspecialchars($item->zone_name, ENT_COMPAT, 'UTF-8'); ?>
										</td>
										<td data-title="<?php echo Text::_('COM_SOCIALADS_ADS_AD_TYPE');?>">
											<?php
											if ($item->ad_alternative == 1)
											{
												echo Text::_('COM_SOCIALADS_ADS_AD_TYPE_ALT_AD');
											}
											elseif ($item->ad_noexpiry == 1)
											{
												echo Text::_('COM_SOCIALADS_ADS_AD_TYPE_UNLTD_AD');
											}
											else if ($item->ad_affiliate == 1)
											{
												echo Text::_('COM_SOCIALADS_AD_TYP_AFFI');
											}
											else
											{
												if ($item->ad_payment_type == 0)
												{
													echo Text::_('COM_SOCIALADS_ADS_AD_TYPE_IMPRS');
												}
												else if ($item->ad_payment_type == 1)
												{
													echo Text::_('COM_SOCIALADS_ADS_AD_TYPE_CLICKS');
												}
												else if ($item->ad_payment_type == 4)
												{
													echo Text::_('COM_SOCIALADS_CHARGE_ADS_TOGETHER');
												}
												else
												{?>
													<img src="<?php echo Uri::root(true) . '/media/com_sa/images/start_date.png' ?>">
														<?php echo $item->ad_startdate; ?>
														<br/>
													<?php
													if(($item->ad_enddate!='0000-00-00') && $item->ad_enddate)			//if not 0 then	only show end date
													{?>
														<img src="<?php echo Uri::root(true) . '/media/com_sa/images/end_date.png' ?>">
														<?php echo $item->ad_enddate;
													} ?>
												<?php
												}
											}?>
										</td>
										<td data-title="<?php echo Text::_('COM_SOCIALADS_ADS_APPROVAL_STATUS');?>">
											<?php if ($item->ad_approved == 1)
											{
												?>
												<span class="badge bg-success"><?php echo Text::_('COM_SOCIALADS_AD_CONFIRM'); ?></span>
												<?php
											}
											else if ($item->ad_approved == 2)
											{
												?>
												<span class="badge bg-secondary"><?php echo Text::_('COM_SOCIALADS_ADS_REJECTED'); ?></span>
												<?php
											}
											else
											{
												?>
												<span class="badge bg-info"><?php echo Text::_('COM_SOCIALADS_AD_PENDING'); ?></span>
												<?php
											}
											?>
										</td>
										<?php
										if ($payment_mode == 'pay_per_ad_mode')
										{ ?>
											<td class="text-center" data-title="<?php echo Text::_('COM_SOCIALADS_PAYMENT_STATUS');?>">
												<?php
												if ($item->ad_alternative == 1 || $item->ad_noexpiry == 1 || $item->ad_affiliate == 1)
												{ ?>
													<i class="fa fa-check"></i>
												<?php
												}
												else
												{
													switch ($item->status)
													{
														case 'P': ?>
															<i class="fa fa-clock-o"> </i>
															<?php
															break;
														case 'C': ?>
															<i class="fa fa-check"></i>
															<?php
															break;
														case 'RF': ?>
															<i class="fa fa-times"></i>
															<?php
															break;
														default: ?>
																<i class="fa fa-minus-circle"></i>
															<?php
															break;
													}
												} ?>
											</td>
											<?php
										}

										// Popover for ad credits and availability
										$out_of = '';

										if ($this->state->get('filter.from') || $this->state->get('filter.to'))
										{
											require_once JPATH_SITE . "/components/com_socialads/helpers/common.php";

											$from       = $this->state->get('filter.from') ? $this->state->get('filter.from') : null;
											$to       = $this->state->get('filter.to') ? $this->state->get('filter.to') : null;
											$impAndCount = SaCommonHelper::getImpressionAndClicks($item->ad_id, $from, $to);
											$clicks = $impAndCount['clicks'];
											$impr = $impAndCount['imp'];
										}
										else 
										{
											$clicks = $item->clicks;
											$impr = $item->impressions;
										}

										if ($payment_mode == 'pay_per_ad_mode')
										{
											// if camp ad is there den they dont have credits..
											if ($item->camp_id!=0 && !$item->bid_value)
											{
												$out_of = '';
											}
											elseif ($item->bid_value > 0)
											{
												$out_of = $item->bid_value;
											}
											elseif ($item->ad_alternative== 1 || $item->ad_noexpiry== 1 || $item->ad_affiliate == 1)
											{
												$out_of = Text::_('COM_SOCIALADS_CREDIT_UNLIMITED');
											}
											elseif ($item->ad_payment_type == 2)
											{
												$out_of = '';
											}
											else
											{
												$out_of = $item->ad_credits_balance;
											}

											if ($out_of)
											{
												$text_to_show = Text::_('COM_SOCIALADS_CREDITS_AVAILABLE')." : " . $out_of . '<br />';

												if ($item->ad_payment_type == 0)
												{
													$text_to_show .= Text::_('COM_SOCIALADS_ADS_AD_TYPE_IMPRS')." : " . $item->impressions;
												}

												if($item->ad_payment_type == 1)
												{
													$text_to_show .= Text::_('COM_SOCIALADS_ADS_AD_NO_OF_CLICKS')." : " . $item->clicks;
												}

												$out_of_anchor = '<a class="ad_type_tootip" data-content="' . $text_to_show.'" data-placement="top" data-html="html"  data-trigger="hover" rel="popover" >';
												$out_of_anchor = ' / ' . $out_of_anchor . $out_of . '</a>';
											}
										} ?>

										<td class="sa-text-right" data-title="<?php echo Text::_('COM_SOCIALADS_ADS_AD_NO_OF_IMPRESSIONS');?>">
											<?php
											if ($impr)
											{
												echo $impr;
											}
											else
											{
												echo "0";
											}

											// If ad is type is impreddions then show available credits
											if ($item->ad_payment_type == 0 && $out_of)
											{
												echo $out_of_anchor;
											} ?>
										</td>
										<td class="sa-text-right" data-title="<?php echo Text::_('COM_SOCIALADS_ADS_AD_NO_OF_CLICKS');?>">
											<?php
											if ($clicks)
											{
												echo $clicks;
											}
											else
											{
												echo "0";
											}

											// If ad is type is clicks then show available credits
											if ($item->ad_payment_type == 1 && $out_of)
											{
												echo $out_of_anchor;
											}
											?>
										</td>
										<td class="" data-title="<?php echo Text::_('COM_SOCIALADS_ADS_AD_TYPE_C_T_R');?>">
											<?php
											if ($item->impressions != 0)
											{
												$ctr = (($item->clicks) / ($item->impressions)) * 100;
												echo number_format($ctr, 6);
											}
											else
											{
												echo number_format($item->clicks, 6);
											}
											?>
										</td>
										<td class="text-center" data-title="<?php echo Text::_('COM_SOCIALADS_ADS_IGNORES');?>">
											<?php
											$ignorecounts = $model->getIgnorecount($item->ad_id);

											if ($ignorecounts == 0)
											{
												echo $ignorecounts;
											}
											else
											{
												$link = Route::_('index.php?option=com_socialads&view=ignores&tmpl=component&adid=' . $item->ad_id);
												echo HTMLHelper::_(
													'bootstrap.renderModal',
													'ignoresDetails' . $item->ad_id . 'Modal',
													array(
															'title'       => Text::_('COM_SOCIALADS_ADS_IGNORES'),
															'backdrop'    => 'static',
															'url'         => $link,
															'height'      => '400px',
															'width'       => '800px',
															'bodyHeight'  => 70,
															'modalWidth'  => 80,
														)
												);?>

												<a data-bs-toggle="modal" data-bs-target="#ignoresDetails<?php echo $item->ad_id ?>Modal" title="<?php echo Text::_('COM_SOCIALADS_ADS_IGNORES'); ?>" class="sa-btn-wrapper btn btn-sm">
													<?php echo $model->getIgnorecount($item->ad_id); ?>
												</a>

											<?php
											} ?>
										</td>
										<td data-title="<?php echo Text::_('COM_SOCIALADS_ADS_AD_ACTIONS');?>">
											<?php
											$itemid = SaCommonHelper::getSocialadsItemid('adsummary');
											$paymentHistory = Uri::root() . substr( Route::_('index.php?option=com_socialads&tmpl=component&view=adsummary&layout=payment_history&adid=' . (int) $item->ad_id . '&Itemid=' . (int) $itemid), strlen(Uri::base(true)) + 1
											);

											$stats = Uri::root() . substr( Route::_('index.php?option=com_socialads&tmpl=component&view=adsummary&adid=' . (int) $item->ad_id . '&Itemid=' . (int) $itemid), strlen(Uri::base(true)) + 1
											);

											$itemid = SaCommonHelper::getSocialadsItemid('preview');
											$link = Uri::root() . substr(Route::_('index.php?option=com_socialads&view=preview&tmpl=component&layout=default&id=' . (int) $item->ad_id . '&Itemid=' . (int) $itemid), strlen(Uri::base(true)) + 1
											);

											echo HTMLHelper::_(
												'bootstrap.renderModal',
												'adPreviewDetails' . $item->ad_id . 'Modal',
												array(
														'title'       => Text::_('COM_SOCIALADS_AD_PREVIEW'),
														'backdrop'    => 'static',
														'url'         => $link,
														'height'      => '400px',
														'width'       => '800px',
														'bodyHeight'  => 50,
														'modalWidth'  => 40,
													)
											);

											echo HTMLHelper::_(
												'bootstrap.renderModal',
												'paymentDetails' . $item->ad_id . 'Modal',
												array(
														'title'       => Text::_('COM_SOCIALADS_ADS_PAYMENT_HISTORY'),
														'backdrop'    => 'static',
														'url'         => $paymentHistory,
														'height'      => '400px',
														'width'       => '800px',
														'bodyHeight'  => 70,
														'modalWidth'  => 80,
													)	
											);

											echo HTMLHelper::_(
												'bootstrap.renderModal',
												'statsDetails' . $item->ad_id . 'Modal',
												array(
														'title'       => Text::_('COM_SOCIALADS_AD_STATS'),
														'backdrop'    => 'static',
														'url'         => $stats,
														'height'      => '400px',
														'width'       => '800px',
														'bodyHeight'  => 70,
														'modalWidth'  => 80,
													)
											);
											?>
											<div class="actions">	

												<a data-bs-toggle="modal" data-bs-target="#adPreviewDetails<?php echo $item->ad_id ?>Modal" title="<?php echo Text::_('COM_SOCIALADS_AD_PREVIEW'); ?>" class="sa-btn-wrapper btn btn-sm saActions">
													<i class="fa fa-picture-o"></i>
												</a>

												<?php
												if ($item->ad_url2)
												{
													?>

													<a href="<?php echo $item->ad_url1 . '://' . $item->ad_url2; ?>" target="_blank" class="sa-btn-wrapper btn-sm saActions btn" title="<?php echo Text::_('COM_SOCIALADS_AD_LINK'); ?>">
														<i class="fa fa-link">
														</i>
													</a>
													<?php
												}
												?>

												<a data-bs-toggle="modal" data-bs-target="#paymentDetails<?php echo $item->ad_id ?>Modal" title="<?php echo Text::_('COM_SOCIALADS_ADS_PAYMENT_HISTORY'); ?>" class="sa-btn-wrapper btn btn-sm saActions">
													<i class="fa fa-money"></i>
												</a>

												<a data-bs-toggle="modal" data-bs-target="#statsDetails<?php echo $item->ad_id ?>Modal" title="<?php echo Text::_('COM_SOCIALADS_AD_STATS'); ?>" class="sa-btn-wrapper btn btn-sm saActions">
													<i class="fa fa-bar-chart"></i>
												</a>
											</div>
										</td>
									</tr>
								<?php
								endforeach; ?>
							</tbody>
						</table>
					</div>
				</div>
				<div class="float-end clearfix">
					<?php echo $this->pagination->getListFooter(); ?>
				</div>
				<div class="clearfix"></div>
				<div class="alert alert-info row">
					<div class="col-md-4 col-sm-12 sa-legends-padding">
						<div>
							<i class="fa fa-user"></i> = <?php echo Text::_('COM_SOCIALADS_GUEST_ADS'); ?>
						</div>
						<div>
							<img src="<?php echo Uri::root(true) . '/media/com_sa/images/group.png'; ?>"/> = <?php echo Text::_('COM_SOCIALADS_TARGET_ADS'); ?>
						</div>
					</div>
					<div class="col-md-4 col-sm-12 sa-legends-padding">
						<div>
							<i class="fa fa-minus-circle"></i> = <?php echo Text::_('COM_SOCIALADS_NO_ADORDER'); ?>
						</div>
						<div>
							<i class="fa fa-clock-o" ></i> = <?php echo Text::_('COM_SOCIALADS_SA_PENDIN'); ?>
						</div>
					</div>
					<div class="col-md-4 col-sm-12 sa-legends-padding">
						<div>
							<i class="fa fa-check"></i> = <?php echo Text::_('COM_SOCIALADS_SA_CONFIRM') . ' / ' . Text::_('COM_SOCIALADS_SA_APPROVE'); ?>
						</div>
						<div>
							<i class="fa fa-times"></i> = <?php echo Text::_('COM_SOCIALADS_SA_REFUND') . ' / ' . Text::_('COM_SOCIALADS_SA_REJEC'); ?>
						</div>
					</div>
					<div class="clearfix"></div>
				</div>
			<?php
			endif; ?>
			<input type="hidden" id='reason' name="reason" value="" />
			<input type="hidden" name="task" value="" />
			<input type="hidden" name="boxchecked" value="0" />
			<input type="hidden" id='hidid' name="ad_id" value="" />
			<input type="hidden" id='hidstat' name="status" value="" />
			<input type="hidden" id='hidzone' name="zone" value="" />
			<input type="hidden" name="filter_order" value="<?php echo $listOrder; ?>" />
			<input type="hidden" name="filter_order_Dir" value="<?php echo $listDirn; ?>" />
			<?php echo HTMLHelper::_('form.token'); ?>
		</div>
	</form>
</div>
<script>
	sa.initSaJs();
	Joomla.submitbutton = function(action)
	{
		sa.ads.submitButtonAction(action)
	}
	techjoomla.jQuery(document).ready(function()
	{
		jQuery('.ad_type_tootip').popover();
		jQuery('.fromTodateFields .sa-dashboard-calender').change( function () {
			document.adminForm.submit();
		});
	});
</script>
