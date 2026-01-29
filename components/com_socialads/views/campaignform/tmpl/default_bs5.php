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
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Component\ComponentHelper;

HTMLHelper::_('behavior.keepalive');
HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('behavior.formvalidator');

if (JVERSION < '4.0')
{
	HTMLHelper::_('formbehavior.chosen', 'select');
}

// Load admin language file
$lang = Factory::getLanguage();
$lang->load('com_socialads', JPATH_ADMINISTRATOR);

if ($this->item->state == 1)
{
	$state_string = Text::_("COM_SOCIALADS_COUPONS_PUBLISHED");
	$state_value = 1;
}
else
{
	$state_string = Text::_("COM_SOCIALADS_COUPONS_UNPUBLISHED");
	$state_value = 0;
}

$canState = Factory::getUser()->authorise('core.edit.state', 'com_socialads');
?>
<script type="text/javascript">
	var currency="<?php echo SaCommonHelper::getCurrencySymbol(); ?>";
	techjoomla.jQuery(document).ready(function()
	{
		techjoomla.jQuery(".alphaCheck").keyup(function(event)
				{
					let charCode = event.which || event.keyCode;

					// Keycode fot tab is 9 & shift is 16
					if (!((charCode === 9) || (charCode === 16)))
					sa.checkForZeroAndAlpha(this,'46', Joomla.Text._('COM_SOCIALAD_PAYMENT_ENTER_NUMERICS'));
				});
	});
</script>

<div class="<?php echo SA_WRAPPER_CLASS; ?> campaign-edit front-end-edit">
	<div class="page-header">
		<h1>
			<?php
			 if (!empty($this->item->id)):
			 echo Text::_('COM_SOCIALADS_EDIT_ITEM');
			else:
				echo Text::_('COM_SOCIALADS_ADD_ITEM');
			endif;
			?>
		</h1>
	</div>
	<form id="campaign-form" action="<?php echo Route::_('index.php?option=com_socialads&task=campaign.edit'); ?>" method="post" class="form-validate form-horizontal" enctype="multipart/form-data">
		<div class="form-group row">
			<div class="col-lg-2 col-md-2 col-sm-3 col-xs-12 form-label"><?php echo $this->form->getLabel('campaign'); ?></div>
			<div class="col-lg-4 col-md-4 col-sm-9 col-xs-12"><?php echo $this->form->getInput('campaign'); ?></div>
		</div>
		<!---->
	<div class="form-group row">
		<?php $canState = false; ?>
		<?php $canState = $canState = Factory::getUser()->authorise('core.edit.own','com_socialads'); ?>

		<?php
		if(!$canState):
			?>
			<div class=" col-lg-2 col-md-2 col-sm-2 col-xs-12 form-label ">
				<?php echo $this->form->getLabel('state'); ?>
			</div>
			<?php
			$state_string = Text::_('COM_SOCIALADS_COUPONS_PUBLISHED');
			$state_value = 0;
			if($this->item->state == 1):
				$state_string = Text::_('COM_SOCIALADS_COUPONS_UNPUBLISHED');
				$state_value = 1;
			elseif($this->item->state == 2):
				$state_string = Text::_('COM_SOCIALADS_COUPONS_PAUSED');
				$state_value = 2;
			endif;
			?>
			<div class="col-lg-4 col-md-4 col-sm-9 col-xs-12"><?php echo $state_string; ?></div>
			<input type="hidden" name="jform[state]" value="<?php echo $state_value; ?>" />
		<?php
		else:
			if (!empty($this->item->id) && $this->item->state == 2):?>
				<div class="col-lg-4 col-md-4 col-sm-9 col-xs-12">
					<?php echo Text::_('COM_SOCIALADS_COUPONS_PAUSED'); ?>
				</div>
				<input type="hidden" name="jform[state]" value="2" />
			<?php
			else :
			?>
			<div class=" col-lg-2 col-md-2 col-sm-2 col-xs-12 form-label ">
				<?php echo $this->form->getLabel('state'); ?>
			</div>
			<div class="col-lg-4 col-md-4 col-sm-9 col-xs-12">
				<?php //echo $this->form->getInput('state'); ?>

				<?php
				$jtPublish = " checked='checked' ";
				$jtUnpublish = "";

				if (empty($this->form->getValue('state')))
				{
					$jtPublish = "";
					$jtUnpublish = " checked='checked' ";
				}

				  $jtPublish;
				?>

				<label class="radio-inline">
				  <input type="radio" class="" <?php echo $jtPublish;?> value="1" id="jform_state1" name="jform[state]" >
				  <?php echo Text::_('COM_SOCIALADS_COUPONS_PUBLISHED');?>
				</label>
				<label class="radio-inline">
				  <input type="radio" class="" <?php echo $jtUnpublish;?> value="0" id="jform_state0" name="jform[state]" >
					<?php echo Text::_('COM_SOCIALADS_COUPONS_UNPUBLISHED');?>
				</label>
			</div>
		<?php
		endif;
		endif; ?>

	</div>
		<!----->
		<div class="form-group row">
			<?php
				$params = ComponentHelper::getParams('com_socialads');
				$currency = $params->get('currency');
			?>
			<div class="col-lg-2 col-md-2 col-sm-3 col-xs-12 form-label"><?php echo $this->form->getLabel('daily_budget'); ?></div>
			<div class="col-lg-4 col-md-4 col-sm-9 col-xs-12">
				<div class="input-group input-large">
					<?php echo $this->form->getInput('daily_budget'); ?>
					<span class="input-group-text"><?php echo SaCommonHelper::getCurrencySymbol(); ?></span>
				</div>
			</div>
		</div>
		<div class="form-group row">
			<div class="col-lg-2 col-md-2 col-sm-3 col-xs-12 form-label"><?php echo $this->form->getLabel('start_date'); ?></div>
			<div class="col-lg-4 col-md-4 col-sm-9 col-xs-12"><?php echo $this->form->getInput('start_date'); ?></div>
		</div>
		<div class="form-group row">
			<div class="col-lg-2 col-md-2 col-sm-3 col-xs-12 form-label"><?php echo $this->form->getLabel('end_date'); ?></div>
			<div class="col-lg-4 col-md-4 col-sm-9 col-xs-12"><?php echo $this->form->getInput('end_date'); ?></div>
		</div>
		<div class="form-group row">
			<div class="col-lg-6 col-md-6 col-sm-9 col-xs-12">
				<button type="submit" class="validate btn float-end ms-2 btn-success"><?php echo Text::_('JSUBMIT'); ?></button>
				<a class="btn float-end ms-2 btn-danger" href="<?php echo Route::_('index.php?option=com_socialads&task=campaignform.cancel'); ?>" title="<?php echo Text::_('JCANCEL'); ?>">
					<?php echo Text::_('JCANCEL'); ?>
				</a>
			</div>
		</div>
		<input type="hidden" name="option" value="com_socialads" />
		<input type="hidden" name="option" value="com_socialads" />
		<input type="hidden" name="task" value="campaignform.save" />
		<input type="hidden" name="cid" value=<?php echo $this->item->id;?>/>
		<?php echo HTMLHelper::_('form.token'); ?>
	</form>
</div>
