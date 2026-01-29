<?php
/**
 * @version    SVN: <svn_id>
 * @package    Tjfields
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2015 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

// No direct access
defined('_JEXEC') or die();

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;

JHtml::addIncludePath(JPATH_COMPONENT . '/helpers/html');
Factory::getApplication()->getDocument()->getWebAssetManager()->useStyle('searchtools')->useScript('searchtools');

// Load lang file for countries
$lang = Factory::getLanguage();
$lang->load('tjgeo.countries', JPATH_SITE, null, false, true);

$user      = Factory::getUser();
$userId    = $user->get('id');
$listOrder = $this->state->get('list.ordering');
$listDirn  = $this->state->get('list.direction');
$canOrder  = $user->authorise('core.edit.state', 'com_tjfields');
$saveOrder = $listOrder == 'a.ordering';

if ($saveOrder)
{
	$saveOrderingUrl = 'index.php?option=com_tjfields&task=countries.saveOrderAjax&tmpl=component';
	HTMLHelper::_('sortablelist.sortable', 'countryList', 'adminForm', strtolower($listDirn), $saveOrderingUrl);
}

$sortFields = $this->getSortFields();
?>

<script type="text/javascript">
	Joomla.orderTable = function()
	{
		table = document.getElementById("sortTable");
		direction = document.getElementById("directionTable");
		order = table.options[table.selectedIndex].value;

		if (order !== '<?php echo $listOrder; ?>')
		{
			dirn = 'asc';
		}
		else
		{
			dirn = direction.options[direction.selectedIndex].value;
		}

		Joomla.tableOrdering(order, dirn, '');
	}
</script>

<?php
if (! empty($this->extra_sidebar))
{
	$this->sidebar .= $this->extra_sidebar;
}
?>
<div class="<?php echo TJFIELDS_WRAPPER_CLASS;?> tj-countries j-sidebar-container" id="j-sidebar-container">
	<form action="<?php echo Route::_('index.php?option=com_tjfields&view=countries&client=' . $this->input->get('client', '', 'STRING')); ?>" method="post" name="adminForm" id="adminForm">
		<div class="col-md-12">
			<div class="js-stools" role="search">
				<div class="js-stools-container-bar">
					<div id="filter-bar" class="btn-toolbar">
						<div class="js-stools-container-selector filter-search btn-group float-start col-12 col-sm-12 col-md-2 col-lg-4">
							<input
								type="text"
								name="filter_search"
								id="filter_search"
								placeholder="<?php echo Text::_('COM_TJFIELDS_FILTER_SEARCH_DESC_COUNTRIES'); ?>"
								value="<?php echo $this->escape($this->state->get('filter.search')); ?>"
								class="hasTooltip form-control"
								title="<?php echo Text::_('COM_TJFIELDS_FILTER_SEARCH_DESC_COUNTRIES'); ?>" />
							<button
								type="submit"
								class="btn btn-primary hasTooltip"
								title="<?php echo Text::_('JSEARCH_FILTER_SUBMIT'); ?>">
								<i class="icon-search"></i>
							</button>
							<button
								type="button"
								class="btn btn-primary hasTooltip"
								title="<?php echo Text::_('JSEARCH_FILTER_CLEAR'); ?>"
								onclick="document.getElementById('filter_search').value='';this.form.submit();">
								<i class="icon-remove"></i>
							</button>
						</div>
						<div class="js-stools-container-selector btn-group float-end col-12 col-sm-12 col-md-2 hidden-phone hidden-tablet">
							<label for="directionTable" class="element-invisible">
								<?php echo Text::_('JFIELD_ORDERING_DESC'); ?>
							</label>
							<select name="directionTable" id="directionTable" class="form-select" onchange="Joomla.orderTable()">
								<option value=""><?php echo Text::_('JFIELD_ORDERING_DESC'); ?></option>
								<option value="asc"
									<?php
										if ($listDirn == 'asc')
										{
											echo 'selected="selected"';
										}
									?>>
										<?php echo Text::_('JGLOBAL_ORDER_ASCENDING'); ?>
								</option>
								<option value="desc"
									<?php
									if ($listDirn == 'desc')
									{
										echo 'selected="selected"';
									}
									?>>
										<?php echo Text::_('JGLOBAL_ORDER_DESCENDING'); ?>
								</option>
							</select>
						</div>
						<div class="js-stools-container-selector btn-group float-end col-12 col-sm-12 col-md-2 hidden-phone hidden-tablet">
							<label for="sortTable" class="element-invisible">
								<?php echo Text::_('JGLOBAL_SORT_BY'); ?>
							</label>
							<select name="sortTable" id="sortTable" class="form-select" onchange="Joomla.orderTable()">
								<option value=""><?php echo Text::_('JGLOBAL_SORT_BY'); ?></option>
								<?php echo HTMLHelper::_('select.options', $sortFields, 'value', 'text', $listOrder); ?>
							</select>
						</div>
						<div class="js-stools-container-selector btn-group float-end col-12 col-sm-12 col-md-2 hidden-phone">
							<?php echo HTMLHelper::_('select.genericlist', $this->publish_states, "filter_published", 'class="form-select" size="1" onchange="document.adminForm.submit();" name="filter_published"', "value", "text", $this->state->get('filter.state'));?>
						</div>
						<div class="js-stools-container-selector btn-group float-end col-12 col-sm-12 col-md-2 col-lg-1 hidden-phone">
							<label for="limit" class="element-invisible"><?php echo Text::_('JFIELD_PLG_SEARCH_SEARCHLIMIT_DESC'); ?></label>
							<?php echo $this->pagination->getLimitBox(); ?>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php
		if (empty($this->items))
		{?>
			<div class="clearfix">&nbsp;</div>
			<div class="alert alert-no-items mt-3">
				<?php echo Text::_('COM_TJFIELDS_NO_MATCHING_RESULTS'); ?>
			</div>
		<?php
		}
		else
		{?>
			<table class="table mt-3" id="countryList">
				<thead>
					<tr>
						<?php
						if (isset($this->items[0]->ordering))
						{ ?>
							<th width="1%" class="nowrap center hidden-phone">
								<?php echo HTMLHelper::_('grid.sort', '<i class="icon-menu-2"></i>', 'a.ordering', $listDirn, $listOrder, null, 'asc', 'JGRID_HEADING_ORDERING');?>
							</th>
						<?php 
						}?>
						<th class="w-1 text-center">
							<?php echo HTMLHelper::_('grid.checkall'); ?>
						</th>
						<?php if (isset($this->items[0]->state)): ?>
							<th width="1%" class="nowrap center">
								<?php echo HTMLHelper::_('grid.sort', 'JSTATUS', 'state', $listDirn, $listOrder); ?>
							</th>
						<?php endif; ?>
						<th class='left'>
							<?php echo HTMLHelper::_('grid.sort', 'COM_TJFIELDS_COUNTRIES_COUNTRY', 'a.country', $listDirn, $listOrder); ?>
						</th>
						<th class="center hidden-phone">
							<?php echo HTMLHelper::_('grid.sort', 'COM_TJFIELDS_COUNTRIES_COUNTRY_3_CODE', 'a.country_3_code', $listDirn, $listOrder); ?>
						</th>
						<th class="center hidden-phone">
							<?php echo HTMLHelper::_('grid.sort', 'COM_TJFIELDS_COUNTRIES_COUNTRY_CODE', 'a.country_code', $listDirn, $listOrder); ?>
						</th>
						<th class='left hidden-phone'>
							<?php echo HTMLHelper::_('grid.sort', 'COM_TJFIELDS_COUNTRIES_COUNTRY_JTEXT', 'a.country_jtext', $listDirn, $listOrder); ?>
						</th>
						<?php
						if (isset($this->items[0]->id))
						{ ?>
							<th width="1%" class="nowrap center hidden-phone">
								<?php echo HTMLHelper::_('grid.sort', 'JGRID_HEADING_ID', 'a.id', $listDirn, $listOrder); ?>
							</th>
						<?php 
						} ?>
					</tr>
				</thead>
				<tbody>
					<?php
					foreach ($this->items as $i => $item)
					{
						$ordering   = ($listOrder == 'a.ordering');
						$canCreate  = $user->authorise('core.create', 'com_tjfields');
						$canEdit    = $user->authorise('core.edit', 'com_tjfields');
						$canCheckin = $user->authorise('core.manage', 'com_tjfields');
						$canChange  = $user->authorise('core.edit.state', 'com_tjfields');
					?>
						<tr class="row<?php echo $i % 2; ?>">
							<?php if (isset($this->items[0]->ordering)): ?>
								<td class="order nowrap center hidden-phone">
									<?php
									if ($canChange)
									{
										$disabledLabel    = (!$saveOrder) ? Text::_('JORDERINGDISABLED') : '';
										$disableClassName = (!$saveOrder) ? 'inactive tip-top' : '';
									?>
										<span class="sortable-handler hasTooltip <?php echo $disableClassName; ?>" title="<?php echo $disabledLabel ?>">
											<i class="icon-menu"></i>
										</span>

										<input
											type="text"
											style="display: none"
											name="order[]"
											size="5" value="<?php echo $item->ordering; ?>"
											class="width-20 text-area-order " />
									<?php 
									}
									else
									{ ?>
										<span class="sortable-handler inactive">
											<i class="icon-menu"></i>
										</span>
									<?php 
									} ?>
								</td>
							<?php endif; ?>
							<td class="center hidden-phone">
								<?php echo HTMLHelper::_('grid.id', $i, $item->id); ?>
							</td>
							<?php
							if (isset($this->items[0]->state))
							{
								?>
								<td class="center">
									<?php echo HTMLHelper::_('jgrid.published', $item->state, $i, 'countries.', $canChange, 'cb'); ?>
								</td>
							<?php 
							} ?>
							<td>
								<?php
								if (isset($item->checked_out) && $item->checked_out)
								{
									echo HTMLHelper::_('jgrid.checkedout', $i, $item->editor, $item->checked_out_time, 'countries.', $canCheckin); 
								}

								if ($canEdit)
								{ ?>
									<a href="<?php echo Route::_('index.php?option=com_tjfields&task=country.edit&id=' . (int) $item->id . '&client=' . $this->input->get('client', '', 'STRING')); ?>">
										<?php echo $this->escape($item->country); ?>
									</a>
								<?php
								}
								else 
								{ ?>
									<?php echo $this->escape($item->country);
								} ?>
							</td>
							<td class="center hidden-phone">
								<?php echo $item->country_3_code; ?>
							</td>
							<td class="center hidden-phone">
								<?php echo $item->country_code; ?>
							</td>
							<td class="left hidden-phone">
								<?php
								if ($lang->hasKey(strtoupper($item->country_jtext)))
								{
									echo Text::_($item->country_jtext);
								}
								else if ($item->country_jtext !== '')
								{
									echo "<span class='text text-warning'>" . Text::_('COM_TJFIELDS_MISSING_LANG_CONSTANT') . "</span>";
								}
								?>
							</td>
							<?php if (isset($this->items[0]->id)): ?>
								<td class="center hidden-phone">
									<?php echo (int) $item->id; ?>
								</td>
							<?php endif; ?>
						</tr>
					<?php 
					} ?>
				</tbody>
			</table>
			<?php echo $this->pagination->getListFooter();
		} ?>

		<input type="hidden" name="task" value="" />
		<input type="hidden" name="boxchecked" value="0" />
		<input type="hidden" name="filter_order" value="<?php echo $listOrder; ?>" />
		<input type="hidden" name="filter_order_Dir" value="<?php echo $listDirn; ?>" />
		<?php echo HTMLHelper::_('form.token'); ?>
	</form>
</div>
