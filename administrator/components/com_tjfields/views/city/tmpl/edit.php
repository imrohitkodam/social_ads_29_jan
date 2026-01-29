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

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Router\Route;

JHtml::addIncludePath(JPATH_COMPONENT . '/helpers/html');

HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('behavior.formvalidator');
HTMLHelper::_('behavior.keepalive');

$this->item->country_id = !empty($this->item->country_id) ? $this->item->country_id : '';
$this->item->region_id = !empty($this->item->region_id) ? $this->item->region_id : '';

Factory::getDocument()->addScriptDeclaration("
Joomla.submitbutton = function(task)
{
	if (task == 'city.cancel')
	{
		Joomla.submitform(task, document.getElementById('city-form'));
	}
	else
	{
		if (task != 'city.cancel' && document.formvalidator.isValid(document.getElementById('city-form')))
		{
			Joomla.submitform(task, document.getElementById('city-form'));
		}
		else
		{
			alert('" . $this->escape(Text::_('JGLOBAL_VALIDATION_FORM_FAILED')) . "');
		}
	}
}

var defaultCountryId='';
var defaultRegionId='';
var data='';

techjoomla.jQuery(document).ready(function()
{
	techjoomla.jQuery('#jform_country_id').attr('data-chosen', 'com_tjfields');
	techjoomla.jQuery('#jform_region_id').attr('data-chosen', 'com_tjfields');

	defaultCountryId = '" . $this->item->country_id . "';
	defaultRegionId = '" . $this->item->region_id . "';

	generateRegions(data, defaultCountryId, defaultRegionId);
});


function generateRegions(countryId, state, city)
{
	var countryId = techjoomla.jQuery('#jform_country_id').val();

	techjoomla.jQuery.ajax(
	{
		url:'" . Uri::base() . "'+'index.php?option=com_tjfields&task=city.getRegionsList&countryId='+countryId+'&tmpl=component',
		type:'GET',
		dataType:'json',
		success:function(data)
		{
			if (data === undefined || data === null || data.length <= 0)
			{
				var option = '<option value=\"\">' + '" . Text::_('COM_TJFIELDS_FILTER_SELECT_REGION') . "' + '</option>';
				select = techjoomla.jQuery('#jform_region_id');
				select.find('option').remove().end();
				select.append(option);
			}
			else
			{
				generateRegionOptions(data, countryId, defaultRegionId);
			}
		}
	});
}

function generateRegionOptions(data, countryId, defaultRegionId)
{
	var options, index, select, option;
	select = techjoomla.jQuery('#jform_region_id');
	select.find('option').remove().end();
	options = data.options;

	var option = '<option value=\"\">' + '" . Text::_('COM_TJFIELDS_FILTER_SELECT_REGION') . "' + '</option>';
	techjoomla.jQuery('#jform_region_id').append(option);

	for (index = 0; index < data.length; ++index)
	{
		var region = data[index];

		if (defaultRegionId === region['id'])
		{
			var option = \"<option value=\" + region['id'] + \" selected='selected'>\"  + region['region'] + '</option>';
		}
		else
		{
			var option = \"<option value=\" + region['id'] + \">\" + region['region'] + '</option>';
			var option = \"<option value=\" + region['id'] + \">\" + region['region'] + '</option>';
		}

		techjoomla.jQuery('#jform_region_id').append(option);

		techjoomla.jQuery('#jform_region_id').trigger('liszt:updated');
		techjoomla.jQuery('#jform_region_id').trigger('chosen:updated');
	}
}
");
?>
<div class="<?php echo TJFIELDS_WRAPPER_CLASS;?> tj-city">
	<form
		action="<?php echo Route::_('index.php?option=com_tjfields&layout=edit&id=' . (int) $this->item->id . '&client=' . $this->input->get('client', '', 'STRING')); ?>"
		method="post" enctype="multipart/form-data" name="adminForm" id="city-form" class="form-validate">

		<div class="form-horizontal">
			<div class="row-fluid">
				<div class="span12 form-horizontal">
					<fieldset class="adminform">

						<div class="control-group">
							<div class="control-label">
								<?php echo $this->form->getLabel('id'); ?>
							</div>
							<div class="controls">
								<?php echo $this->form->getInput('id'); ?>
							</div>
						</div>

						<div class="control-group">
							<div class="control-label">
								<?php echo $this->form->getLabel('city'); ?>
							</div>
							<div class="controls">
								<?php echo $this->form->getInput('city'); ?>
							</div>
						</div>


						<div class="control-group">
							<div class="control-label">
								<?php echo $this->form->getLabel('country_id'); ?>
							</div>
							<div class="controls">
								<?php echo $this->form->getInput('country_id'); ?>
							</div>
						</div>

						<div class="control-group">
							<div class="control-label">
								<?php echo $this->form->getLabel('region_id'); ?>
							</div>
							<div class="controls">
								<?php echo $this->form->getInput('region_id'); ?>
							</div>
						</div>

						<div class="control-group">
							<div class="control-label">
								<?php echo $this->form->getLabel('city_jtext'); ?>
							</div>
							<div class="controls">
								<?php echo $this->form->getInput('city_jtext'); ?>
								<div class="row-fluid">
									<div class="span12">
										<p class="text text-warning">
										<br/>
										<?php echo Text::_('COM_TJFIELDS_FORM_DESC_CITY_CITY_JTEXT_HELP'); ?>
										</p>
									</div>
								</div>
							</div>
						</div>

					</fieldset>
				</div>
			</div>

			<input type="hidden" name="task" value="" />
			<?php echo HTMLHelper::_('form.token'); ?>
		</div>
	</form>
</div>
