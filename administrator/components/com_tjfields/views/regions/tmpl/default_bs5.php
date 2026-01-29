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
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Language\Text;

JHtml::addIncludePath(JPATH_COMPONENT . '/helpers/html');

// Load lang file for regions
$lang = Factory::getLanguage();
$lang->load('tjgeo.regions', JPATH_SITE, null, false, true);

$user      = Factory::getUser();
$userId    = $user->get('id');
$listOrder = $this->state->get('list.ordering');
$listDirn  = $this->state->get('list.direction');
$canOrder  = $user->authorise('core.edit.state', 'com_tjfields');
$saveOrder = $listOrder == 'a.ordering';

// Allow adding non select list filters
if (! empty($this->extra_sidebar))
{
	$this->sidebar .= $this->extra_sidebar;
}
?>

<form action="<?php echo Route::_('index.php?option=com_tjfields&view=regions&client=' . $this->input->get('client', '', 'STRING')); ?>" method="post" name="adminForm" id="adminForm">
	<div class="<?php echo TJFIELDS_WRAPPER_CLASS;?> tj-regions j-sidebar-container" id="j-sidebar-container">
		<?php
		echo LayoutHelper::render('joomla.searchtools.default', array('view' => $this));

		if (empty($this->items))
		{?>
			<div class="clearfix">&nbsp;</div>
			<div class="alert alert-no-items">
				<?php echo Text::_('COM_TJFIELDS_NO_MATCHING_RESULTS'); ?>
			</div>
			<?php
		}
		else
		{ ?>
			<table class="table" id="regionList">
				<thead>
					<tr>
						<th class="w-1 text-center">
							<?php echo HTMLHelper::_('grid.checkall'); ?>
						</th>
						<?php if (isset($this->items[0]->state)): ?>
							<th width="1%" class="nowrap center">
								<?php echo HTMLHelper::_('grid.sort', 'JSTATUS', 'state', $listDirn, $listOrder); ?>
							</th>
						<?php endif; ?>
						<th class='left'>
							<?php echo HTMLHelper::_('grid.sort', 'COM_TJFIELDS_REGIONS_REGION', 'a.region', $listDirn, $listOrder); ?>
						</th>
						<th class="left">
							<?php echo HTMLHelper::_('grid.sort', 'COM_TJFIELDS_REGIONS_COUNTRY', 'c.country', $listDirn, $listOrder); ?>
						</th>
						<th class="center hidden-phone">
							<?php echo HTMLHelper::_('grid.sort', 'COM_TJFIELDS_REGIONS_REGION_3_CODE', 'a.region_3_code', $listDirn, $listOrder); ?>
						</th>
						<th class="center hidden-phone">
							<?php echo HTMLHelper::_('grid.sort', 'COM_TJFIELDS_REGIONS_REGION_CODE', 'a.region_code', $listDirn, $listOrder); ?>
						</th>
						<th class='left hidden-phone'>
							<?php echo HTMLHelper::_('grid.sort', 'COM_TJFIELDS_REGIONS_REGION_JTEXT', 'a.region_jtext', $listDirn, $listOrder); ?>
						</th>
						<?php if (isset($this->items[0]->id)): ?>
							<th width="1%" class="nowrap center hidden-phone">
								<?php echo HTMLHelper::_('grid.sort', 'JGRID_HEADING_ID', 'a.id', $listDirn, $listOrder); ?>
							</th>
						<?php endif; ?>
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
							<td class="center hidden-phone">
								<?php echo HTMLHelper::_('grid.id', $i, $item->id); ?>
							</td>
							<?php if (isset($this->items[0]->state)): ?>
								<td class="center">
									<?php echo HTMLHelper::_('jgrid.published', $item->state, $i, 'regions.', $canChange, 'cb'); ?>
								</td>
							<?php endif; ?>
							<td>
							<?php
								if (isset($item->checked_out) && $item->checked_out)
								{
									echo HTMLHelper::_('jgrid.checkedout', $i, $item->editor, $item->checked_out_time, 'regions.', $canCheckin); 
								}

								if ($canEdit)
								{
									?>
									<a href="<?php echo Route::_('index.php?option=com_tjfields&task=region.edit&id=' . (int) $item->id . '&client=' . $this->input->get('client', '', 'STRING')); ?>">
										<?php echo $this->escape($item->region); ?>
									</a>
								<?php 
								}
								else
								{
									echo $this->escape($item->region);
								} ?>
							</td>
							<td class="left">
								<?php echo $this->escape($item->country); ?>
							</td>
							<td class="center hidden-phone">
								<?php echo $item->region_3_code; ?>
							</td>
							<td class="center hidden-phone">
								<?php echo $item->region_code; ?>
							</td>
							<td class="left hidden-phone">
								<?php
								if ($lang->hasKey(strtoupper($item->region_jtext)))
								{
									echo Text::_($item->region_jtext);
								}
								elseif ($item->region_jtext !== '')
								{
									echo "<span class='text text-warning'>" . Text::_('COM_TJFIELDS_MISSING_LANG_CONSTANT') . "</span>";
								}
								?>
							</td>
							<?php
							if (isset($this->items[0]->id))
							{ ?>
								<td class="center hidden-phone">
									<?php echo (int) $item->id; ?>
								</td>
							<?php 
							} ?>
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
		</div>
	</div>
</form>
