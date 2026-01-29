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

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Plugin\PluginHelper;

JHtml::addIncludePath(JPATH_COMPONENT . '/helpers/html');

HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('behavior.multiselect');

$listOrder  = $this->state->get('list.ordering');
$listDirn   = $this->state->get('list.direction');
//$sa_params = JComponentHelper::getParams('com_socialads');
?>
<div class="<?php echo SA_WRAPPER_CLASS;?>" id="sa-myorders">
	<?php
	$payment_mode = $this->params->get('payment_mode');

	if ($payment_mode == 'wallet_mode')
	{?>
		<div class="alert alert-warning alert-dismissible fade show" role="alert">
			<?php echo Text::_('COM_SOCIALADS_WALLET_NO_AUTH_SEE'); ?>
			<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
		</div>
		<?php
		return false;
	}?>
	<div class="page-header">
		<h1>
			<?php echo Text::_('COM_SOCIALADS_ORDERS_HEADING');?>
		</h1>
	</div>
	<!-- show message if no items found -->
	<form action="<?php echo Route::_('index.php?option=com_socialads&view=myorders&Itemid=' . $this->myordersItemid); ?>" method="post" name="adminForm" id="adminForm">
		<fieldset class="filters btn-toolbar clearfix mb-3">
			<div class="col-md-12 mt-2">
				<div class="btn-group ms-2 float-end hidden-phone">
					<label for="limit" class="element-invisible">
					</label>
					<?php echo $this->pagination->getLimitBox(); ?>
				</div>
				<div class="btn-group ms-2 float-end hidden-phone">
					<?php
					$payment_status = $this->state->get('filter.status');
					echo HTMLHelper::_('select.genericlist', $this->ostatus, "filter.status", 'class="input-medium form-select" size="1" onchange="document.adminForm.submit();" name="filter_status"', "value", "text", $this->state->get('filter.status')); ?>
				</div>				
			</div>
		</fieldset>
		<?php
		if (empty($this->items))
		{
		?>
			<div class="alert"><?php echo Text::_('COM_SOCIALADS_FILTER_SEARCH_NOT_FOUND');?></div>
		<?php
		}
		else
		{
		?>
			<div id="no-more-tables" class="table-responsive">
				<table class="table table-responsive table-striped table-bordered table-hover  table-light border" id="reportList">
					<thead class="text-break table-primary text-light text">
						<tr>
							<th class=''>
								<?php echo Text::_('COM_SOCIALADS_ORDERS_NO'); ?>
							</th>
							<?php
							if (isset($this->items[0]->id)): ?>
								<th width="1%" class="nowrap center hidden-phone">
									<?php echo HTMLHelper::_('grid.sort', 'COM_SOCIALADS_ORDERS_ORDER_ID', 'o.prefix_oid', $listDirn, $listOrder); ?>
								</th>
							<?php
							endif; ?>
							<th class=''>
								<?php echo HTMLHelper::_('grid.sort',  'COM_SOCIALADS_ORDERS_DATE', 'o.cdate', $listDirn, $listOrder); ?>
							</th>
							<th class=''>
								<?php echo HTMLHelper::_('grid.sort',  'COM_SOCIALADS_ORDERS_AD_TITLE', 'd.ad_title', $listDirn, $listOrder); ?>
							</th>
							<th class=''>
								<?php echo HTMLHelper::_('grid.sort',  'COM_SOCIALADS_ORDERS_TYPE', 'd.ad_payment_type', $listDirn, $listOrder); ?>
							</th>
							<th class=''>
								<?php echo HTMLHelper::_('grid.sort',  'COM_SOCIALADS_ORDERS_AMOUNT', 'o.amount', $listDirn, $listOrder); ?>
							</th>
							<th class=''>
								<?php echo HTMLHelper::_('grid.sort',  'COM_SOCIALADS_ORDERS_PAYMENT_METHOD', 'o.processor', $listDirn, $listOrder); ?>
							</th>
							<th class=''>
								<?php echo HTMLHelper::_('grid.sort',  'COM_SOCIALADS_ORDERS_STATUS', 'o.status', $listDirn, $listOrder); ?>
							</th>
						</tr>
					</thead>
					<tbody>
						<?php
						$id = 1;
						foreach ($this->items as $i => $item) : ?>
							<tr class="row<?php echo $i % 2; ?>">
								<td data-title="<?php echo Text::_("COM_SOCIALADS_ORDERS_NO"); ?>"><?php echo $id++;?></td>
								<?php
								if (isset($this->items[0]->id)): ?>
									<td class="center hidden-phone" data-title="<?php echo Text::_("COM_SOCIALADS_ORDERS_ORDER_ID"); ?>"><?php echo (!empty($item->prefix_oid)?$item->prefix_oid:$item->id); ?></td>
								<?php
								endif; ?>
								<td data-title="<?php echo Text::_("COM_SOCIALADS_ORDERS_DATE"); ?>"><?php echo htmlspecialchars($item->cdate, ENT_COMPAT, 'UTF-8'); ?></td>
								<td data-title="<?php echo Text::_("COM_SOCIALADS_ORDERS_AD_TITLE"); ?>">
									<?php echo htmlspecialchars($item->ad_title, ENT_COMPAT, 'UTF-8'); ?>
								</td>
								<td data-title="<?php echo Text::_("COM_SOCIALADS_ORDERS_TYPE"); ?>">
									<?php
									if ($item->ad_payment_type == 0)
									{
										echo Text::_('COM_SOCIALADS_ORDERS_TYPE_IMP');
									}
									elseif ($item->ad_payment_type == 1)
									{
										echo Text::_('COM_SOCIALADS_ORDERS_TYPE_CLICK');
									}
									else
									{
										echo Text::_('COM_SOCIALADS_ORDERS_TYPE_DAY');
									}?>
								</td>
								<td data-title="<?php echo Text::_("COM_SOCIALADS_ORDERS_AMOUNT"); ?>"><?php echo SaCommonHelper::getFormattedPrice($item->amount);?></td>
								<td data-title="<?php echo Text::_("COM_SOCIALADS_ORDERS_PAYMENT_METHOD"); ?>">
								<?php
									if ($item->processor)
									{
										$plugin = PluginHelper::getPlugin('payment', htmlspecialchars($item->processor, ENT_COMPAT, 'UTF-8'));

										if (!empty($plugin))
										{
											$pluginParams = json_decode($plugin->params);
											echo !empty ($pluginParams->plugin_name) ? htmlspecialchars($pluginParams->plugin_name, ENT_COMPAT, 'UTF-8') : htmlspecialchars($item->processor, ENT_COMPAT, 'UTF-8');
										}
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
								<?php
								switch ($item->status)
								 {
									case 'C' :
										$whichever = '<span class="label label-success">' . Text::_('COM_SOCIALADS_SA_CONFIRM') .' </span>';
									break;
									case 'RF' :
										$whichever = '<span class="label label-danger">' . Text::_('COM_SOCIALADS_SA_REFUND') . '</span>';
									break;
									case 'P' :
										$whichever = '<span class="label label-warning">' . Text::_('COM_SOCIALADS_SA_PENDIN'). '</span>';
									break;
									case 'E' :
										$whichever = '<span class="label label-warning">' . Text::_('COM_SOCIALADS_SA_REJECTED') . '</span>';
									break;
									default:
										$whichever = '<span class="label label-success">' . htmlspecialchars($item->status, ENT_COMPAT, 'UTF-8') . '</span>';
									break;
								 }?>
								<td data-title="<?php echo Text::_("COM_SOCIALADS_ORDERS_STATUS"); ?>"><?php echo $whichever; ?></td>
							</tr>
						<?php
						endforeach; ?>
					</tbody>
				</table>
			</div>
			<div class="float-end clearfix">
				<?php echo $this->pagination->getListFooter(); ?>
			</div>
			<div class="clearfix"></div>
		<?php
		}
		?>
		<input type="hidden" name="task" value=""/>
		<input type="hidden" name="filter_order" value="<?php echo htmlspecialchars($listOrder, ENT_COMPAT, 'UTF-8'); ?>"/>
		<input type="hidden" name="filter_order_Dir" value="<?php echo htmlspecialchars($listDirn, ENT_COMPAT, 'UTF-8'); ?>"/>
		<?php echo HTMLHelper::_('form.token'); ?>
	</form>
</div>
