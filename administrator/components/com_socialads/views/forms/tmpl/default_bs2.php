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
use Joomla\CMS\HTML\HTMLHelper;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;

JHtml::addIncludePath(JPATH_COMPONENT . '/helpers/html');

HTMLHelper::_('formbehavior.chosen', 'select');
HTMLHelper::_('bootstrap.renderModal', 'a.modal');

$versionObj = new SaVersion;
$options = array("version" => $versionObj->getMediaVersion());
HTMLHelper::stylesheet('media/com_sa/vendors/font-awesome/css/font-awesome.min.css', $options);

$model        = $this->getModel('forms');

$params       = ComponentHelper::getParams('com_socialads');
$payment_mode = $params->get('payment_mode');

$user         = Factory::getUser();
$userId       = $user->get('id');

$listOrder    = $this->state->get('list.ordering');
$listDirn     = $this->state->get('list.direction');
$canOrder     = $user->authorise('core.edit.state', 'com_socialads');
$saveOrder    = $listOrder == 'a.ordering';

if ($saveOrder)
{
	$saveOrderingUrl = 'index.php?option=com_socialads&task=forms.saveOrderAjax&tmpl=component';
	HTMLHelper::_('sortablelist.sortable', 'dataList', 'adminForm', strtolower($listDirn), $saveOrderingUrl);
}

$sortFields = $this->getSortFields();

if (JVERSION > 3.0)
{
	$pending_icon = ' icon-clock ';
}
else
{
	$pending_icon = ' icon-time ';
}

if (!empty($this->extra_sidebar))
{
	$this->sidebar .= $this->extra_sidebar;
}
?>

