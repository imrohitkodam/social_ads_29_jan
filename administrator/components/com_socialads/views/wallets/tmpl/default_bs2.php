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

HTMLHelper::_('bootstrap.renderModal', 'a.modal');
JHtml::addIncludePath(JPATH_COMPONENT.'/helpers/html');
HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('behavior.multiselect');
HTMLHelper::_('formbehavior.chosen', 'select');

$user	= Factory::getUser();
$userId	= $user->get('id');
$listOrder	= $this->state->get('list.ordering');
$listDirn	= $this->state->get('list.direction');
$canOrder	= $user->authorise('core.edit.state', 'com_socialads');
$saveOrder	= $listOrder == 'a.ordering';
if ($saveOrder)
{
	$saveOrderingUrl = 'index.php?option=com_socialads&task=adwallets.saveOrderAjax&tmpl=component';
	HTMLHelper::_('sortablelist.sortable', 'adwalletList', 'adminForm', strtolower($listDirn), $saveOrderingUrl);
}
$sortFields = $this->getSortFields();

if (!empty($this->extra_sidebar))
{
	$this->sidebar .= $this->extra_sidebar;
}

if(!empty($this->sidebar)): ?>
	<div id="j-sidebar-container" class="span2">
		<?php echo $this->sidebar; ?>
	</div>
	<div id="j-main-container" class="span10 tjBs2 wallets-listing-page">
<?php else : ?>
	<div id="j-main-container" class="tjBs2 wallets-listing-page">
