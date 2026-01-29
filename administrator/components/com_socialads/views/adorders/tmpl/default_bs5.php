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

use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;

HTMLHelper::_('bootstrap.renderModal', 'a.modal');
JHtml::addIncludePath(JPATH_COMPONENT . '/helpers/html');

if (JVERSION < '4.0')
{
	HTMLHelper::_('formbehavior.chosen', 'select');
}

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

$user	= Factory::getUser();
$userId	= $user->get('id');
$listOrder	= $this->state->get('list.ordering');
$listDirn	= $this->state->get('list.direction');
$canOrder	= $user->authorise('core.edit.state', 'com_socialads');
$saveOrder	= $listOrder == 'a.ordering';
$model 		= $this->getModel('adorders');

if ($saveOrder)
{
	$saveOrderingUrl = 'index.php?option=com_socialads&task=adorders.saveOrderAjax&tmpl=component';
	HTMLHelper::_('sortablelist.sortable', 'List', 'adminForm', strtolower($listDirn), $saveOrderingUrl);
}

$sortFields = $this->getSortFields();
$totalamount = 0;

if (!empty($this->extra_sidebar))
{
	$this->sidebar .= $this->extra_sidebar;
}
?>
<div class="<?php echo SA_WRAPPER_CLASS;?> sa-ad-order">
		<div id="j-main-container" class="col-md-12">
			<?php
				// Taxation is diabled msg
				if ($this->params->get('payment_mode') == 'wallet_mode')
				{
					?>
					<div class="alert alert-error">
						<?php echo Text::_('COM_SOCIALADS_U_HV_CURRENTLY_USING_WALLET_MODE_HELP_MSG'); ?>
					</div>
					<?php
					return false;
				}
			?>
			<form action="<?php echo Route::_('index.php?option=com_socialads&view=adorders'); ?>" method="post" name="adminForm" id="adminForm">
				<div id="filter-bar" class="btn-toolbar">
					<div class="col-md-12 mt-2">
						<div class="filter-search btn-group float-start mb-2">
							<input type="text" name="filter_search" id="filter_search" class="form-control"
							placeholder="<?php echo Text::_('COM_SOCIALADS_AD_ORDERS_FILTER_SEARCH_PLACEHOLDER'); ?>"
							value="<?php echo $this->escape($this->state->get('filter.search')); ?>"
							title="<?php echo Text::_('COM_SOCIALADS_AD_ORDERS_FILTER_SEARCH'); ?>" />
						</div>

						<div class="btn-group float-start mb-2">
							<button class="btn hasTooltip btn-outline-secondary" type="submit" title="<?php echo Text::_('JSEARCH_FILTER_SUBMIT'); ?>">
								<i class="fa fa-search"></i>
							</button>
							<button class="btn hasTooltip btn-outline-secondary" id="clear-search-button" type="button" title="<?php echo Text::_('JSEARCH_FILTER_CLEAR'); ?>">
								<i class="fa fa-remove"></i>
							</button>
						</div>

						<div class="btn-group float-end hidden-phone mb-2 ms-2">
							<label for="limit" class="element-invisible">
								<?php echo Text::_('JFIELD_PLG_SEARCH_SEARCHLIMIT_DESC');?>
							</label>
							<?php echo $this->pagination->getLimitBox(); ?>
						</div>
						<div class="btn-group float-end hidden-phone mb-2 ms-2">
							<?php
								$payment_status = $this->state->get('filter.status');
								echo HTMLHelper::_('select.genericlist', $this->ostatus, "filter.status", 'class="form-select input-medium" size="1" onchange="document.adminForm.submit();" name="filter_status"', "value", "text", $this->state->get('filter.status'));
								?>
						</div>

						<div class="btn-group float-end hidden-phone mb-2 ms-2">
							<?php
								echo HTMLHelper::_('select.genericlist', $this->gatewayoptions, "filter_gatewaylist", 'class="form-select ad-status inputbox input-medium" size="1" onchange="document.adminForm.submit();" name="gatewaylist"', "value", "text", $this->state->get('filter.gatewaylist'));
							?>
						</div>
					</div>
				</div>

				<div class="clearfix"> </div>
				<?php
				if (empty($this->items)) : ?>
					<div class="clearfix">&nbsp;</div>
					<div class="alert alert-no-items">
						<?php echo Text::_('COM_SOCIALADS_FILTER_SEARCH_NOT_FOUND'); ?>
					</div>

				<?php
				else : ?>
					<div id='no-more-tables'>
						<table class="table table-responsive mt-3" id="List">
							<thead>
								<tr>
									<?php if (isset($this->items[0]->ordering)): ?>
										<th class="tj-width-1 nowrap center hidden-phone">
											<?php echo HTMLHelper::_('grid.sort', '<i class="icon-menu-2"></i>', 'a.ordering', $listDirn, $listOrder, null, 'asc', 'JGRID_HEADING_ORDERING'); ?>
										</th>
									<?php endif; ?>

									<th class="tj-width-1 hidden-phone">
										<input type="checkbox" name="checkall-toggle" value="" title="<?php echo Text::_('JGLOBAL_CHECK_ALL'); ?>" onclick="Joomla.checkAll(this)" />
									</th>

									<?php if (isset($this->items[0]->id)): ?>
										<th class="tj-width-1 nowrap center hidden-phone">
											<?php echo HTMLHelper::_('grid.sort', 'JGRID_HEADING_ID', 'd.ad_id', $listDirn, $listOrder); ?>
										</th>
									<?php endif; ?>
									<th class='tj-width-5'>
										<?php echo HTMLHelper::_('grid.sort',  'COM_SOCIALADS_ADORDERS_AD_ID', 'd.ad_id', $listDirn, $listOrder); ?>
									</th>
									<th class='tj-width-5'>
										<?php echo HTMLHelper::_('grid.sort',  'COM_SOCIALADS_ADORDERS_AD_TITLE', 'd.ad_title', $listDirn, $listOrder); ?>
									</th>
									<th class='tj-width-5'>
										<?php echo HTMLHelper::_('grid.sort',  'COM_SOCIALADS_ADORDERS_AD_CREDITS_QTY', 'p.ad_credits_qty', $listDirn, $listOrder); ?>
									</th>
									<th class='tj-width-5'>
										<?php echo HTMLHelper::_('grid.sort',  'COM_SOCIALADS_ADORDERS_CDATE', 'o.cdate', $listDirn, $listOrder); ?>
									</th>

									<th class='tj-width-5'>
										<?php echo HTMLHelper::_('grid.sort',  'COM_SOCIALADS_ADORDERS_STATUS', 'o.status', $listDirn, $listOrder); ?>
									</th>

									<th class='tj-width-5'>
										<?php echo HTMLHelper::_('grid.sort',  'COM_SOCIALADS_ADORDERS_AD_TYPE', 'd.ad_payment_type', $listDirn, $listOrder); ?>
									</th>

									<th class='tj-width-5'>
										<?php echo HTMLHelper::_('grid.sort',  'COM_SOCIALADS_ADORDERS_USERNAME', 'u.username', $listDirn, $listOrder); ?>
									</th>
									<th class='tj-width-5'>
										<?php echo HTMLHelper::_('grid.sort',  'COM_SOCIALADS_ADORDERS_PROCESSOR', 'o.processor', $listDirn, $listOrder); ?>
									</th>

									<th class='tj-width-5'>
										<?php echo HTMLHelper::_('grid.sort',  'COM_SOCIALADS_ADORDERS_AD_AMOUNT', 'o.amount', $listDirn, $listOrder); ?>
									</th>
								</tr>
							</thead>

							<tbody>
								<?php
								$k = 0;
								foreach ($this->items as $i => $item) :
									$ordering   = ($listOrder == 'a.ordering');
									$canCreate	= $user->authorise('core.create',		'com_socialads');
									$canEdit	= $user->authorise('core.edit',			'com_socialads');
									$canCheckin	= $user->authorise('core.manage',		'com_socialads');
									$canChange	= $user->authorise('core.edit.state',	'com_socialads');
								?>
								<?php
								$whichever = '';
								$row_color = '';

								 switch ($item->status)
								 {
										case 'C' :
											$whichever =  Text::_('COM_SOCIALADS_ADORDERS_AD_CONFIRM');
											$row_color = "success";
										break;
										case 'RF' :
											$whichever = Text::_('COM_SOCIALADS_ADORDERS_AD_REFUND');
											$row_color = "error";
										break;
										case 'P' :
											$whichever = Text::_('COM_SOCIALADS_ADORDERS_AD_PENDING');
											$row_color = "error";
										break;
										case 'E' :
											$whichever = Text::_('COM_SOCIALADS_ADORDERS_AD_CANCEL') ;
											$row_color = "error";
										break;
										default:
											$whichever = $item->status;
											break;
								 }
								?>
								<tr class="row<?php echo $i % 2; ?> <?php echo $row_color; ?>">
									<?php if (isset($this->items[0]->ordering)): ?>
										<td class="order nowrap center hidden-phone" data-title="<?php echo Text::_('');?>">
											<?php if ($canChange) :
													$disableClassName = '';
													$disabledLabel	  = '';

														if (!$saveOrder) :
															$disabledLabel    = Text::_('JORDERINGDISABLED');
															$disableClassName = 'inactive tip-top';
														endif; ?>

														<span class="sortable-handler hasTooltip <?php echo $disableClassName?>" title="<?php echo $disabledLabel?>">
															<i class="icon-menu"></i>
														</span>
														<input type="text" style="display:none" name="order[]" size="5" value="<?php echo $item->ordering;?>" class="width-20 text-area-order " />
											<?php else : ?>
													<span class="sortable-handler inactive" >
														<i class="icon-menu"></i>
													</span>
											<?php endif; ?>
										</td>
									<?php endif; ?>

									<td class="hidden-phone" data-title="<?php echo Text::_('id');?>">
										<?php echo HTMLHelper::_('grid.id', $i, $item->id); ?>
									</td>

									<?php if (isset($this->items[0]->id)): ?>
										<td data-title="<?php echo Text::_('JGRID_HEADING_ID');?>">
											<?php
											$link = Route::_('index.php?option=com_socialads&view=adorders&layout=details&id=' . $item->id .'&tmpl=component');
											
											if ($item->comment != "AUTO_GENERATED")
											{?>	
												<?php
												echo HTMLHelper::_(
													'bootstrap.renderModal',
													'orderDetails' . $item->id . 'Modal',
													array(
															'title'       => Text::_('COM_SOCIALADS_INVOICE_ORDER_DETAIL'),
															'backdrop'    => 'static',
															'url'         => $link,
															'height'      => '400px',
															'width'       => '800px',
															'bodyHeight'  => 70,
															'modalWidth'  => 80,
														)
												); ?>
												<a data-bs-toggle="modal" data-bs-target="#orderDetails<?php echo $item->id ?>Modal">
													<?php echo (!empty($item->prefix_oid)?$item->prefix_oid:$item->id); ?>
												</a>
											<?php
											}
											else
											{
												echo (!empty($item->prefix_oid)?$item->prefix_oid:$item->id);
											}
											?>
										</td>
									<?php endif; ?>
									<td data-title="<?php echo Text::_('COM_SOCIALADS_ADORDERS_AD_ID');?>">
										<?php echo $item->ad_id; ?>
									</td>
									<td data-title="<?php echo Text::_('COM_SOCIALADS_ADORDERS_AD_TITLE');?>">
										<?php echo htmlspecialchars($item->ad_title, ENT_COMPAT, 'UTF-8'); ?>
									</td>
									<td data-title="<?php echo Text::_('COM_SOCIALADS_ADORDERS_AD_CREDITS_QTY');?>">
									<?php
										if ($item->ad_payment_type == 2) { 
											echo $item->ad_credits_qty . " " . Text::_('COM_SOCIALADS_ADORDERS_DAYS');
										}
										else
											echo $item->ad_credits_qty; ?>
									</td>
									<td data-title="<?php echo Text::_('COM_SOCIALADS_ADORDERS_CDATE');?>">
										<?php echo HTMLHelper::date($item->cdate, Text::_('COM_SOCIALADS_DATE_FORMAT_SHOW_AMPM'), true); ?>
									</td>

									<td data-title="<?php echo Text::_('COM_SOCIALADS_ADORDERS_STATUS');?>">
										<?php
										$validStatus = $model->getValidOrderStatus($item->status);

										if (!empty($item->processor))
										{
											echo HTMLHelper::_('select.genericlist',$validStatus,'pstatus'.$k,'class="pad_status form-select"  onChange="saAdmin.adorders.selectStatusOrder(' . $item->id . ',this);"',"value","text",$item->status);
										}
										else
										{
											echo $whichever ;
										} ?>
									</td>

									<td data-title="<?php echo Text::_('COM_SOCIALADS_ADS_AD_TYPE_ALT_AD');?>">
										<?php
											if ($item->ad_payment_type == '')
											{
												echo "--";
											}
											elseif ($item->ad_payment_type == 0)
											{
												echo Text::_('COM_SOCIALADS_ADS_AD_TYPE_IMPRS');
											}
											elseif($item->ad_payment_type == 1)
											{
												echo Text::_('COM_SOCIALADS_ADS_AD_TYPE_CLICKS');
											}
											else
											{
												echo Text::_('COM_SOCIALADS_ADS_AD_TYPE_PERDATE');
											}
										?>
									</td>


									<td data-title="<?php echo Text::_('COM_SOCIALADS_ADORDERS_PROCESSOR');?>">
										<?php echo htmlspecialchars($item->username, ENT_COMPAT, 'UTF-8');?>
									</td>

									<td data-title="<?php echo Text::_('COM_SOCIALADS_ADORDERS_PROCESSOR');?>">
									<?php
									if ($item->processor)
									{
										echo $item->processor;
									}
									elseif ($item->amount == 0 && !empty($item->coupon) )
									{
										echo Text::_('COM_SOCIALADS_ADORDERS_VIA_COUPON');
									}
									elseif ($item->comment == "AUTO_GENERATED")
									{
										echo Text::_('COM_SOCIALADS_ADORDERS_VIA_MIGRATION');
									}?>
									</td>

									<td data-title="<?php echo Text::_('COM_SOCIALADS_ADORDERS_AD_AMOUNT');?>">
										<?php echo SaCommonHelper::getFormattedPrice($item->amount);?>
										<?php $totalamount=$totalamount+$item->amount;?>
									</td>
								</tr>
								<?php endforeach; ?>

								<tr>
									<td colspan="9"></td>

									<td>
										<b><?php echo Text::_('COM_SOCIALADS_TOTAL'); ?></b>
									</td>

									<td>
										<b><?php echo SaCommonHelper::getFormattedPrice($totalamount);?></b>
									</td>
								</tr>
							</tbody>
						</table>

						<div>
							<?php echo $this->pagination->getListFooter(); ?>
						</div>
					</div>
				<?php
				endif; ?>
				<input type="hidden" name="task" value="" />
				<input type="hidden" name="boxchecked" value="0" />
				<input type="hidden" id='hidid' name="id" value="" />
				<input type="hidden" id='hidstat' name="status" value="" />
				<input type="hidden" name="filter_order" value="<?php echo $listOrder; ?>" />
				<input type="hidden" name="filter_order_Dir" value="<?php echo $listDirn; ?>" />
				<?php echo HTMLHelper::_('form.token'); ?>
			</form>
		</div>
</div>

<script type="text/javascript">
	var tjListOrderingColumn = "<?php echo $listOrder; ?>";
	saAdmin.initSaJs();
</script>
