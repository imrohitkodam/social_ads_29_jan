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

JHtml::addIncludePath(JPATH_COMPONENT.'/helpers/html');
HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('behavior.multiselect');

if (JVERSION < '4.0')
{
	HTMLHelper::_('formbehavior.chosen', 'select');
}

$user	= Factory::getUser();
$userId	= $user->get('id');
$listOrder	= $this->state->get('list.ordering');
$listDirn	= $this->state->get('list.direction');
$canOrder	= $user->authorise('core.edit.state', 'com_socialads');
$saveOrder	= $listOrder == 'a.ordering';
$input = Factory::getApplication()->input;
$adid = $input->get('adid');

if ($saveOrder)
{
	$saveOrderingUrl = 'index.php?option=com_socialads&task=ignores.saveOrderAjax&tmpl=component';
	HTMLHelper::_('sortablelist.sortable', 'List', 'adminForm', strtolower($listDirn), $saveOrderingUrl);
}
$sortFields = $this->getSortFields();
?>
<form action="<?php echo Route::_('index.php?option=com_socialads&view=ignores&tmpl=component&adid='. $adid); ?>" method="post" name="adminForm" id="adminForm">
	<div>
		<h3><?php echo Text::_('COM_SOCIALADS_ADS_IGNORED');?></h3>
		<div id="filter-bar" class="btn-toolbar col-md-12">
			<div class="filter-search btn-group pull-left float-start">
				<label for="filter_search" class="element-invisible"><?php echo Text::_('JSEARCH_FILTER');?></label>
				<input type="text" name="filter_search" id="filter_search" placeholder="<?php echo Text::_('JSEARCH_FILTER'); ?>" value="<?php echo $this->escape($this->state->get('filter.search')); ?>" title="<?php echo Text::_('JSEARCH_FILTER'); ?>" />
			</div>
			<div class="btn-group pull-left float-start">
				<button class="btn hasTooltip" type="submit" title="<?php echo Text::_('JSEARCH_FILTER_SUBMIT'); ?>"><i class="icon-search"></i></button>
				<button class="btn hasTooltip" id="clear-search-button" type="button" title="<?php echo Text::_('JSEARCH_FILTER_CLEAR'); ?>"><i class="icon-remove"></i></button>
			</div>
			<div class="btn-group pull-right float-end hidden-phone">
				<label for="directionTable" class="element-invisible"><?php echo Text::_('JFIELD_ORDERING_DESC');?></label>
				<select name="directionTable" id="directionTable" class="input-medium form-select" onchange="Joomla.orderTable()">
					<option value=""><?php echo Text::_('JFIELD_ORDERING_DESC');?></option>
					<option value="asc" <?php if ($listDirn == 'asc') echo 'selected="selected"'; ?>><?php echo Text::_('JGLOBAL_ORDER_ASCENDING');?></option>
					<option value="desc" <?php if ($listDirn == 'desc') echo 'selected="selected"'; ?>><?php echo Text::_('JGLOBAL_ORDER_DESCENDING');?></option>
				</select>
			</div>
			<div class="btn-group pull-right float-end">
				<label for="sortTable" class="element-invisible"><?php echo Text::_('JGLOBAL_SORT_BY');?></label>
				<select name="sortTable" id="sortTable" class="input-medium form-select" onchange="Joomla.orderTable()">
					<option value=""><?php echo Text::_('JGLOBAL_SORT_BY');?></option>
					<?php echo HTMLHelper::_('select.options', $sortFields, 'value', 'text', $listOrder);?>
				</select>
			</div>

			<div class="btn-group pull-right float-end hidden-phone">
				<label for="limit" class="element-invisible"><?php echo Text::_('JFIELD_PLG_SEARCH_SEARCHLIMIT_DESC');?></label>
				<?php echo $this->pagination->getLimitBox(); ?>
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
			<table class="table table-striped" id="List">
				<thead>
					<tr>
						<?php
						if (isset($this->items[0]->ordering)): ?>
							<th width="1%" class="nowrap center hidden-phone">
								<?php echo HTMLHelper::_('grid.sort', '<i class="icon-menu-2"></i>', 'a.ordering', $listDirn, $listOrder, null, 'asc', 'JGRID_HEADING_ORDERING'); ?>
							</th>
						<?php
						endif; ?>
						<th width="1%" class="hidden-phone">
							<input type="checkbox" name="checkall-toggle" value="" title="<?php echo Text::_('JGLOBAL_CHECK_ALL'); ?>" onclick="Joomla.checkAll(this)" />
						</th>
						<?php
						if (isset($this->items[0]->state)): ?>
							<th width="1%" class="nowrap center">
								<?php echo HTMLHelper::_('grid.sort', 'JSTATUS', 'a.state', $listDirn, $listOrder); ?>
							</th>
						<?php
						endif; ?>
						<th class='left'>
							<?php echo HTMLHelper::_('grid.sort',  'COM_SOCIALADS_IGNORES_AD_FEEDBACK', 'a.ad_feedback', $listDirn, $listOrder); ?>
						</th>
						<th class='left'>
							<?php echo HTMLHelper::_('grid.sort',  'COM_SOCIALADS_IGNORES_IDATE', 'a.idate', $listDirn, $listOrder); ?>
						</th>
						<th class='left'>
							<?php echo HTMLHelper::_('grid.sort',  'COM_SOCIALADS_IGNORES_IGNORED_BY', 'u.username', $listDirn, $listOrder); ?>
						</th>
					</tr>
				</thead>
				<tfoot>
					<?php
					if (isset($this->items[0]))
					{
						$colspan = count(get_object_vars($this->items[0]));
					}
					else
					{
						$colspan = 10;
					}
					?>
					<tr>
						<td colspan="<?php echo $colspan ?>">
							<?php echo $this->pagination->getListFooter(); ?>
						</td>
					</tr>
				</tfoot>
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
								<td class="order nowrap center hidden-phone">
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
								<td class="center">
									<?php echo HTMLHelper::_('jgrid.published', $item->state, $i, 'ignores.', $canChange, 'cb'); ?>
								</td>
							<?php
							endif; ?>
							<td>
								<?php echo $item->ad_feedback; ?>
							</td>
							<td>
								<?php echo $item->idate; ?>
							</td>
							<td class="">
								<?php echo $item->ignored_by; ?>
							</td>
						</tr>
					<?php
					endforeach; ?>
				</tbody>
			</table>
		<?php
		endif; ?>
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

