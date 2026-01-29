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
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;

if (JVERSION < '4.0.0')
{
	HTMLHelper::_('behavior.framework');
}

HTMLHelper::_('behavior.formvalidator');

$versionObj = new SaVersion;
$options = array("version" => $versionObj->getMediaVersion());
$attributes = array("defer" => "defer");
HTMLHelper::stylesheet('media/techjoomla_strapper/bs3/css/bootstrap.min.css', $options);
HTMLHelper::stylesheet('media/com_sa/vendors/morris/morris.css', $options);
HTMLHelper::stylesheet('media/com_sa/css/tjdashboard.min.css', $options);
HTMLHelper::script('media/com_sa/vendors/morris/morris.min.js', $options, $attributes);
HTMLHelper::script('media/com_sa/vendors/morris/raphael.min.js', $options, $attributes);
HTMLHelper::script('media/com_sa/js/sa.min.js', $options);
HTMLHelper::script('libraries/techjoomla/assets/js/houseKeeping.js', $options, $attributes);

if (JVERSION < '5.0.0')
{
	HTMLHelper::stylesheet('media/com_sa/vendors/font-awesome/css/font-awesome.min.css', $options);
}
else 
{
	HTMLHelper::stylesheet('media/com_sa/vendors/font-awesome/css/font-awesome-6-5-1.min.css', $options);
}

// Joomla Component Creator code to allow adding non select list filters
if (!empty($this->extra_sidebar))
{
	$this->sidebar .= $this->extra_sidebar;
}
?>

<div class="<?php echo SA_WRAPPER_CLASS; ?> tj-dashboard" id="sa-dashboard">
	<?php
	//if (JVERSION >= '3.0'):
		if (!empty($this->sidebar)):
	?>
	<div id="j-sidebar-container" class="span2">
		<?php echo $this->sidebar; ?>
	</div>
	<div id="j-main-container" class="span10">
	<?php
		else:
	?>
	<div id="j-main-container">
	<?php
		endif;
	//endif;
	?>
		<form action="<?php echo Route::_('index.php?option=com_socialads&view=dashboard');?>" method="post" name="adminForm" id="adminForm">
			<!-- TJ Bootstrap3 -->
			<div class="tjBs3">
				<!-- TJ Dashboard -->
				<div class="tjDB">
					<!-- Start - version -->
					<div class="row">
						<?php echo $this->loadTemplate('version'); ?>
					</div>

					<!-- Show maxmind db update or plugin warning -->
					<div class="row">
						<?php if ($this->geoTargeting && !$this->hasTJMaxmindPlg): ?>
							<div class="clearfix">
								<div class="alert alert-warning">
									<?php
										echo Text::_('COM_SOCIALADS_LBL_TJMAXMIND_PLUGIN_MISSING_OR_DISABLED');
									?>
									<br/>
									<a href="index.php?option=com_plugins&view=plugins&filter_folder=system&filter_element=tjmaxmind">
										<?php echo Text::_('COM_SOCIALADS_LBL_TJMAXMIND_PLUGIN_ENABLE'); ?>
									</a>
								</div>
							</div>

							<div class="clearfix"></div>
						<?php endif; ?>

						<?php if ($this->geoTargeting && !$this->maxmindDbExists): ?>
							<div class="clearfix">
								<div class="alert alert-error">
									<span class="icon-cancel"></span>
									<?php echo Text::_('COM_SOCIALADS_GEOLITECITY_INSTALLATION_1'); ?>
								</div>
								<div class="alert alert-info geo_target_instructions">
									<?php echo Text::_('COM_SOCIALADS_GEOLITECITY_INSTALLATION_2'); ?>
									<a target="_blank" href="https://www.maxmind.com/">
										<?php echo Text::_('COM_SOCIALADS_GEOLITECITY_CLICK_HERE'); ?>
									</a><br>
									<?php echo Text::_('COM_SOCIALADS_GEOLITECITY_INSTALLATION_3'); ?>
									<br>
									<?php echo Text::_('COM_SOCIALADS_GEOLITECITY_INSTALLATION_4'); ?>
									<br>
									<?php echo Text::_('COM_SOCIALADS_GEOLITECITY_INSTALLATION_5'); ?>
									<br>
									<?php echo Text::_('COM_SOCIALADS_GEOLITECITY_INSTALLATION_6'); ?>
								</div>
							</div>

							<div class="clearfix"></div>
						<?php endif; ?>
					</div>

					<!-- Start - stat boxes -->
					<div class="row">
						<?php echo $this->loadTemplate('statboxes'); ?>
					</div>

					<div class="row">
						<div class="col-lg-8 col-md-8">
							<!-- Start - Bar Chart for Monthly Income for past 12 months -->
							<div class="row">
								<div class="col-lg-12">
									<?php echo $this->loadTemplate('barchart'); ?>
								</div>
							</div>

							<!-- Start - donut chart for perodic order details -->
							<div class="row">
								<div class="col-lg-7 col-md-12 col-sm-12">
									<?php echo $this->loadTemplate('donutchart'); ?>
								</div>

								<!-- Start - stats tables -->
								<div class="col-lg-5 col-md-12 col-sm-12">
									<?php echo $this->loadTemplate('stattables'); ?>
								</div>
							</div>
						</div>

						<div class="col-lg-4 col-md-4">
							<?php
							if (JVERSION < '4.0.0')
							{
								echo $this->loadTemplate('verticalbox_bs2');
							}
							else 
							{
								echo $this->loadTemplate('verticalbox_bs5');
							}
							?>
						</div>
					</div>
				</div>
				<!-- /.tjDB TJ Dashboard -->
			<div>
			<!-- /.tjBs3TJ TJ Bootstrap3 -->
		</form>
	</div>
</div>

<script type="text/javascript">
	var tjHouseKeepingView = "dashboard";
	saAdmin.dashboard.initDashboardJs();
</script>
