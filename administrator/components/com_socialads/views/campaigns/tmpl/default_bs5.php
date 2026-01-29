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

HTMLHelper::addIncludePath(JPATH_COMPONENT.'/helpers/html');
HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('behavior.multiselect');
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

$user      = Factory::getUser();
$userId    = $user->get('id');
$listOrder = $this->state->get('list.ordering');
$listDirn  = $this->state->get('list.direction');
$canOrder  = $user->authorise('core.edit.state', 'com_socialads');
$saveOrder = $listOrder == 'a.ordering';

if ($saveOrder)
{
	$saveOrderingUrl = 'index.php?option=com_socialads&task=campaigns.saveOrderAjax&tmpl=component';
	HTMLHelper::_('sortablelist.sortable', 'campaignList', 'adminForm', strtolower($listDirn), $saveOrderingUrl);
}

if (!empty($this->extra_sidebar))
{
	$this->sidebar .= $this->extra_sidebar;
}
?>
<div class="<?php echo SA_WRAPPER_CLASS;?> sa-ad-campagins">
	<div id="j-main-container" class="col-md-12">

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
	<form action="<?php echo Route::_('index.php?option=com_socialads&view=campaigns'); ?>" method="post"name="adminForm" id="adminForm">
	<div id="filter-bar" class="btn-toolbar">
			<div class="col-md-12 mt-2">
				<div class="row">
					<div class="col-md-6 col-sm-12">
						<div class="filter-search btn-group float-start">
							<input type="text" name="filter_search" id="filter_search" class="form-control" placeholder="<?php echo Text::_('COM_SOCIALADS_CAMPAIGNS_FILTER_SEARCH'); ?>" value="<?php echo $this->escape($this->state->get('filter.search')); ?>" title="<?php echo Text::_('COM_SOCIALADS_CAMPAIGNS_FILTER_SEARCH'); ?>" />
							<button class="btn hasTooltip btn-outline-secondary" type="submit" title="<?php echo Text::_('JSEARCH_FILTER_SUBMIT'); ?>"><i class="fa fa-search"></i></button>
							<button class="btn hasTooltip btn-outline-secondary" id="clear-search-button" type="button" title="<?php echo Text::_('JSEARCH_FILTER_CLEAR'); ?>"><i class="fa fa-remove"></i></button>
						</div>
					</div>
					<div class="btn-group float-end col-12 col-sm-12 col-md-2 col-lg-2 hidden-phone">
						<?php
							echo HTMLHelper::_('select.genericlist', $this->publish_states, "filter_published", 'class="input-medium form-select" size="1" onchange="document.adminForm.submit();" name="filter_published"', "value", "text", $this->state->get('filter.state'));
						?>
					</div>
					<?php
						if (JVERSION >= '3.0') : ?>
						<div class="btn-group float-end col-12 col-sm-12 col-md-2 col-lg-2 hidden-phone">
							<?php echo HTMLHelper::_('select.genericlist', $this->createdbyoptions, "filter_usernamelist", 'class="ad-status inputbox input-medium form-select" size="1" onchange="document.adminForm.submit();" name="usernamelist"', "value", "text", $this->state->get('filter.usernamelist'));	?>
						</div>
						<div class="btn-group float-end col-12 col-sm-12 col-md-2 col-lg-1 hidden-phone">
							<label for="limit" class="visually-hidden"><?php echo Text::_('JFIELD_PLG_SEARCH_SEARCHLIMIT_DESC');?></label>
							<?php echo $this->pagination->getLimitBox(); ?>
						</div>
					<?php endif; ?>
				</div>
			</div>
		</div>
		<div class="clearfix">&nbsp;</div>
		<?php
		if (empty($this->items)) : ?>
			<div class="clearfix">&nbsp;</div>
			<div class="alert alert-no-items">
				<?php echo Text::_('COM_SOCIALADS_FILTER_SEARCH_NOT_FOUND'); ?>
			</div>
			<?php
		else : ?>
			<div id = "no-more-tables">
				<table class="table mt-2 table-responsive" id="campaignList">
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
							<?php endif; ?>
								<th class="left">
									<?php echo HTMLHelper::_('grid.sort', 'COM_SOCIALADS_CAMPAIGNS_CAMPAIGN', 'a.campaign', $listDirn, $listOrder); ?>
								</th>
								<th class="left">
									<?php echo HTMLHelper::_('grid.sort', 'COM_SOCIALADS_CAMPAIGNS_CREATED_BY', 'u.name', $listDirn, $listOrder); ?>
								</th>
								<th class="sa-text-right">
									<?php echo HTMLHelper::_('grid.sort', 'COM_SOCIALADS_CAMPAIGNS_DAILY_BUDGET', 'a.daily_budget', $listDirn, $listOrder); ?>
								</th>
								<th class="sa-text-right">
									<?php echo Text::_('COM_SOCIALADS_FORM_LBL_CAMPAIGN_START_DATE'); ?>
								</th>
								<th class="sa-text-right">
									<?php echo Text::_('COM_SOCIALADS_FORM_LBL_CAMPAIGN_END_DATE'); ?>
								</th>
								<th class="sa-text-right">
									<?php echo HTMLHelper::_('grid.sort', 'COM_SOCIALADS_CAMPAIGNS_NO_OF_ADS', 'no_of_ads', $listDirn, $listOrder); ?>
								</th>
								<th class="sa-text-right">
									<?php echo HTMLHelper::_('grid.sort', 'COM_SOCIALADS_CAMPAIGNS_CLICKS', 'clicks', $listDirn, $listOrder); ?>
								</th>
								<th class="sa-text-right">
									<?php echo HTMLHelper::_('grid.sort', 'COM_SOCIALADS_CAMPAIGNS_IMPRESSIONS', 'impressions', $listDirn, $listOrder); ?>
								</th>
								<th class="sa-text-right">
									<?php echo HTMLHelper::_('grid.sort', 'COM_SOCIALADS_CAMPAIGNS_CLICK_THROUGH_RATIO', 'impressions', $listDirn, $listOrder); ?>
								</th>

							<?php if (isset($this->items[0]->id)): ?>
								<th width="1%" class="nowrap center hidden-phone sa-text-right">
									<?php echo HTMLHelper::_('grid.sort', 'JGRID_HEADING_ID', 'a.id', $listDirn, $listOrder); ?>
								</th>
							<?php endif; ?>
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
						<tr>
							<td colspan="<?php echo $colspan ?>">
								<?php echo $this->pagination->getListFooter(); ?>
							</td>
						</tr>
					</tfoot>
					<tbody>
						<?php foreach ($this->items as $i => $item) :
							$ordering = ($listOrder == 'a.ordering');
							$canCreate	= $user->authorise('core.create',		'com_socialads');
							$canEdit	= $user->authorise('core.edit',			'com_socialads');
							$canCheckin	= $user->authorise('core.manage',		'com_socialads');
							$canChange	= $user->authorise('core.edit.state',	'com_socialads');
						?>
						<tr class="row<?php echo $i % 2; ?>">
							<?php if (isset($this->items[0]->ordering)): ?>
								<td class="order nowrap center hidden-phone">
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
								<td class="hidden-phone">
									<?php echo HTMLHelper::_('grid.id', $i, $item->id); ?>
								</td>
							<?php if (isset($this->items[0]->state)): ?>
								<td class="center" data-title="<?php echo Text::_('JSTATUS');?>">
									<?php echo HTMLHelper::_('jgrid.published', $item->state, $i, 'campaigns.', $canChange, 'cb'); ?>
								</td>
							<?php endif; ?>
								<td data-title="<?php echo Text::_('COM_SOCIALADS_CAMPAIGNS_CAMPAIGN');?>">
									<?php if (isset($item->checked_out) && $item->checked_out) : ?>
										<?php echo HTMLHelper::_('jgrid.checkedout', $i, $item->editor, $item->checked_out_time, 'campaigns.', $canCheckin); ?>
									<?php endif; ?>
									<?php if ($canEdit) : ?>
										<a href="<?php echo Route::_('index.php?option=com_socialads&task=campaign.edit&id='.(int) $item->id); ?>">
											<?php echo htmlspecialchars($item->campaign, ENT_COMPAT, 'UTF-8');?>
										</a>
									<?php else : ?>
										<?php echo htmlspecialchars($item->campaign, ENT_COMPAT, 'UTF-8');?>
									<?php endif; ?>
								</td>
								<td data-title="<?php echo Text::_('COM_SOCIALADS_CAMPAIGNS_CREATED_BY');?>">
									<?php echo $item->uname; ?>
								</td>
								<td data-title="<?php echo Text::_('COM_SOCIALADS_CAMPAIGNS_DAILY_BUDGET');?>" class="sa-text-right">
									<?php echo SaCommonHelper::getFormattedPrice($item->daily_budget, '', $this->params->get('decimals_count', 2)); ?>
								</td>
								<td data-title="<?php echo Text::_('COM_SOCIALADS_FORM_LBL_CAMPAIGN_START_DATE');?>" class="sa-text-right">
									<?php echo $item->start_date ? $item->start_date : '-'; ?>
								</td>
								<td data-title="<?php echo Text::_('COM_SOCIALADS_FORM_LBL_CAMPAIGN_END_DATE');?>" class="sa-text-right">
									<?php echo $item->end_date ? $item->end_date : '-'; ?>
								</td>
								<td data-title="<?php echo Text::_('COM_SOCIALADS_CAMPAIGNS_NO_OF_ADS');?>" class="sa-text-right">
									<?php if ($item->no_of_ads)
											{
									?>
												<a href="index.php?option=com_socialads&view=forms&filter_campaignslist=<?php echo $item->id; ?>" title="<?php echo Text::_('COM_SOCIALADS_CLICK_TO_VIEW_ADS'); ?>"><?php echo $item->no_of_ads; ?></a>
									<?php	}
											else
											{
												echo "0";
											}
									?>

								</td>
								<td data-title="<?php echo Text::_('COM_SOCIALADS_CAMPAIGNS_CLICKS');?>" class="sa-text-right">
									<?php
									if($item->clicks > 0)
									{
										echo $item->clicks;
									}
									else
									{
										echo "0";
									}
									?>
								</td>
								<td data-title="<?php echo Text::_('COM_SOCIALADS_CAMPAIGNS_IMPRESSIONS');?>" class="sa-text-right">
									<?php
									if($item->impressions > 0)
									{
										echo $item->impressions;
									}
									else
									{
										echo "0";
									}
									?>
								</td>
								<td data-title="<?php echo Text::_('COM_SOCIALADS_CAMPAIGNS_CLICK_THROUGH_RATIO');?>" class="sa-text-right">
									<?php
									$ctr = 0;
									if ($item->impressions != 0)
									{
										$ctr = (($item->clicks) / ($item->impressions)) * 100;
										echo number_format($ctr, 6);
									}
									else
									{
										echo number_format($ctr, 6);
									}
									?>
								</td>
								<?php
								if (isset($this->items[0]->id)): ?>
									<td class="hidden-phone sa-text-right">
										<?php echo (int) $item->id; ?>
									</td>
								<?php
								endif; ?>
							</tr>
						<?php
						endforeach; ?>
					</tbody>
				</table>
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
	var tjListOrderingColumn = "<?php echo $listOrder; ?>";
	saAdmin.initSaJs();
	Joomla.submitbutton = function(action){saAdmin.campaigns.submitButtonAction(action);}
</script>
