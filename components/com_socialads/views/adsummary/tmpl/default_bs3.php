<?php
/**
 * @version     SVN:<SVN_ID>
 * @package     Com_Socialads
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2015 TechJoomla. All rights reserved
 * @license     GNU General Public License version 2, or later
 */

// No direct access
defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Text;

if (JVERSION < '4.0.0')
{
	HTMLHelper::_('behavior.framework');
}

HTMLHelper::_('behavior.formvalidator');
HTMLHelper::_('stylesheet', 'media/techjoomla_strapper/vendors/font-awesome/css/font-awesome.min.css');
HTMLHelper::_('stylesheet', 'media/techjoomla_strapper/vendors/font-awesome/css/font-awesome.css');


$ad_params    = ComponentHelper::getParams('com_socialads');
$payment_mode = $ad_params->get('payment_mode');

$versionObj = new SaVersion;
$options = array("version" => $versionObj->getMediaVersion());
HTMLHelper::script('media/com_sa/vendors/morris/morris.min.js', $options);
HTMLHelper::script('media/com_sa/vendors/morris/raphael.min.js', $options);
HTMLHelper::stylesheet('media/com_sa/vendors/morris/morris.css', $options);
HTMLHelper::stylesheet('media/com_sa/css/tjdashboard-sb-admin.min.css', $options);
HTMLHelper::stylesheet('media/com_sa/css/tjdashboard.min.css', $options);
HTMLHelper::stylesheet('media/techjoomla_strapper/bs3/css/bootstrap.min.css', $options);
?>
<div class="<?php echo SA_WRAPPER_CLASS; ?> tj-adsummary" id="sa-adsummary">
	<div class="page-header">
		<h1>
			<?php echo Text::_('COM_SOCIALADS_AD_STATS');?>
		</h1>
	</div>
	<form action="" method="post" name="adminForm" id="adminForm">
		<div class="<?php echo SA_WRAPPER_CLASS;?>">
			<div class="tjDB">
				<div class="container">
					<div class="row">
						<div class="col-lg-4 col-md-5 col-sm-5 col-xs-9">
							<div class="form-group">
								<label label-default class="col-lg-2 col-md-2 col-sm-2 col-xs-3 control-label">
									<?php echo Text::_('COM_SOCIALADS_STATS_FROM_DATE'); ?>
								</label>
								<div class="col-lg-9 col-md-9 col-sm-9 col-xs-9">
									<div class="form-inline">
										<?php echo HTMLHelper::_('calendar', $this->from_date, 'from', 'from', '%Y-%m-%d', array('class' => ' input-small input-sm')); ?>
									</div>
								</div>
							</div>
						</div>
						<div class="col-lg-4 col-md-5 col-sm-5 col-xs-9">
							<div class="form-group">
								<label label-default class="col-lg-2 col-md-2 col-sm-2 col-xs-3 control-label">
									<?php echo Text::_("COM_SOCIALADS_STATS_TO_DATE"); ?>
								</label>
								<div class="col-lg-9 col-md-9 col-sm-9 col-xs-9">
									<div class="form-inline">
										<?php echo HTMLHelper::_('calendar', $this->to_date, 'to', 'to', '%Y-%m-%d', array('class' => ' input-small input-sm')); ?>
									</div>
								</div>
							</div>
						</div>
						<div class="col-lg-2 col-md-2 col-sm-2 col-xs-3">
							<input id="getadstatds" type="button" class="btn btn-success" value="<?php echo Text::_("COM_SOCIALADS_GO"); ?>" onclick="saAdmin.dashboard.validatePeriodicDates();Joomla.submitform();"/>
						</div>
						<div class="clearfix"></div>
					</div>
					<div class="row">
						<div class="col-sm-6 col-md-6 col-lg-6">
							<div class="panel panel-default">
								<div class="panel-heading">
									<i class="fa fa-bar-chart-o fa-fw"></i>
										<b><?php echo Text::_('COM_SOCIALADS_LINE_CHART_STAT'); ?></b>
								</div>
								<div class="panel-body">
									<?php if (!empty($this->statsforbar))
									{?>
										<div id="curve_chart"></div>
									<?php
									}
									else
									{?>
										<div class="">
											<?php echo Text::_("COM_SOCIALADS_NO_STATS_FOUND");?>
										</div>
									<?php
									}?>
								</div>
							</div>
						</div>
						<div class="col-sm-6 col-md-6 col-lg-6">
							<div class="panel panel-default">
								<div class="panel-heading">
									<i class="fa fa-bar-chart-o fa-fw"></i>
										<b><?php echo Text::_('COM_SOCIALADS_PIE_CHART_STAT'); ?></b>
								</div>
								<div class="panel-body">
									<?php
									if ($this->statsforpie[0] > 0 or $this->statsforpie[1] > 0)
									{?>
										<div id="donut_chart"></div>
									<?php
									}
									else
									{?>
										<div class="">
											<?php echo Text::_("COM_SOCIALADS_NO_STATS_FOUND");?>
										</div>
									<?php
									}?>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<input type="hidden" name="task" id="task" value="" />
		<input type="hidden" name="option" value="com_socialads" />
		<input type="hidden" name="view" value="adsummary" />
		<input type="hidden" name="layout" value="default" />
	</form>
</div>
<script>
	<!-- To draw charts on page load -->
	techjoomla.jQuery(document).ready(function() {
		drawCharts();
	});

	<!-- Function to draw charts on click for ststs tab -->
	function drawCharts()
	{
		<!-- SetTimeout function used to draw charts on page reload -->
		setTimeout(function()
		{
			techjoomla.jQuery('#curve_chart').html('');
			techjoomla.jQuery('#donut_chart').html('');
			<?php
			if (!empty($this->statsforbar))
			{
			?>
				<!-- Line chart for ad summary -->
				Morris.Line({
				element: 'curve_chart',
				data :<?php echo json_encode($this->statsforbar);?>,
				xkey: 'date',
				ykeys: ['click','impression'],
				labels: ['<?php echo Text::_('COM_SOCIALADS_FORM_LBL_ARCHIVESTAT_CLICKS');?>','<?php echo Text::_('COM_SOCIALADS_FORM_LBL_ARCHIVESTAT_IMPRESSIONS');?>'],
				xLabels: '<?php echo Text::_('COM_SOCIALADS_DATE')?>',
				lineColors: ['#FFA500','#3EA99F'],
				hideHover: 'auto',
				resize: true,
				});
			<?php
			}
			?>

			<?php
			if ($this->statsforpie[0] > 0 or $this->statsforpie[1] > 0)
			{
			?>
				<!-- Donut chart for ad summary -->
				Morris.Donut({
				element: 'donut_chart',
				data: [
				{label: "<?php echo Text::_('COM_SOCIALADS_FORM_LBL_ARCHIVESTAT_CLICKS');?>", value: <?php echo $this->statsforpie[1];?>},
				{label: "<?php echo Text::_('COM_SOCIALADS_FORM_LBL_ARCHIVESTAT_IMPRESSIONS');?>", value: <?php echo $this->statsforpie[0];?>},
				],
				colors: ["#f0ad4e", "#5cb85c"]
				});
			<?php
			}
			?>
		},300);
	}
</script>