<?php endif;?>
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
	<form action="<?php echo Route::_('index.php?option=com_socialads&view=wallets'); ?>" method="post" name="adminForm" id="adminForm">
	<div id="filter-bar" class="btn-toolbar">
			<div class="form-inline">
				<div class="filter-search input-group pull-left">
					<input type="text" name="filter_search" id="filter_search" placeholder="<?php echo Text::_('COM_SOCIALADS_ADWALETS_FILTER_SEARCH'); ?>" value="<?php echo $this->escape($this->state->get('filter.search')); ?>" title="<?php echo Text::_('COM_SOCIALADS_ADWALETS_FILTER_SEARCH'); ?>" />
					<button class="btn hasTooltip" type="submit" title="<?php echo Text::_('JSEARCH_FILTER_SUBMIT'); ?>"><i class="icon-search"></i></button>
					<button class="btn hasTooltip" id="clear-search-button" type="button" title="<?php echo Text::_('JSEARCH_FILTER_CLEAR'); ?>"><i class="icon-remove"></i></button>
				</div>
				<div class="btn-group span2 mt-sm-2 col-lg-1 pull-right limitboxdisplay">
					<label for="limit" class="element-invisible"><?php echo Text::_('JFIELD_PLG_SEARCH_SEARCHLIMIT_DESC');?></label>
					<?php echo $this->pagination->getLimitBox(); ?>
				</div>

				<div class="btn-group span2 mt-sm-2 pull-right ">
					<label for="directionTable" class="element-invisible"><?php echo Text::_('JFIELD_ORDERING_DESC');?></label>
					<select name="directionTable" id="directionTable" class="sa-width-100" onchange="Joomla.orderTable()">
						<option value=""><?php echo Text::_('JFIELD_ORDERING_DESC');?></option>
						<option value="asc" <?php if ($listDirn == 'asc') echo 'selected="selected"'; ?>><?php echo Text::_('JGLOBAL_ORDER_ASCENDING');?></option>
						<option value="desc" <?php if ($listDirn == 'desc') echo 'selected="selected"'; ?>><?php echo Text::_('JGLOBAL_ORDER_DESCENDING');?></option>
					</select>
				</div>

				<div class="btn-group span2 mt-sm-2 pull-right">
					<label for="sortTable" class="element-invisible"><?php echo Text::_('JGLOBAL_SORT_BY');?></label>
					<select name="sortTable" id="sortTable" class="sa-width-100" onchange="Joomla.orderTable()">
						<option value=""><?php echo Text::_('JGLOBAL_SORT_BY');?></option>
						<?php echo HTMLHelper::_('select.options', $sortFields, 'value', 'text', $listOrder);?>
					</select>
				</div>
				<!--
					<div class="btn-group pull-right hidden-phone">
					<?php
						//echo HTMLHelper::_('select.genericlist', $this->publish_states, "filter_published", 'class="sa-width-100" size="1" onchange="document.adminForm.submit();" name="filter_published"', "value", "text", $this->state->get('filter.state'));
						?>
					</div>
				-->
			</div>
		</div>
		<div class="clearfix"> </div>
		<?php if (empty($this->items)) : ?>
		<div class="clearfix">&nbsp;</div>
		<div class="alert alert-no-items">
			<?php echo Text::_('COM_SOCIALADS_FILTER_SEARCH_NOT_FOUND'); ?>
		</div>
		<?php
		else : ?>
		<div id = "no-more-tables" class="mt-sm-2">
			<table class="table table-striped" id="adwalletList">
				<thead>
					<tr>
					<?php if (isset($this->items[0]->ordering)): ?>
						<th width="1%" class="nowrap center hidden-phone">
							<?php echo HTMLHelper::_('grid.sort', '<i class="icon-menu-2"></i>', 'a.ordering', $listDirn, $listOrder, null, 'asc', 'JGRID_HEADING_ORDERING'); ?>
						</th>
					<?php endif; ?>

					<!--
						<th width="1%" class="hidden-phone">
							<input type="checkbox" name="checkall-toggle" value="" title="<?php // echo JText::_('JGLOBAL_CHECK_ALL'); ?>" onclick="Joomla.checkAll(this)" />
						</th>
						<?php // if (isset($this->items[0]->state)): ?>
						<th width="1%" class="nowrap center">
							<?php //echo HTMLHelper::_('grid.sort', 'JSTATUS', 'a.state', $listDirn, $listOrder); ?>
						</th>
						<?php //endif; ?>
					-->

					<th class='left'>
						<?php echo HTMLHelper::_('grid.sort',  'COM_SOCIALADS_ADWALETS_USERNAME', 'u.username', $listDirn, $listOrder); ?>
					</th>
					<th class='left'>
						<?php echo Text::_('COM_SOCIALADS_ADWALETS_DETAILS'); ?>
					</th>
					<th class="sa-text-right">
						<?php echo HTMLHelper::_('grid.sort',  Text::sprintf('COM_SOCIALADS_ADWALETS_SPENT', $this->params->get('currency', 'USD')), 'total_spent', $listDirn, $listOrder); ?>
					</th>
					<th class="sa-text-right">
					<?php echo HTMLHelper::_('grid.sort',  Text::sprintf('COM_SOCIALADS_ADWALETS_EARN', $this->params->get('currency', 'USD')), 'total_earn', $listDirn, $listOrder); ?>
					</th>
					<th class="sa-text-right"'>
					<?php echo HTMLHelper::_('grid.sort',  Text::sprintf('COM_SOCIALADS_ADWALETS_BALANCE', $this->params->get('currency', 'USD')), 'total_payment', $listDirn, $listOrder); ?>
					</th>
				</tr>
			</thead>
				<tfoot>
					<?php
						if(isset($this->items[0]))
						{
							$colspan = count(get_object_vars($this->items[0]));
						}
						else
						{
							$colspan = 10;
						}
					?>
				</tfoot>
				<tbody>
				<?php foreach ($this->items as $i => $item) :
					$ordering = ($listOrder == 'a.ordering');
					$canCreate = $user->authorise('core.create', 'com_socialads');
					$canEdit = $user->authorise('core.edit', 'com_socialads');
					$canCheckin = $user->authorise('core.manage', 'com_socialads');
					$canChange = $user->authorise('core.edit.state', 'com_socialads');
					?>
					<tr class="row<?php echo $i % 2; ?>">
						<?php if (isset($this->items[0]->ordering)): ?>

						<td class="order nowrap center hidden-phone">
						<?php if ($canChange) :
							$disableClassName = '';
							$disabledLabel = '';
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

					<td data-title="<?php echo Text::_('COM_SOCIALADS_ADWALETS_USERNAME'); ?>">
						<?php echo $item->created_by . " (" . $item->email . ")"; ?>
					</td>
					<td data-title="<?php echo Text::_('COM_SOCIALADS_ADWALETS_DETAILS'); ?>">
						<?php
							$link = Route::_('index.php?option=com_socialads&view=wallet&tmpl=component&layout=default&user='.$item->user_id);?>
							
							<?php
							echo HTMLHelper::_(
								'bootstrap.renderModal',
								'walletDetails' . $item->id . 'Modal',
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
							<a data-toggle="modal" data-target="#walletDetails<?php echo $item->id ?>Modal">
								<?php echo Text::_('COM_SOCIALADS_ADWALETS_VIEW_DETAILS');?>
							</a>
					</td>
					<td class="sa-text-right" data-title="<?php echo Text::_('COM_SOCIALADS_ADWALETS_SPENT'); ?>">
						<?php echo SaCommonHelper::getFormattedPrice($item->total_spent, '', $this->params->get('decimals_count', 2)); ?>
					</td>
					<td class="sa-text-right" data-title="<?php echo Text::_('COM_SOCIALADS_ADWALETS_EARN'); ?>">
						<?php echo SaCommonHelper::getFormattedPrice($item->total_payment, '', $this->params->get('decimals_count', 2)); ?>
					</td>
					<td class="sa-text-right" data-title="<?php echo Text::_('COM_SOCIALADS_ADWALETS_BALANCE'); ?>">
						<?php echo SaCommonHelper::getFormattedPrice($item->total_payment, '', $this->params->get('decimals_count', 2)); ?>
					</td>
				</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>

		<div>
			<?php echo $this->pagination->getListFooter(); ?>
		</div>
			<?php endif; ?>
		<input type="hidden" name="task" value="" />
		<input type="hidden" name="boxchecked" value="0" />
		<input type="hidden" name="filter_order" value="<?php echo $listOrder; ?>" />
		<input type="hidden" name="filter_order_Dir" value="<?php echo $listDirn; ?>" />
		<?php echo HTMLHelper::_('form.token'); ?>
	</div>
</form>

<script type="text/javascript">
	saAdmin.initSaJs();
	var tjListOrderingColumn = "<?php echo $listOrder; ?>";
</script>