<div class="<?php echo SA_WRAPPER_CLASS; ?> sa-ads tjBs2 forms-listing-page">
	<?php
	if (!empty($this->sidebar)): ?>
		<div id="j-sidebar-container" class="span2">
			<?php echo $this->sidebar; ?>
		</div>
		<div id="j-main-container" class="span10">
	<?php
	else : ?>
		<div id="j-main-container">
	<?php
	endif; ?>
		<form action="<?php echo Route::_('index.php?option=com_socialads&view=forms'); ?>" method="post" name="adminForm" id="adminForm">
			<div id="filter-bar" class="btn-toolbar row-fluid">
				<div class="form-inline">
					<div class="filter-search input-group pull-left sa-mt-1">
						<label for="filter_search" class="element-invisible">
							<?php echo Text::_('JSEARCH_FILTER'); ?>
						</label>
						<input type="text" class="form-control" name="filter_search" id="filter_search"
							placeholder="<?php echo Text::_('COM_SOCIALADS_ADS_SEARCH'); ?>"
							value="<?php echo $this->escape($this->state->get('filter.search')); ?>"
							title="<?php echo Text::_('JSEARCH_FILTER'); ?> " />
						<button class="btn btn-outline-secondary hasTooltip" type="submit" title="<?php echo Text::_('JSEARCH_FILTER_SUBMIT'); ?>">
							<i class="icon-search"></i>
						</button>
						<button class="btn btn-outline-secondary hasTooltip" id="clear-search-button" type="button" title="<?php echo Text::_('JSEARCH_FILTER_CLEAR'); ?>">
							<i class="icon-remove"></i>
						</button>
					</div>
					<div class="btn-group sa-mt-1 span2 pull-right">
						<?php echo HTMLHelper::_('select.genericlist', $this->publish_states, "filter_published", 'class="sa-width-100" size="1" onchange="document.adminForm.submit();" name="filter_published"', "value", "text", $this->state->get('filter.state')); ?>
					</div>

					<?php
					if ($params->get('payment_mode') == 'wallet_mode')
					{
						?>
						<div class="btn-group sa-mt-1 span2 pull-right">
							<?php echo HTMLHelper::_('select.genericlist', $this->campaignsoptions, "filter_campaignslist", 'class="sa-width-100" size="1" onchange="document.adminForm.submit();" name="campaignslist"', "value", "text", $this->state->get('filter.campaignslist')); ?>
						</div>
						<?php	
					}
					?>
					<div class="btn-group sa-mt-1 span2 pull-right">
						<?php
							echo HTMLHelper::_('select.genericlist', $this->zoneOptions, "filter_zonelist", 'class="sa-width-100" size="1" onchange="document.adminForm.submit();" name="zonelist"', "value", "text", $this->state->get('filter.zonelist'));
						?>
					</div>
					<div class="btn-group sa-mt-1 span2 pull-right ">
						<?php
							$payment_status = $this->state->get('filter.ad_approved');
							echo HTMLHelper::_('select.genericlist', $this->ostatus, "filter.ad_approved", 'class="sa-width-100" size="1" onchange="document.adminForm.submit();" name="filter_ad_approved"', "value", "text", $this->state->get('filter.ad_approved'));
							?>
					</div>

					<?php
					if (JVERSION >= '3.0'): ?>
						<div class="btn-group sa-mt-1 span2 col-lg-1 pull-right limitboxdisplay">
							<label for="limit" class="element-invisible">
								<?php echo Text::_('JFIELD_PLG_SEARCH_SEARCHLIMIT_DESC'); ?>
							</label>
							<?php echo $this->pagination->getLimitBox(); ?>
						</div>
					<?php
					endif; ?>
				</div>
			</div>
			<div class="row-fluid">
				<div class="form-inline fromTodateFields sa-mt-1 span12 pull-left">
					<div class="span3">
						<label for="from" class="hidden-xs"><?php echo Text::_('COM_SOCIALADS_STATS_FROM_DATE'); ?></label>
						<?php
						echo HTMLHelper::_('calendar', $this->state->get('filter.from'), 'from', 'from', '%Y-%m-%d', array(
							'class' => 'inputbox form-control input-xs calenderChange', 'placeholder' => Text::_('COM_SOCIALADS_STATS_FROM_DATE')
						));
						?>
					</div>
					<div class="span3">
						<label for="to" class="hidden-xs"><?php echo Text::_("COM_SOCIALADS_STATS_TO_DATE"); ?></label>
						<?php
						echo HTMLHelper::_('calendar',$this->state->get('filter.to'), 'to', 'to', '%Y-%m-%d', array(
							'class' => 'inputbox form-control input-xs calenderChange', 'placeholder' => Text::_('COM_SOCIALADS_STATS_TO_DATE')
						));
						?>
					</div>
				</div>
			</div>
			<div class="clearfix sa-mt-1"> </div>
			<?php
			if (empty($this->items)): ?>
				<div class="clearfix">&nbsp;</div>
				<div class="alert alert-no-items">
					<?php echo Text::_('COM_SOCIALADS_FILTER_SEARCH_NOT_FOUND'); ?>
				</div>
			<?php
			else : ?>
				<div id = "no-more-tables">
					<table class="table table-striped sa-mt-1" id="dataList">
						<thead>
							<tr>
								<?php
								if (isset($this->items[0]->ordering)): ?>
									<th class="tj-width-1 nowrap center hidden-phone">
										<?php echo HTMLHelper::_('grid.sort', '<i class="icon-menu-2"></i>', 'a.ordering', $listDirn, $listOrder, null, 'asc', 'JGRID_HEADING_ORDERING'); ?>
									</th>
								<?php
								endif; ?>
								<th class="tj-width-1 center hidden-phone">
									<input type="checkbox" name="checkall-toggle" value="" title="<?php echo Text::_('JGLOBAL_CHECK_ALL'); ?>" onclick="Joomla.checkAll(this)" />
								</th>
								<?php
								if (isset($this->items[0]->state)): ?>
									<th class="tj-width-1 nowrap center">
										<?php echo HTMLHelper::_('grid.sort', 'JSTATUS', 'a.state', $listDirn, $listOrder); ?>
									</th>
								<?php
								endif; ?>
								<th class="tj-width-20">
									<?php echo HTMLHelper::_('grid.sort',  'COM_SOCIALADS_ADS_AD_TITLE', 'a.ad_title', $listDirn, $listOrder); ?>
								</th>
								<th class="center tj-width-1">
									<?php echo Text::_('COM_SOCIALADS_ADS_AD_PREVIEW'); ?>
								</th>
								<?php
								if ($payment_mode == 'wallet_mode'): ?>
									<th class="tj-width-10">
										<?php echo HTMLHelper::_('grid.sort',  'COM_SOCIALADS_ADS_AD_CAMPAGIN', 'c.campaign', $listDirn, $listOrder); ?>
									</th>
								<?php
								endif; ?>
								<th class="tj-width-10">
									<?php echo HTMLHelper::_('grid.sort',  'COM_SOCIALADS_ADS_AD_TYPE', 'a.ad_payment_type', $listDirn, $listOrder); ?>
								</th>
								<th class="tj-width-15">
									<?php echo HTMLHelper::_('grid.sort',  'COM_SOCIALADS_ADS_AD_ZONE', 'a.ad_zone', $listDirn, $listOrder); ?>
								</th>
								<th class="tj-width-5">
									<?php echo HTMLHelper::_('grid.sort',  'COM_SOCIALADS_ORDERS_CREATED_BY', 'a.created_by', $listDirn, $listOrder); ?>
								</th>
								<th class="center tj-width-5">
									<?php echo HTMLHelper::_('grid.sort', 'COM_SOCIALADS_ADS_APPROVAL_STATUS', 'a.ad_approved',  $listDirn, $listOrder); ?>
								</th>
								<?php
								if ($payment_mode == 'pay_per_ad_mode'): ?>
									<th class="center tj-width-5">
										<?php echo HTMLHelper::_('grid.sort', 'COM_SOCIALADS_ADS_PAYMENT_MODE', 'ao.status',  $listDirn, $listOrder); ?>
									</th>
								<?php
								endif; ?>
								<th class="tj-width-5 sa-text-right">
									<?php echo HTMLHelper::_('grid.sort',  'COM_SOCIALADS_ADS_AD_NO_OF_CLICKS', 'clicks', $listDirn, $listOrder); ?>
								</th>
								<th class="tj-width-5 sa-text-right">
									<?php echo HTMLHelper::_('grid.sort',  'COM_SOCIALADS_ADS_AD_NO_OF_IMPRESSIONS', 'impressions', $listDirn, $listOrder); ?>
								</th>
								<th class="tj-width-10 sa-text-right">
									<?php echo Text::_('COM_SOCIALADS_ADS_AD_TYPE_C_T_R'); ?>
								</th>
								<th class="tj-width-5 sa-text-right">
									<?php echo Text::_('COM_SOCIALADS_ADS_IGNORES'); ?>
								</th>
								<?php
								if (isset($this->items[0]->ad_id)): ?>
									<th class="tj-width-1 nowrap hidden-phone sa-text-right">
										<?php echo HTMLHelper::_('grid.sort', 'JGRID_HEADING_ID', 'a.ad_id', $listDirn, $listOrder); ?>
									</th>
								<?php
								endif; ?>
							</tr>
						</thead>
						<tbody>
							<?php
							foreach ($this->items as $i => $item) :
								$ordering   = ($listOrder == 'a.ordering');
								$canCreate	= $user->authorise('core.create',		'com_socialads');
								$canEdit	= $user->authorise('core.edit',			'com_socialads');
								$canCheckin	= $user->authorise('core.manage',		'com_socialads');
								$canChange	= $user->authorise('core.edit.state',	'com_socialads');
								$j = 0;

								$rowApprovalColor = '';

								switch ($item->ad_approved)
								{
									case 0:
										$rowApprovalColor = "warning";
									break;

									case 1:
										$rowApprovalColor = "";
									break;

									case 2:
										$rowApprovalColor = "error";
									break;
								}
								?>

								<tr class="row<?php echo $i % 2 . ' ' . $rowApprovalColor; ?>" >
									<?php
									if (isset($this->items[0]->ordering)): ?>
										<td class="order nowrap center hidden-phone tj-width-1">
											<?php
											if ($canChange) :
												$disableClassName = '';
												$disabledLabel = '';

												if (!$saveOrder) :
													$disabledLabel    = Text::_('JORDERINGDISABLED');
													$disableClassName = 'inactive tip-top';
												endif; ?>
												<span class="sortable-handler hasTooltip <?php echo $disableClassName?>" title="<?php echo $disabledLabel?>">
													<i class="icon-menu"></i>
												</span>
												<input type="text" style="display:none" name="order[]" size="5" value="<?php echo $item->ordering; ?>" class="width-20 text-area-order " />
												<?php
											else : ?>
												<span class="sortable-handler inactive" >
													<i class="icon-menu"></i>
												</span>
											<?php
											endif; ?>
										</td>
									<?php
									endif; ?>
									<td class="hidden-phone center tj-width-1">
										<?php echo HTMLHelper::_('grid.id', $i, $item->ad_id); ?>
									</td>
									<?php
									if (isset($this->items[0]->state)): ?>
										<td class="center tj-width-1" data-title="<?php echo Text::_('JSTATUS'); ?>">
											<?php echo HTMLHelper::_('jgrid.published', $item->state, $i, 'forms.', $canChange, 'cb'); ?>
										</td>
									<?php
									endif; ?>
									<td data-title="<?php echo Text::_('COM_SOCIALADS_ADS_AD_TITLE'); ?>" class="tj-width-20">
										<?php
										if ($canEdit): ?>
											<a href="<?php echo Route::_('index.php?option=com_socialads&task=form.edit&ad_id=' . (int) $item->ad_id); ?>">
												<span class="ad-type-img" >
													<?php
													if ($item->ad_guest == 1)
													{
														?>
														<i class="icon-user"></i>
														<?php
													}
													elseif (($item->ad_guest == 0) && ($item->ad_alternative == 0))
													{
														?>
														<img src="<?php echo Uri::root(true) . '/media/com_sa/images/group.png'?>" />
														<?php
													}
													?>
												</span>
												<?php echo htmlspecialchars($item->ad_title, ENT_COMPAT, 'UTF-8'); ?>
											</a>
										<?php
										else: ?>
												<span class="ad-type-img" >
												<?php
												if ($item->ad_guest == 1)
												{
													?>
													<i class="icon-user"></i>
													<?php
												}
												elseif (($item->ad_guest == 0) && ($item->ad_alternative == 0))
												{
													?>
													<img src="<?php echo Uri::root(true) . '/media/com_sa/images/group.png'?>" />
													<?php
												}
												?>
											</span>
											<?php echo htmlspecialchars($item->ad_title, ENT_COMPAT, 'UTF-8');
										endif; ?>

									</td>
									<td class="center tj-width-1" data-title="<?php echo Text::_('COM_SOCIALADS_ADS_AD_PREVIEW'); ?>">
										<?php $link = Route::_('index.php?option=com_socialads&view=previewad&tmpl=component&layout=default&id=' . $item->ad_id); ?>
										<?php
											$stats = Route::_('index.php?option=com_socialads&view=adsummary&tmpl=component&layout=default&adid=' . $item->ad_id);

											echo HTMLHelper::_(
												'bootstrap.renderModal',
												'previewDetails' . $item->ad_id . 'Modal',
												array(
														'title'       => Text::_('COM_SOCIALADS_INVOICE_ORDER_DETAIL'),
														'backdrop'    => 'static',
														'url'         => $link,
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
											); ?>
											<div class="btn-group actions">	
												<a data-toggle="modal" data-target="#previewDetails<?php echo $item->ad_id ?>Modal" class="sa-btn-wrapper btn-mini saActions">
													<span class="editlinktip hasTip" title="<?php echo Text::_('COM_SOCIALADS_AD_PREVIEW'); ?>" >
														<img src="<?php echo Uri::root() . '/media/com_sa/images/preview.png'?>">
													</span>
												</a>

												<?php
												if ($item->ad_url2)
												{
													?>

													<a href="<?php echo $item->ad_url1 . '://' . $item->ad_url2; ?>" target="_blank" class="sa-btn-wrapper btn-mini saActions btn" title="<?php echo Text::_('COM_SOCIALADS_AD_LINK'); ?>">
														<i class="fa fa-link">
														</i>
													</a>
													<?php
												}
												?>

												<a data-toggle="modal" data-target="#statsDetails<?php echo $item->ad_id ?>Modal" title="<?php echo Text::_('COM_SOCIALADS_AD_STATS'); ?>" class="sa-btn-wrapper btn btn-mini saActions sa-ml-5">
													<i class="fa fa-bar-chart"></i>
												</a>
											</div>
									</td>
									<?php
									if ($payment_mode == 'wallet_mode')
									{
										?>
										<td data-title="<?php echo Text::_('COM_SOCIALADS_ADS_AD_CAMPAGIN'); ?>" class="tj-width-10">
											<?php echo $item->campaign ? htmlspecialchars($item->campaign, ENT_COMPAT, 'UTF-8') : ''; ?>
										</td>
									<?php
									}
									?>
									<td data-title="<?php echo Text::_('COM_SOCIALADS_ADS_AD_TYPE'); ?>" class="tj-width-10">
										<?php
										if ($item->ad_alternative == 1)
										{
											echo Text::_('COM_SOCIALADS_ADS_AD_TYPE_ALT_AD');
										}
										elseif($item->ad_noexpiry == 1)
										{
											echo Text::_('COM_SOCIALADS_ADS_AD_TYPE_UNLTD_AD');
										}
										elseif($item->ad_affiliate == 1)
										{
											echo Text::_('COM_SOCIALADS_ADS_AD_TYPE_AFFI');
										}
										else
										{
											if ($item->ad_payment_type == 0)
											{
												echo Text::_('COM_SOCIALADS_ADS_AD_TYPE_IMPRS');
											}
											elseif ($item->ad_payment_type == 1)
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
												if(($item->ad_enddate != '0000-00-00') )			//if not 0 then	only show end date
												{?>
													<img src="<?php echo Uri::root(true) . '/media/com_sa/images/end_date.png' ?>">
													<?php echo $item->ad_enddate;
												} ?>
											<?php
											}
										} ?>
									</td>
									<td data-title="<?php echo Text::_('COM_SOCIALADS_ADS_AD_ZONE'); ?>" class="tj-width-15">
										<?php
										if ($item->zone_name)
										{
											echo htmlspecialchars($item->zone_name, ENT_COMPAT, 'UTF-8');
										}
										else
										{
											echo HTMLHelper::_('select.genericlist',
											$this->zone_array,
											"layout_select",
											'size="1" onchange="saAdmin.ads.selectZoneIfNotExists(' . $item->ad_id . ', this);" class="input-medium "',
											"value", "text", "0");
										}
										?>
									</td>
									<td class="tj-width-5" data-title="<?php echo Text::_('COM_SOCIALADS_ORDERS_CREATED_BY'); ?>">
										<?php echo htmlspecialchars($item->created_by, ENT_COMPAT, 'UTF-8'); ?>
									</td>
									<td data-title="<?php echo Text::_('COM_SOCIALADS_ADS_APPROVAL_STATUS'); ?>" class="center tj-width-5">
										<?php
										$whichever = '';

										switch ($item->ad_approved)
										{
											case 1:
												$whichever = Text::_('COM_SOCIALADS_ADS_COMPLETED');
											break;

											case 2:
												$whichever = Text::_('COM_SOCIALADS_ADS_REJECTED');
											break;
										}

										echo HTMLHelper::_('select.genericlist', $this->status, 'status' . $j, 'class="input-medium ad-status" size="1" onchange="saAdmin.ads.selectAdStatus(' . $item->ad_id . ', this);" data-oldvalue="'. $item->ad_approved .'"', "value", "text", $item->ad_approved);
										?>
									</td>

									<?php
									if ($payment_mode == 'pay_per_ad_mode')
									{
										?>
										<td class="center tj-width-5" data-title="<?php echo Text::_('COM_SOCIALADS_ADS_PAYMENT_MODE');?>">
											<?php
												if ($item->ad_alternative == 1 || $item->ad_noexpiry == 1 || $item->ad_affiliate == 1)
												{
													?>
													<i class="icon-ok"></i>
													<?php
												}
												else
												{
													switch ($item->status)
													{
														case 'P': ?>
															<i class="icon-clock"> </i>
															<?php
															break;
														case 'C': ?>
															<i class="icon-ok"></i>
															<?php
															break;
														case 'RF': ?>
															<i class="icon-remove"></i>
															<?php
															break;
														case 'E': ?>
															<i class="icon-remove"></i>
															<?php
															break;
														default: ?>
															<i class="icon-minus"></i>
															<?php
															break;
													}
												}
											?>
										</td>
										<?php
									}

									// Popover for ad credits and availability
									$out_of = 0;

									if ($payment_mode == 'pay_per_ad_mode')
									{
										// If camp ad is there den they dont have credits..
										if ($item->camp_id != 0 && !$item->bid_value)
										{
											$out_of = '';
										}
										elseif($item->bid_value > 0)
										{
											$out_of = $item->bid_value;
										}
										elseif($item->ad_alternative== 1 || $item->ad_noexpiry== 1 || $item->ad_affiliate == 1)
										{
											$out_of = Text::_('COM_SOCIALADS_ADS_UNLIMITED');
										}
										elseif($item->ad_payment_type == 2)
										{
											$out_of = '';
										}
										else
										{
											$out_of = $item->ad_credits_balance;
										}
									}
									?>

									<td class="tj-width-5 sa-text-right" data-title="<?php echo Text::_('COM_SOCIALADS_ADS_AD_NO_OF_CLICKS'); ?>">
										<?php
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
										// If ad is type is clicks then show available credits
										if ($item->ad_payment_type == 1 && $out_of)
										{
											echo $item->clicks . " / " . $out_of;
										}
										elseif ($clicks)
										{
											echo $clicks;
										}
										else
										{
											echo "0";
										}
										?>
									</td>

									<td class="tj-width-5 sa-text-right" data-title="<?php echo Text::_('COM_SOCIALADS_ADS_AD_NO_OF_IMPRESSIONS'); ?>">
										<?php
										// If ad is type is impressions then show available credits
										if ($item->ad_payment_type == 0 && $out_of)
										{
											echo $item->impressions . " / " . $out_of;
										}
										elseif ($impr)
										{
											echo $impr;
										}
										else
										{
											echo "0";
										}
										?>
									</td>

									<td class="tj-width-10 sa-text-right" data-title="<?php echo Text::_('COM_SOCIALADS_ADS_AD_TYPE_C_T_R'); ?>">
										<?php
										$adClicks      = $clicks;
										$adImpressions = $impr;

										if ($adImpressions != 0)
										{
											$ctr = (($adClicks) / ($adImpressions)) * 100;
											echo number_format($ctr, 6);
										}
										else
										{
											echo number_format($adClicks, 6);
										}
										?>
									</td>
									<td class="tj-width-5 sa-text-right" data-title="<?php echo Text::_('COM_SOCIALADS_ADS_IGNORES'); ?>">
										<?php
										$ignoreCounts = $model->getIgnorecount($item->ad_id);

										if ($ignoreCounts == 0)
										{
											echo $ignoreCounts;
										}
										else
										{
											$link = 'index.php?option=com_socialads&view=ignores&tmpl=component&adid=' . $item->ad_id; ?>
											<a href="<?php echo $link; ?>" rel="{handler: 'iframe', size: {x: 800, y: 350}}" class="modal">
												<?php echo $model->getIgnorecount($item->ad_id); ?>
											</a>
										<?php
										}
										?>
									</td>
									<?php
									if (isset($this->items[0]->ad_id)): ?>
										<td class="hidden-phone tj-width-1 sa-text-right" data-title="<?php echo Text::_('JGRID_HEADING_ID'); ?>">
											<?php echo (int) $item->ad_id; ?>
										</td>
									<?php
									endif;
									?>
								</tr>
							<?php
							endforeach; ?>
						</tbody>
					</table>

					<div>
						<?php echo $this->pagination->getListFooter(); ?>
					</div>

					<!-- Show legends and meaning-->
					<div class="alert alert-info">
						<div class="pull-left sa-legends-padding">
							<div>
								<i class="icon-user"></i> = <?php echo Text::_('COM_SOCIALADS_ADS_NON_TARGETED_ADS'); ?>
							</div>
							<div>
								<img src="<?php echo Uri::root(true) . '/media/com_sa/images/group.png'; ?>"> = <?php echo Text::_('COM_SOCIALADS_ADS_TARGETED_ADS'); ?>
							</div>
						</div>

						<div class="pull-left sa-legends-padding">
							<div>
								<i class="icon-minus"></i> = <?php echo Text::_('COM_SOCIALADS_ADS_NO_ADORDER_EXISTS'); ?>
							</div>
							<div>
								<i class="<?php echo $pending_icon; ?>" ></i> = <?php echo Text::_('COM_SOCIALADS_AD_PENDING'); ?>
							</div>
						</div>

						<div class="pull-left sa-legends-padding">
							<div>
								<i class="icon-ok"></i> = <?php echo Text::_('COM_SOCIALADS_ADS_COMPLETED') . ' / ' . Text::_('COM_SOCIALADS_ADS_APPROVED'); ?>
							</div>
							<div>
								<i class="icon-remove"></i> = <?php echo Text::_('COM_SOCIALADS_ADS_REFUND') . ' / ' . Text::_('COM_SOCIALADS_ADS_REJECTED'); ?>
							</div>
						</div>
						<div class="clearfix"></div>
					</div>
				</div>
			<?php
			endif;
			?>
			<input type="hidden" name="task" value="" />
			<input type="hidden" name="boxchecked" value="0" />

			<input type="hidden" id='hidid' name="ad_id" value="" />
			<input type="hidden" id='hidstat' name="status" value="" />
			<input type="hidden" id='hidzone' name="zone" value="" />
			<input type="hidden" id='reason' name="reason" value="" />

			<input type="hidden" name="filter_order" value="<?php echo $listOrder; ?>" />
			<input type="hidden" name="filter_order_Dir" value="<?php echo $listDirn; ?>" />

			<?php echo HTMLHelper::_('form.token'); ?>
		</div>
	</form>
</div>

<script type="text/javascript">
	saAdmin.initSaJs();
	var tjListOrderingColumn = "<?php echo $listOrder; ?>";
	Joomla.submitbutton = function(task) {saAdmin.ads.submitButtonAction(task);}
	techjoomla.jQuery(document).ready(function()
	{
		jQuery('.fromTodateFields .calenderChange').change( function () {
			document.adminForm.submit();
		});
	});
</script>
