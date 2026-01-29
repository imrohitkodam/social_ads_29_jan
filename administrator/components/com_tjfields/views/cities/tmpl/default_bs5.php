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

// Load lang file for cities
$lang = Factory::getLanguage();
$lang->load('tjgeo.cities', JPATH_SITE, null, false, true);

$user = Factory::getUser();
$userId = $user->get('id');
$listOrder = $this->state->get('list.ordering');
$listDirn = $this->state->get('list.direction');
$canOrder = $user->authorise('core.edit.state', 'com_tjfields');
$saveOrder = $listOrder == 'a.ordering';

// Allow adding non select list filters
if (! empty($this->extra_sidebar))
{
	$this->sidebar .= $this->extra_sidebar;
}
?>

<div class="<?php echo TJFIELDS_WRAPPER_CLASS;?> tj-cities j-main-container" id="j-main-container">
	<form
		action="<?php echo Route::_('index.php?option=com_tjfields&view=cities&client=' . $this->input->get('client', '', 'STRING')); ?>"
		method="post" name="adminForm" id="adminForm">
		<?php echo LayoutHelper::render('joomla.searchtools.default', array('view' => $this));?>
		<?php if (empty($this->items)) : ?>
			<div class="clearfix">&nbsp;</div>
			<div class="alert alert-no-items">
				<?php echo Text::_('COM_TJFIELDS_NO_MATCHING_RESULTS'); ?>
			</div>
			<?php
			else : ?>
				<table class="table" id="cityList">
					<thead>
						<tr>
							<th width="1%" class="hidden-phone"><input
								type="checkbox" name="checkall-toggle" value=""
								title="<?php echo Text::_('JGLOBAL_CHECK_ALL'); ?>"
								onclick="Joomla.checkAll(this)" />
							</th>

							<?php if (isset($this->items[0]->state)): ?>
								<th width="1%" class="nowrap center">
									<?php echo HTMLHelper::_('grid.sort', 'JSTATUS', 'state', $listDirn, $listOrder); ?>
								</th>
							<?php endif; ?>

							<th class='left'>
								<?php echo HTMLHelper::_('grid.sort', 'COM_TJFIELDS_CITIES_CITY', 'a.city', $listDirn, $listOrder); ?>
							</th>

							<th class="left">
								<?php echo HTMLHelper::_('grid.sort', 'COM_TJFIELDS_CITIES_COUNTRY', 'a.country_id', $listDirn, $listOrder); ?>
							</th>

							<th class="center hidden-phone">
								<?php echo HTMLHelper::_('grid.sort', 'COM_TJFIELDS_CITIES_REGION', 'a.region_id', $listDirn, $listOrder); ?>
							</th>

							<th class='left hidden-phone'>
								<?php echo HTMLHelper::_('grid.sort', 'COM_TJFIELDS_CITIES_CITY_JTEXT', 'a.city_jtext', $listDirn, $listOrder); ?>
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
						foreach ($this->items as $i => $item):
							$ordering = ($listOrder == 'a.ordering');
							$canCreate = $user->authorise('core.create', 'com_tjfields');
							$canEdit = $user->authorise('core.edit', 'com_tjfields');
							$canCheckin = $user->authorise('core.manage', 'com_tjfields');
							$canChange = $user->authorise('core.edit.state', 'com_tjfields');
							?>

							<tr class="row<?php echo $i % 2; ?>">
								<td class="center hidden-phone">
									<?php echo HTMLHelper::_('grid.id', $i, $item->id); ?>
								</td>

								<?php if (isset($this->items[0]->state)): ?>
									<td class="center">
										<?php echo HTMLHelper::_('jgrid.published', $item->state, $i, 'cities.', $canChange, 'cb'); ?>
									</td>
								<?php endif; ?>

								<td>
									<?php if (isset($item->checked_out) && $item->checked_out) : ?>
										<?php echo HTMLHelper::_('jgrid.checkedout', $i, $item->editor, $item->checked_out_time, 'cities.', $canCheckin); ?>
									<?php endif; ?>
									<?php if ($canEdit) : ?>
										<a href="<?php echo Route::_('index.php?option=com_tjfields&task=city.edit&id=' . (int) $item->id . '&client=' . $this->input->get('client', '', 'STRING')); ?>">
											<?php echo $this->escape($item->city); ?>
										</a>
										<?php else : ?>
											<?php echo $this->escape($item->city); ?>
									<?php endif; ?>
								</td>

								<td class="left">
									<?php echo $this->escape($item->country); ?>
								</td>

								<td class="center hidden-phone">
									<?php echo $this->escape($item->region); ?>
								</td>

								<td class="left hidden-phone">
									<?php
									if ($lang->hasKey(strtoupper($item->city_jtext ? $item->city_jtext : '')))
									{
										echo Text::_($item->city_jtext);
									}
									elseif ($item->city_jtext !== '')
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
						<?php endforeach; ?>
					</tbody>
				</table>
				<?php echo $this->pagination->getListFooter(); ?>
			<?php endif; ?>

			<input type="hidden" name="task" value="" />
			<input type="hidden" name="boxchecked" value="0" />
			<input type="hidden" name="filter_order" value="<?php echo $listOrder; ?>" />
			<input type="hidden" name="filter_order_Dir" value="<?php echo $listDirn; ?>" />

			<?php echo HTMLHelper::_('form.token'); ?>
		</div>
	</form>
</div>
