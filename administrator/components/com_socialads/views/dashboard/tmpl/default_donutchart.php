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

use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;

?>

<div class="panel panel-default">
	<div class="panel-heading">
		<i class="fa fa-pie-chart fa-fw"></i>
		<?php echo Text::_('COM_SOCIALADS_MONTHLY_PERIODIC_ORDERS_DETAILS'); ?>
	</div>
	<div class="panel-body">
		<div class="col-lg-6 col-md-6 col-sm-12">
			<div class="form-inline">
				<label for="from"><?php echo Text::_('COM_SOCIALADS_STATS_FROM_DATE'); ?></label>
				<?php
				echo HTMLHelper::_('calendar', $this->from_date, 'from', 'from', '%Y-%m-%d', array(
					'class' => 'form-control'
				));
				?>
			</div>
		</div>
		
		<div class="col-lg-6 col-md-6 col-sm-12">
			<div class="form-inline">
				<label for="to"><?php echo Text::_("COM_SOCIALADS_STATS_TO_DATE"); ?></label>
				<?php
				echo HTMLHelper::_('calendar', $this->to_date, 'to', 'to', '%Y-%m-%d', array(
					'class' => 'form-control'
				));
				?>
			</div>
		</div>
		
		<div class="col-lg-12 col-md-12 col-sm-12 pull-left">
			<label class="hidden-xs">&nbsp;</label>
			<input id="btnRefresh" class="btn btn-micro btn-primary float-end pull-right sa-mt-3" type="button" value="<?php echo Text::_("COM_SOCIALADS_GO");?>" onclick="saAdmin.dashboard.validatePeriodicDates();Joomla.submitform();" />
		</div>

		<div class="clearfix"></div>

		 <div class="col-sm-12 col-md-12 col-lg-12">
			<div>
				<h4 class="center"><?php echo Text::_('COM_SOCIALADS_PERIODIC_INCOME'); ?></h4>
			</div>
			<?php
			// To show income between selected dates

			if ($this->periodicorderscount)
			{
				$this->price = SaCommonHelper::getFormattedPrice($this->periodicorderscount,$this->currency);

				if ($this->price)
				{
					?>
					<div class="huge center">
						<?php echo $this->price; ?>
					</div>
					<?php
				}
				else
				{
					?>
					<div class="huge center">
						<?php echo $this->price . " 0"; ?>
					</div>
					<?php
				}
			}
				?>
		</div>

		<div class="col-sm-12 col-md-12 col-lg-12">
			<div id="donut-chart">
			</div>
			<div class="center" id="donut-chart-msg">
				<?php echo Text::_("COM_SOCIALADS_NO_DATA_FOUND"); ?>
			</div>
		</div>
	</div>
</div>
