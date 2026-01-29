<?php
/**
 * @version    SVN:<SVN_ID>
 * @package    SocialAds
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2015 TechJoomla. All rights reserved
 * @license    GNU General Public License version 2, or later
 */

// no direct access
defined('_JEXEC') or die;
use Joomla\CMS\HTML\HTMLHelper;

use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

JHtml::addIncludePath(JPATH_COMPONENT . '/helpers/html');
HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('formbehavior.chosen', 'select');
HTMLHelper::_('behavior.multiselect');

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

$user       = Factory::getUser();
$userId     = $user->get('id');
$listOrder  = $this->state->get('list.ordering');
$listDirn   = $this->state->get('list.direction');
$canCreate  = $user->authorise('core.create', 'com_socialads');
$canEdit    = $user->authorise('core.edit', 'com_socialads');
$canCheckin = $user->authorise('core.manage', 'com_socialads');
$canChange  = $user->authorise('core.edit.state', 'com_socialads');
$canDelete  = $user->authorise('core.delete', 'com_socialads');
$params=ComponentHelper::getParams('com_socialads');
$payment_mode=$params->get('payment_mode');
$canDo = SocialadsHelper::getActions();

if ($payment_mode =='pay_per_ad_mode')
{ ?>
	<div class="alert alert-warning alert-dismissible fade show" role="alert">
		<?php echo Text::_('COM_SOCIALADS_WALLET_NO_AUTH_SEE'); ?>
		<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
	</div>
	<?php
	return false;
}
?>
<div class="<?php echo SA_WRAPPER_CLASS;?> " id="sa-campaigns">
	<form action="<?php echo Route::_('index.php?option=com_socialads&view=campaigns&Itemid=' . $this->campaignsItemId); ?>" method="post" name="adminForm" id="adminForm">
		<div class="page-header">
			<h1><?php echo Text::_("COM_SOCIALADS_TITLE_CAMPAIGNS");?></h1>
		</div>
		<div>
			<?php echo $this->toolbarHTML;?>
		</div>
		<div class="clearfix"></div>
		<hr class="hr-condensed" />
		<div id="filter-bar" class="btn-toolbar row">
			<div class="col-lg-4 col-md-7 col-sm-6 col-xs-12">
				<div class="input-group">
					<input type="text"
						placeholder="<?php echo Text::_('COM_SOCIALADS_CAMPAIGNS_FILTER_SEARCH'); ?>"
						name="filter_search"
						id="filter_search"
						value="<?php echo $this->escape($this->state->get('filter.search')); ?>"
						class="form-control"
						onchange="document.adminForm.submit();"
						title="<?php echo Text::_('COM_SOCIALADS_CAMPAIGNS_FILTER_SEARCH'); ?>"/>
						<button type="button" onclick="this.form.submit();" class="btn btn-md btn-primary border-0 border-start text-light" data-original-title="Search">
							<i class="fa fa-search" aria-hidden="true"></i>
						</button>
						<button onclick="document.getElementById('filter_search').value='';this.form.submit();" type="button" class="btn btn-md btn-secondary" data-original-title="Clear">
							<i class="fa fa-remove" aria-hidden="true"></i>
						</button>
				</div>
			</div>
			<div class="col-xs-2 col-sm-6 col-md-5 col-lg-8">
				<div class="btn-group float-end hidden-xs">
					<label label-default for="limit" class="element-invisible"></label>
					<?php echo $this->pagination->getLimitBox(); ?>
				</div>
			</div>
		</div>
		<?php
		if (empty($this->items)):?>
			<br>
			<div class="alert">
				<?php
					echo Text::_('COM_SOCIALADS_FILTER_SEARCH_NOT_FOUND');
				?>
			</div>
		<?php
		else:?>
			<div id="no-more-tables" class="table-responsive ads-list pt-3">
				<table class="table table-striped table-bordered" id="campaignList">
					<thead class="table-primary">
						<tr>
							<th class="hidden-xm center">
									<input type="checkbox" name="checkall-toggle" value="" title="<?php echo Text::_('JGLOBAL_CHECK_ALL');?>" onclick="Joomla.checkAll(this)" />
							</th>
							<?php if (isset($this->items[0]->state)): ?>
							<th class="center">
								<?php echo HTMLHelper::_('grid.sort', 'JSTATUS', 'a.state', $listDirn, $listOrder); ?>
							</th>
							<?php endif; ?>
							<th class="">
								<?php echo HTMLHelper::_('grid.sort',  'COM_SOCIALADS_CAMPAIGNS_CAMPAIGN', 'a.campaign', $listDirn, $listOrder); ?>
							</th>
							<th class="sa-text-left">
								<?php echo HTMLHelper::_('grid.sort',  'COM_SOCIALADS_CAMPAIGNS_DAILY_BUDGET', 'a.daily_budget', $listDirn, $listOrder); ?>
							</th>
							<th class="sa-text-left">
								<?php echo Text::_('COM_SOCIALADS_FORM_LBL_CAMPAIGN_START_DATE'); ?>
							</th>
							<th class="sa-text-left">
								<?php echo Text::_('COM_SOCIALADS_FORM_LBL_CAMPAIGN_END_DATE'); ?>
							</th>
							<th class="sa-text-left">
								<?php echo HTMLHelper::_('grid.sort',  'COM_SOCIALADS_CAMPAIGNS_NO_OF_ADS', 'no_of_ads', $listDirn, $listOrder); ?>
							</th>
							<th class="sa-text-left">
								<?php echo HTMLHelper::_('grid.sort',  'COM_SOCIALADS_ADS_AD_NO_OF_CLICKS', 'clicks', $listDirn, $listOrder); ?>
							</th>
							<th class="sa-text-left">
								<?php echo HTMLHelper::_('grid.sort',  'COM_SOCIALADS_ADS_AD_TYPE_IMPRS', 'impressions', $listDirn, $listOrder); ?>
							</th>
							<th class="sa-text-left">
								<?php echo HTMLHelper::_('grid.sort',  'COM_SOCIALADS_ADS_AD_TYPE_C_T_R', 'impressions', $listDirn, $listOrder); ?>
							</th>
						</tr>
					</thead>
					<tbody>
						<?php
						foreach ($this->items as $i => $item) :
							$canEdit = $user->authorise('core.edit', 'com_socialads');
							if (!$canEdit && $user->authorise('core.edit.own', 'com_socialads')):
								$canEdit = Factory::getUser()->id == $item->created_by;
							endif; ?>
							<tr class="row<?php echo $i % 2; ?>">
								<td class="hidden-xm center">
									<?php echo HTMLHelper::_('grid.id', $i, $item->id); ?>
								</td>
								<?php
								if (isset($this->items[0]->state)):
									if ($item->state == '0' || $item->state == '1'):?>
										<td class="center" data-title="<?php echo Text::_('JPUBLISHED');?>">
											<?php
											if ($canDo->get("core.edit.state")): ?>
												<a class="btn btn-micro" href="javascript:void(0);" onclick="document.adminForm.cb<?php echo $i; ?>.checked=1; document.adminForm.boxchecked.value=1; Joomla.submitbutton('<?php echo ($item->state) ? 'campaigns.unpublish' : 'campaigns.publish';?>');">
													<?php
													if ($item->state == 1): ?>
														<img src="<?php echo Uri::root(true); ?>/media/com_sa/images/publish.png"/>
														<?php
													elseif ($item->state == 0): ?>
														<img class="inactive" src="<?php echo Uri::root(true); ?>/media/com_sa/images/unpublish.png"/>
														<?php
													endif; ?>
												</a>
												<?php
											else:?>
												<a class=" disabled btn btn-micro" title="<?php echo Text::_("COM_SOCIALADS_NOT_ALLOWED")?>">
												<?php
													if ($item->state == 1): ?>
														<img src="<?php echo Uri::root(true); ?>/media/com_sa/images/publish.png"/>
														<?php
													else: ?>
														<img src="<?php echo Uri::root(true); ?>/media/com_sa/images/unpublish.png"/>
														<?php
													endif; ?>
												</a>
												<?php
											endif;?>
										</td>
									<?php
									elseif ($item->state == '2'):?>
										<td class="center" data-title="<?php echo Text::_('JPUBLISHED');?>">
											<a class="disabled btn btn-micro" title="<?php echo Text::_("COM_SOCIALADS_CAMPAIGNS_PAUSE")?>">
												<i class="icon-pause"></i>
											</a>
										</td>
										<?php
									endif;
								endif; ?>
								<td class="" data-title="<?php echo Text::_('COM_SOCIALADS_CAMPAIGNS_CAMPAIGN');?>">
									<?php
									if (isset($item->checked_out) && $item->checked_out) : ?>
										<?php echo HTMLHelper::_('jgrid.checkedout', $i, $item->editor, $item->checked_out_time, 'campaigns.', $canCheckin); ?>
									<?php
									endif; ?>
									<?php
									if ($canDo->get("core.edit")): ?>
										<a href="<?php echo Route::_('index.php?option=com_socialads&view=campaignform&id='.(int) $item->id); ?>">
											<?php echo htmlspecialchars($item->campaign, ENT_COMPAT, 'UTF-8'); ?>
										</a>
									<?php
									else: ?>
										<?php echo htmlspecialchars($item->campaign, ENT_COMPAT, 'UTF-8'); ?>
									<?php
									endif;?>
								</td>
								<td class="sa-text-left" data-title="<?php echo Text::_('COM_SOCIALADS_CAMPAIGNS_DAILY_BUDGET');?>">
									<?php echo SaCommonHelper::getFormattedPrice($item->daily_budget, '', $this->params->get('decimals_count', 2)); ?>
								</td>
								<td data-title="<?php echo Text::_('COM_SOCIALADS_FORM_LBL_CAMPAIGN_START_DATE');?>" class="sa-text-left">
									<?php echo $item->start_date ? $item->start_date : '-'; ?>
								</td>
								<td data-title="<?php echo Text::_('COM_SOCIALADS_FORM_LBL_CAMPAIGN_END_DATE');?>" class="sa-text-left">
									<?php echo $item->end_date ? $item->end_date : '-'; ?>
								</td>
								<td class="sa-text-left" data-title="<?php echo Text::_('COM_SOCIALADS_CAMPAIGNS_NO_OF_ADS');?>">
									<?php
									if ($item->no_of_ads)
									{ ?>
										<a href="index.php?option=com_socialads&view=ads&filter_campaignslist=<?php echo $item->id; ?>" title="<?php echo Text::_('COM_SOCIALADS_CLICK_TO_VIEW_ADS'); ?>"><?php echo $item->no_of_ads; ?></a>
									<?php
									}
									else
									{
										echo "0";
									} ?>
								</td>
								<td class="sa-text-left" data-title="<?php echo Text::_('COM_SOCIALADS_ADS_AD_NO_OF_CLICKS');?>">
									<?php
									if ($item->clicks > 0)
									{
									 echo $item->clicks;
									}
									else
									{
										echo "0";
									} ?>
								</td>
								<td class="sa-text-left" data-title="<?php echo Text::_('COM_SOCIALADS_ADS_AD_TYPE_IMPRS');?>">
									<?php
									if ($item->impressions > 0)
									{
										echo $item->impressions;
									}
									else
									{
										echo "0";
									} ?>
								</td>
								<td class="sa-text-left" data-title="<?php echo Text::_('COM_SOCIALADS_ADS_AD_TYPE_C_T_R');?>">
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
							</tr>
						<?php
						endforeach; ?>
					</tbody>
				</table>
			</div>
			<div class="float-end">
				<?php echo $this->pagination->getListFooter(); ?>
			</div>
		<?php
		endif;?>
		<input type="hidden" name="task" value="" />
		<input type="hidden" name="boxchecked" value="0" />
		<input type="hidden" name="filter_order" value="<?php echo $listOrder; ?>" />
		<input type="hidden" name="filter_order_Dir" value="<?php echo $listDirn; ?>" />
		<?php echo HTMLHelper::_('form.token'); ?>
	</form>
</div>
<script type="text/javascript">
	Joomla.submitbutton = function(action){
		sa.campaigns.submitButtonAction(action)
	}

	techjoomla.jQuery(document).ready(function (){
		techjoomla.jQuery('.delete-button').click(sa.campaigns.deleteItem);
	});
</script>
