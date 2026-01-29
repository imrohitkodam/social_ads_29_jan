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

JHtml::addIncludePath(JPATH_COMPONENT.'/helpers/html');

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

if ($saveOrder)
{
	$saveOrderingUrl = 'index.php?option=com_socialads&task=coupons.saveOrderAjax&tmpl=component';
	HTMLHelper::_('sortablelist.sortable', 'couponList', 'adminForm', strtolower($listDirn), $saveOrderingUrl);
}
$sortFields = $this->getSortFields();

if (!empty($this->extra_sidebar))
{
	$this->sidebar .= $this->extra_sidebar;
}
?>
<div class="<?php echo SA_WRAPPER_CLASS;?> sa-coupons">
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
	<form action="<?php echo Route::_('index.php?option=com_socialads&view=coupons'); ?>" method="post" name="adminForm" id="adminForm">
		<div id="filter-bar" class="btn-toolbar">
			<div class="col-md-12 mt-2">
				<div class="row">
					<div class="col-md-6 col-sm-12">
						<div class="filter-search btn-group float-start">
							<input type="text" name="filter_search" id="filter_search" class="form-control" placeholder="<?php echo Text::_('COM_SOCIALADS_COUPONS_FILTER_SEARCH'); ?>" value="<?php echo $this->escape($this->state->get('filter.search')); ?>" title="<?php echo Text::_('COM_SOCIALADS_COUPONS_FILTER_SEARCH'); ?>" />
							<button class="btn hasTooltip btn-outline-secondary" type="submit" title="<?php echo Text::_('JSEARCH_FILTER_SUBMIT'); ?>"><i class="fa fa-search"></i></button>
							<button class="btn hasTooltip btn-outline-secondary" id="clear-search-button" type="button" title="<?php echo Text::_('JSEARCH_FILTER_CLEAR'); ?>"><i class="fa fa-remove"></i></button>
						</div>
					</div>
					<div class="col align-self-end">
						<div class="btn-group float-end hidden-phone ">
							<?php echo HTMLHelper::_('select.genericlist', $this->publish_states, "filter_published", 'class="input-medium form-select" size="1" onchange="document.adminForm.submit();" name="filter_published"', "value", "text", $this->state->get('filter.state'));?>
						</div>
					</div>
					<?php
					if (JVERSION >= '3.0') : ?>
						<div class="btn-group float-end col-12 col-sm-12 col-md-2 col-lg-1 hidden-phone">
							<label for="limit" class="element-invisible"><?php echo Text::_('JFIELD_PLG_SEARCH_SEARCHLIMIT_DESC');?></label>
							<?php echo $this->pagination->getLimitBox(); ?>
						</div>
					<?php endif; ?>			
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
			<div id = "no-more-tables">
				<table class="table table-responsive mt-2" id="couponList">
					<thead>
						<tr>
						<?php
						if (isset($this->items[0]->ordering)): ?>
							<th width="1%" class="nowrap text-center hidden-phone">
								<?php echo HTMLHelper::_('grid.sort', '<i class="icon-menu-2"></i>', 'a.ordering', $listDirn, $listOrder, null, 'asc', 'JGRID_HEADING_ORDERING'); ?>
							</th>
						<?php
						endif; ?>
							<th width="1%" class="hidden-phone">
								<input type="checkbox" name="checkall-toggle" value="" title="<?php echo Text::_('JGLOBAL_CHECK_ALL'); ?>" onclick="Joomla.checkAll(this)" />
							</th>
						<?php
						if (isset($this->items[0]->state)): ?>
							<th width="1%" class="nowrap text-center">
								<?php echo HTMLHelper::_('grid.sort', 'JSTATUS', 'a.state', $listDirn, $listOrder); ?>
							</th>
						<?php
						endif; ?>
						<th class="left">
							<?php echo HTMLHelper::_('grid.sort',  'COM_SOCIALADS_COUPONS_NAME', 'a.name', $listDirn, $listOrder); ?>
						</th>
						<th class="left">
							<?php echo HTMLHelper::_('grid.sort',  'COM_SOCIALADS_COUPONS_CODE', 'a.code', $listDirn, $listOrder); ?>
						</th>
						<th class="sa-text-right">
							<?php echo HTMLHelper::_('grid.sort',  'COM_SOCIALADS_COUPONS_VALUE', 'a.value', $listDirn, $listOrder); ?>
						</th>
						<th class="sa-text-right">
							<?php echo HTMLHelper::_('grid.sort',  'COM_SOCIALADS_COUPONS_MAX_USE', 'a.max_use', $listDirn, $listOrder); ?>
						</th>
						<th class="left">
							<?php echo HTMLHelper::_('grid.sort',  'COM_SOCIALADS_COUPONS_FROM_DATE', 'a.from_date', $listDirn, $listOrder); ?>
						</th>
						<th class="left">
							<?php echo HTMLHelper::_('grid.sort',  'COM_SOCIALADS_COUPONS_EXP_DATE', 'a.exp_date', $listDirn, $listOrder); ?>
						</th>
						<?php
						if (isset($this->items[0]->id)): ?>
							<th width="1%" class="nowrap text-center hidden-phone sa-text-right">
								<?php echo HTMLHelper::_('grid.sort', 'JGRID_HEADING_ID', 'a.id', $listDirn, $listOrder); ?>
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
							?>
							<tr class="row<?php echo $i % 2; ?>">
								<?php
								if (isset($this->items[0]->ordering)): ?>
									<td class="order nowrap text-center hidden-phone">
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
								<td class="hidden-phone">
									<?php echo HTMLHelper::_('grid.id', $i, $item->id); ?>
								</td>
								<?php
								if (isset($this->items[0]->state)): ?>
									<td class="text-center" data-title="<?php echo Text::_('JSTATUS');?>">
										<?php echo HTMLHelper::_('jgrid.published', $item->state, $i, 'coupons.', $canChange, 'cb'); ?>
									</td>
								<?php
								endif; ?>

								<td data-title="<?php echo Text::_('COM_SOCIALADS_COUPONS_NAME');?>">
									<?php
									if (isset($item->checked_out) && $item->checked_out) : ?>
										<?php echo HTMLHelper::_('jgrid.checkedout', $i, $item->editor, $item->checked_out_time, 'coupons.', $canCheckin); ?>
									<?php
									endif; ?>
									<?php
									if ($canEdit) : ?>
										<a href="<?php echo Route::_('index.php?option=com_socialads&task=coupon.edit&id='.(int) $item->id); ?>">
										<?php echo $this->escape($item->name); ?></a>
									<?php
									else : ?>
										<?php echo $this->escape($item->name); ?>
									<?php
									endif; ?>
								</td>
								<td data-title="<?php echo Text::_('COM_SOCIALADS_COUPONS_CODE');?>">
									<?php echo $item->code; ?>
								</td>
								<td data-title="<?php echo Text::_('COM_SOCIALADS_COUPONS_VALUE');?>" class="sa-text-right">
									<?php echo ($item->val_type == 1) ? $item->value . '%' : SaCommonHelper::getFormattedPrice($item->value, '', 2); ?>
								</td>
								<td data-title="<?php echo Text::_('COM_SOCIALADS_COUPONS_MAX_USE');?>" class="sa-text-right">
									<?php echo $item->max_use; ?>
								</td>
								<td data-title="<?php echo Text::_('COM_SOCIALADS_COUPONS_FROM_DATE');?>">
									<?php echo $item->from_date > 0 ? HTMLHelper::date($item->from_date, Text::_('COM_SOCIALADS_DATE_FORMAT_SHOW_AMPM'), true) : '-';?>
								</td>
								<td data-title="<?php echo Text::_('COM_SOCIALADS_COUPONS_EXP_DATE');?>">
									<?php echo $item->exp_date > 0 ? HTMLHelper::date($item->exp_date, Text::_('COM_SOCIALADS_DATE_FORMAT_SHOW_AMPM'), true) : '-';?>
								</td>
								<?php
								if (isset($this->items[0]->id)): ?>
									<td class="text-center hidden-phone sa-text-right">
										<?php echo (int) $item->id; ?>
									</td>
								<?php
								endif; ?>
							</tr>
						<?php
						endforeach; ?>
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
			<input type="hidden" name="filter_order" value="<?php echo $listOrder; ?>" />
			<input type="hidden" name="filter_order_Dir" value="<?php echo $listDirn; ?>" />
			<?php echo HTMLHelper::_('form.token'); ?>
		</div>
	</form>
</div>

<script type="text/javascript">
	saAdmin.initSaJs();
	var tjListOrderingColumn = "<?php echo $listOrder; ?>";
	Joomla.submitbutton = function(action){saAdmin.coupons.submitButtonAction(action);}
</script>
