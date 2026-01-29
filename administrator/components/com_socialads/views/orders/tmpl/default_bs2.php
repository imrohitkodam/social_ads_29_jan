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
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;

JHtml::addIncludePath(JPATH_COMPONENT . '/helpers/html');

HTMLHelper::_('formbehavior.chosen', 'select');

$user	= Factory::getUser();
$userId	= $user->get('id');
$listOrder	= $this->state->get('list.ordering');
$listDirn	= $this->state->get('list.direction');
$canOrder	= $user->authorise('core.edit.state', 'com_socialads');
$saveOrder	= $listOrder == 'a.ordering';
$model 		= $this->getModel('orders');

if ($saveOrder)
{
	$saveOrderingUrl = 'index.php?option=com_socialads&task=orders.saveOrderAjax&tmpl=component';
	HTMLHelper::_('sortablelist.sortable', 'List', 'adminForm', strtolower($listDirn), $saveOrderingUrl);
}

$sortFields = $this->getSortFields();
$totalamount = 0;

if (!empty($this->extra_sidebar))
{
	$this->sidebar .= $this->extra_sidebar;
}
?>
<div class="<?php echo SA_WRAPPER_CLASS;?> tjBs2 sa-ad-order">
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
	endif;?>
		<?php
			// Taxation is diabled msg
			if ($this->params->get('payment_mode') == 'pay_per_ad_mode')
			{
				?>
				<div class="alert alert-error">
					<?php echo Text::_('COM_SOCIALADS_U_HV_CURRENTLY_USING_PAY_PER_MODE_HELP_MSG'); ?>
				</div>
				<?php
				return false;
			}
		?>
			<form action="<?php echo Route::_('index.php?option=com_socialads&view=adorders'); ?>" method="post" name="adminForm" id="adminForm">
				<div id="filter-bar" class="btn-toolbar">
					<div class="form-inline">
						<div class="filter-search input-group pull-left span6">
							<input type="text" name="filter_search" id="filter_search" placeholder="<?php echo Text::_('COM_SOCIALADS_AD_ORDERS_FILTER_SEARCH'); ?>" value="<?php echo $this->escape($this->state->get('filter_search')); ?>" title="<?php echo Text::_('COM_SOCIALADS_AD_ORDERS_FILTER_SEARCH'); ?>" />
							<button class="btn hasTooltip" type="submit" title="<?php echo Text::_('JSEARCH_FILTER_SUBMIT'); ?>">
								<i class="icon-search"></i>
							</button>
							<button class="btn hasTooltip" id="clear-search-button" type="button" title="<?php echo Text::_('JSEARCH_FILTER_CLEAR'); ?>">
								<i class="icon-remove"></i>
							</button>
						</div>
						<?php
						if (JVERSION >= '3.0') : ?>
							<div class="btn-group sa-ml-5 mt-sm-2 span2 pull-right limitboxdisplay">
								<label for="limit" class="element-invisible">
									<?php echo Text::_('JFIELD_PLG_SEARCH_SEARCHLIMIT_DESC');?>
								</label>
								<?php echo $this->pagination->getLimitBox(); ?>
							</div>
							<div class="btn-group sa-ml-5 mt-sm-2 span2 pull-right">
								<?php
								$payment_status = $this->state->get('filter.status');
								echo HTMLHelper::_('select.genericlist', $this->ostatus, "filter.status", 'class="sa-width-100" size="1" onchange="document.adminForm.submit();" name="filter_status"', "value", "text", $this->state->get('filter.status')); ?>
							</div>
							<div class="btn-group sa-ml-5 mt-sm-2 span2 pull-right">
								<?php echo HTMLHelper::_('select.genericlist', $this->gatewayoptions, "filter_gatewaylist", 'class="ad-status inputbox sa-width-100" size="1" onchange="document.adminForm.submit();" name="gatewaylist"', "value", "text", $this->state->get('filter.gatewaylist')); ?>
							</div>
						<?php
						endif; ?>
					</div>
				</div>
				<div class="clearfix mt-sm-2"> </div>
				<?php
				if (empty($this->items)) : ?>
					<div class="clearfix">&nbsp;</div>
					<div class="alert alert-no-items">
						<?php echo Text::_('COM_SOCIALADS_FILTER_SEARCH_NOT_FOUND'); ?>
					</div>
				<?php
				else : ?>
					<div id='no-more-tables' class="mt-sm-2">
						<table class="table table-striped" id="List">
							<thead>
								<tr>
									<?php
									if (isset($this->items[0]->ordering)): ?>
										<th class="tj-width-20 nowrap hidden-phone">
											<?php echo HTMLHelper::_('grid.sort', '<i class="icon-menu-2"></i>', 'a.ordering', $listDirn, $listOrder, null, 'asc', 'JGRID_HEADING_ORDERING'); ?>
										</th>
									<?php
									endif; ?>
									<?php
									if (isset($this->items[0]->id)): ?>
										<th class="tj-width-20 nowrap hidden-phone">
											<?php echo HTMLHelper::_('grid.sort', 'JGRID_HEADING_ID', 'a.prefix_oid', $listDirn, $listOrder); ?>
										</th>
									<?php
									endif; ?>
									<th class="tj-width-10">
										<?php echo HTMLHelper::_('grid.sort',  'COM_SOCIALADS_ADORDERS_USERNAME', 'u.username', $listDirn, $listOrder); ?>
									</th>
									<th class='tj-width-15'>
										<?php echo HTMLHelper::_('grid.sort',  'COM_SOCIALADS_ORDERS_CDATE', 'a.cdate', $listDirn, $listOrder); ?>
									</th>
									<th class="tj-width-10">
										<?php echo HTMLHelper::_('grid.sort',  'COM_SOCIALADS_ORDERS_STATUS', 'a.status', $listDirn, $listOrder); ?>
									</th>
									<th class="tj-width-10">
										<?php echo HTMLHelper::_('grid.sort',  'COM_SOCIALADS_ORDERS_PROCESSOR', 'a.processor', $listDirn, $listOrder); ?>
									</th>
									<th class="tj-width-10 sa-text-right">
										<?php echo HTMLHelper::_('grid.sort',  'COM_SOCIALADS_ORDERS_AD_AMOUNT', 'a.amount', $listDirn, $listOrder); ?>
									</th>
								</tr>
						</thead>
					<tbody>
					<?php
						$k = 0;

						foreach ($this->items as $i => $item) :
							$ordering   = ($listOrder == 'a.ordering');
							$canCreate  = $user->authorise('core.create',		'com_socialads');
							$canEdit    = $user->authorise('core.edit',			'com_socialads');
							$canCheckin = $user->authorise('core.manage',		'com_socialads');
							$canChange  = $user->authorise('core.edit.state',	'com_socialads');
							$whichever  = '';

							 switch ($item->status)
							 {
									case 'C' :
										$whichever =  Text::_('COM_SOCIALADS_AD_CONFIRM');
										$row_color = "success";
									break;
									case 'RF' :
										$whichever = Text::_('COM_SOCIALADS_AD_REFUND') ;
										$row_color = "error";
									break;
									case 'E' :
										$whichever = Text::_('COM_SOCIALADS_AD_CANCEL') ;
										$row_color = "error";
									break;
									case 'P' :
										$whichever = Text::_('COM_SOCIALADS_AD_PENDING') ;
										$row_color = "warning";
									break;
							 } ?>
							<tr class="row<?php echo $i % 2; ?> <?php echo $row_color; ?>">
								<?php
								if (isset($this->items[0]->id)): ?>
									<td class="tj-width-20" data-title="<?php echo Text::_('JGRID_HEADING_ID');?>">
										<?php echo $item->prefix_oid ? $item->prefix_oid : $item->id; ?>
									</td>
								<?php
								endif; ?>
								<td class="tj-width-10" data-title="<?php echo Text::_('COM_SOCIALADS_ADORDERS_USERNAME');?>">
									<?php echo $item->username . ' (' . $item->email . ")"; ?>
								</td>
								<?php
								if (isset($this->items[0]->ordering)): ?>
									<td class="tj-width-10" class="order nowrap center hidden-phone" data-title="<?php echo Text::_('');?>">
										<?php
										if ($canChange) :
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
								<td class="tj-width-15" data-title="<?php echo Text::_('COM_SOCIALADS_ORDERS_CDATE');?>">
									<?php echo HTMLHelper::date($item->cdate, Text::_('COM_SOCIALADS_DATE_FORMAT_SHOW_AMPM'), true); ?>
								</td>
								<td class="tj-width-10" data-title="<?php echo Text::_('COM_SOCIALADS_ORDERS_STATUS');?>">
									<?php
										$validStatus =$model->getValidOrderStatus($item->status);

										if (!empty($item->processor))
										{
											echo HTMLHelper::_('select.genericlist',$validStatus,'pstatus'.$k,'class="pad_status input-medium"  onChange="saAdmin.orders.selectStatusOrder(' . $item->id . ',this);"',"value","text",$item->status);
										}
										else
										{
											echo $whichever ;
										} ?>
								</td>
								<td class="tj-width-10" data-title="<?php echo Text::_('COM_SOCIALADS_ORDERS_PROCESSOR');?>">
									<?php
									if ($item->processor)
									{
										echo $item->processor;
									}
									elseif ($item->amount == 0 && !empty($item->coupon))
									{
										echo Text::_('COM_SOCIALADS_ADORDERS_VIA_COUPON');
									}	?>
								</td>
								<td class="tj-width-10 sa-text-right" data-title="<?php echo Text::_('COM_SOCIALADS_ORDERS_AD_AMOUNT');?>">
									<?php echo SaCommonHelper::getFormattedPrice($item->amount, '', $this->params->get('decimals_count', 2)); ?>
									<?php $totalamount = $totalamount + $item->amount;?>
								</td>
							</tr>
						<?php
						endforeach; ?>
						<tr>
							<td colspan="4"></td>
							<td>
								<b><?php echo Text::_('COM_SOCIALADS_TOTAL'); ?></b>
							</td>
							<td>
								<b>
									<?php echo SaCommonHelper::getFormattedPrice($totalamount, '', $this->params->get('decimals_count', 2)); ?>
								</b>
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
			</div>
		</form>
	</div>
</div>
<script type="text/javascript">
	saAdmin.initSaJs();
	var tjListOrderingColumn = "<?php echo $listOrder; ?>";
</script>
