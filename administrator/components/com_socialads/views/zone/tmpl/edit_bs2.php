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

use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Component\ComponentHelper;

JHtml::addIncludePath(JPATH_COMPONENT . '/helpers/html');

HTMLHelper::_('behavior.formvalidator');
HTMLHelper::_('formbehavior.chosen', 'select');
HTMLHelper::_('behavior.keepalive');

// Import helper for declaring language constant
JLoader::import('SocialadsHelper', Uri::root().'administrator/components/com_socialads/helpers/socialads.php');

// Call helper function
SocialadsHelper::getLanguageConstant();

if(!empty($this->item->id))
{
	$zoneid =$this->item->id;
	$this->recordsCount;
}
else
{
	$zoneid = 0;
	$this->recordsCount=0;
}

if($this->item->id)
{
	$default_layout = str_replace('||','',$this->item->ad_type);
	$affiliate = str_replace('|', '', $default_layout);
	$default_layout = str_replace('affiliate', '', $affiliate);
}
else
{
	$default_layout='text_media';
}

$versionObj = new SaVersion;
$debugMode = Factory::getConfig()->get('debug');
$widgetUrl = Uri::root() . "media/com_sa/js/";
$widgetUrl .= ($debugMode) ? "sawidget.js" : "sawidget.min.js";
$widgetUrl .= "?" . $versionObj->getMediaVersion();
?>

<script type="text/javascript">
	var recordsCount="<?php echo $this->recordsCount; ?>";
	var saWidgetSiteRootUrl="<?php echo Uri::root();?>";
	var layoutName = "<?php echo $this->item->layout?>";
	var widgetUrl="<?php echo $widgetUrl;?>";
	var saWidgetZoneId="<?php echo $zoneid; ?>";

	techjoomla.jQuery(document).ready(function()
	{
		/**Code to Populate Layout*/
		var txtSelectedValuesObj = document.getElementById("layout");
		txtSelectedValuesObj.value="";

		/**Code to Populate Layout*/
		txtSelectedValuesObj = saAdmin.zone.populatelayout();

		techjoomla.jQuery(".alphaCheck").keyup(function()
		{
			saAdmin.checkForAlpha(this,46);
		});
		techjoomla.jQuery(".alphaDecimalCheck").keyup(function()
		{
			saAdmin.checkForAlpha(this,46);
		});

		jQuery('input[name="jform[use_image_ratio]"]').change(function() {
			saAdmin.zone.useImageRatioToggle(this);
		});

		jQuery('input[name="jform[img_width_ratio]"]').change(function() {
			var value = jQuery(this).val();
			if (isNaN(saAdmin.zone.trim(value)) || (parseInt(value) == 0)) {
				document.getElementById("validate_img_width_ratio").innerHTML = Joomla.JText._('COM_SOCIALADS_VALIDATE_NON_ZERO_NUMERIC');
			} else {
				document.getElementById("validate_img_width_ratio").innerHTML = '';
			}
		});

		jQuery('input[name="jform[img_height_ratio]"]').change(function() {
			var value = jQuery(this).val();
			if (isNaN(saAdmin.zone.trim(value)) || (parseInt(value) == 0)) {
				document.getElementById("validate_img_height_ratio").innerHTML = Joomla.JText._('COM_SOCIALADS_VALIDATE_NON_ZERO_NUMERIC');
			} else {
				document.getElementById("validate_img_height_ratio").innerHTML = '';
			}
		});
	});

	techjoomla.jQuery('#adminForm select').attr('data-chosen', 'com_socialads');

	Joomla.submitbutton = function(task)
	{
		var isValid = saAdmin.zone.validateFields(task);

		if (isValid == true)
		{

			var atLeastOneIsChecked = false;

			techjoomla.jQuery('input:checkbox').each(function () {

				if (techjoomla.jQuery(this).is(':checked'))
				{
					atLeastOneIsChecked = true;
				}
			});

			if(atLeastOneIsChecked == false)
			{
				alert("<?php echo Text::_('COM_SOCIALADS_FORM_LBL_ZONE_LAYOUT_ALERT'); ?>");
				document.getElementById("validate_layout").innerHTML="<?php echo Text::_('COM_SOCIALADS_FORM_LBL_ZONE_LAYOUT_VALIDATION'); ?>";

				return false;
			}

			submitform( task );
		}
	}

	window.onload = function() 
	{
		autoFill();
		saAdmin.zone.useImageRatioToggle(jQuery('input[name="jform[use_image_ratio]"]'));
		jQuery("#jform_ad_type").change(autoFill);
		if(<?php echo $zoneid; ?>)
		{
			//console.log(here);
			saAdmin.zone.codechanger("widget");
		}
		techjoomla.jQuery("#widget :input").bind("keyup change click", function() {
			if(techjoomla.jQuery(this).attr("id") != "wid_code")
			saAdmin.zone.codechanger("widget");
		});
		techjoomla.jQuery("#field_target :input").bind("keyup change", function()
		{
			if(techjoomla.jQuery(this).attr("id") != "wid_code")
			saAdmin.zone.codechanger("target");
		});

		function autoFill()
		{
			//alert("yes");
			var selectedadd = document.getElementById("jform_ad_type").value;
			saAdmin.zone.zoneAdTypes(selectedadd);
			var selectedzone = "&zonelayout=" + layoutName;
			var url = "?option=com_socialads&task=zone.getSelectedLayouts&addtype="+selectedadd+selectedzone;
			techjoomla.jQuery.ajax({
						type: "get",
						url:url,
						success: function(response)
						{
							var d = document.getElementById("layout_ad_ajax");
							var olddiv = document.getElementById("layout_ad1");
							d.removeChild(olddiv);
							console.log(response)
							document.getElementById("layout_ad_ajax").innerHTML="<div id=layout_ad1></div>"+response;
						},
						error: function(response)
						{
							techjoomla.jQuery('#'+stepId+'-error').show('slow');
							// show ckout error msg
							console.log(' ERROR!!' );
							return e.preventDefault();
						}
					});
					techjoomla.jQuery( ".yes_no_toggle label" ).on( "click", function()
					{
						var radiovalue = saAdmin.zone.yesnoToggle(this);
					});
			saAdmin.zone.useImageRatioToggle(jQuery('input[name="jform[use_image_ratio]"]'));
		}
	}
</script>
<div class="<?php echo SA_WRAPPER_CLASS;?> "id = "sa-zone">
<form action="<?php echo Route::_('index.php?option=com_socialads&layout=edit&id=' . (int) $this->item->id); ?>" method="post" enctype="multipart/form-data" name="adminForm" id="adminForm" class="form-validate">
	<div class="form-horizontal">
	<ul class="nav nav-tabs">
			<li class="active"><a href="#tab1" data-toggle="tab"><b><?php echo Text::_('COM_SOCIALADS_TITLE_ZONE_BASIC');?></b></a></li>
			<li><a href="#tab2" data-toggle="tab"><b><?php echo Text::_('COM_SOCIALADS_TITLE_ZONE_PRICING');?></b></a></li>
			<?php if($zoneid){ ?>
			<li><a href="#tab3" data-toggle="tab"><b><?php echo Text::sprintf('COM_SOCIALADS_TITLE_ZONE_AD_WIDGET');?></b></a></li>
			<?php } ?>
		</ul>
		<div class="row-fluid">
			<div class="span10 form-horizontal">
				<div class="tab-content">
				<div class="tab-pane active" id="tab1">
				<fieldset class="adminform">

					<input type="hidden" name="jform[id]" value="<?php echo $this->item->id; ?>" />
					<input type="hidden" name="jform[ordering]" value="<?php echo $this->item->ordering; ?>" />
					<input type="hidden" name="jform[state]" value="<?php echo $this->item->state; ?>" />
					<input type="hidden" name="jform[checked_out]" value="<?php echo $this->item->checked_out; ?>" />
					<input type="hidden" name="jform[checked_out_time]" value="<?php echo $this->item->checked_out_time; ?>" />

					<?php if(empty($this->item->created_by))
								{ ?>
									<input type="hidden" name="jform[created_by]" value="<?php echo Factory::getUser()->id; ?>" />
							<?php }
						else
							{ ?>
								<input type="hidden" name="jform[created_by]" value="<?php echo $this->item->created_by; ?>" />
						<?php } ?>
					<div class="control-group">
						<div class="control-label"><?php echo $this->form->getLabel('zone_name'); ?></div>
						<div class="controls"><?php echo $this->form->getInput('zone_name'); ?></div>
					</div>

					<div class="control-group">
					<div class="control-label"><?php echo $this->form->getLabel('ad_type'); ?></div>
					<div class="controls">
					<?php
					$params=ComponentHelper::getParams('com_socialads');
					$allowed_type = $params->get('ad_type_allowed', "text_media", 'STRING');
					$allowed_type= (array) $allowed_type;
					$add_type = array();

					// In edit case display the ad_type already selected even if not in configuration
					if (isset($this->item) && isset($this->item->id))
					{
						$types = explode('||', $this->item->ad_type);
						foreach ($types as $type) {
							array_push($allowed_type, str_replace('|', '', $type));
						}
					}

					if(in_array('text_media',$allowed_type))
						$add_type[] = HTMLHelper::_('select.option','text_media', Text::_('COM_SOCIALADS_TITLE_ZONE_AD_TYPE_TEXT_AND_MEDIA'));
					if(in_array('text',$allowed_type))
						$add_type[] = HTMLHelper::_('select.option','text', Text::_('COM_SOCIALADS_TITLE_ZONE_AD_TYPE_TEXT'));
					if(in_array('media',$allowed_type))
						$add_type[] = HTMLHelper::_('select.option','media',Text::_('COM_SOCIALADS_TITLE_ZONE_AD_TYPE_MEDIA'));
					if(in_array('html5_zip',$allowed_type))
						$add_type[] = HTMLHelper::_('select.option','html5_zip',Text::_('COM_SOCIALADS_TITLE_ZONE_AD_HTML5'));

						echo HTMLHelper::_('select.genericlist', $add_type,'jform[ad_type]', 'class="inputbox" onchange=saAdmin.zone.zoneAdTypes(this.value);', 'value', 'text',$default_layout, 'jform_ad_type' );
						?>
					</div>
				</div>
				<div class="control-group">
					<div class="control-label"><?php echo $this->form->getLabel('orientation'); ?></div>
					<div class="controls"><?php echo $this->form->getInput('orientation'); ?></div>
				</div>
				<div class="control-group">
					<div class="control-label">
						<label id="jform_affiliate-lbl" for="jform_affiliate" class="hasPopover" title=""
						data-content="<?php echo Text::_('COM_SOCIALADS_FORM_DESC_ZONE_AFFILIATE_ADS');?>"
						data-original-title="<?php echo Text::_('COM_SOCIALADS_FORM_LBL_ZONE_AFFILIATE_ADS');?>">
							<?php echo Text::_('COM_SOCIALADS_FORM_LBL_ZONE_AFFILIATE_ADS');?>
						</label>
					</div>
						<?php
							$rawresult = str_replace('||',',',$this->item->ad_type ? $this->item->ad_type : '');
							$rawresult = str_replace('|','',$rawresult ? $rawresult : '');

						$zone_type = explode(",",$rawresult);
						$publish1=$publish2=$publish1_label=$publish2_label='';
						$publish2='checked="checked"';
						$publish2_label = 'btn-danger';
						if($this->item)
						{
								if(in_array('affiliate',$zone_type))
								{
									$publish1='checked="checked"';
									$publish1_label = 'btn-success';
									$publish2 = $publish2_label='';
								}
						}?>
					<div class="controls ">
							<div class="input-append yes_no_toggle">
							<input type="radio" class="inputbox sa_setting_radio" name="affiliate" id="affiliate1" value="1" <?php echo $publish1;?>  >
							<label class="first btn <?php echo $publish1_label;?>" type="button" for="affiliate1"><?php echo Text::_('JYES');?></label>
							<input type="radio" name="affiliate" id="affiliate0" value="0" <?php echo $publish2;?>  >
							<label class="last btn <?php echo $publish2_label;?>" type="button" for="affiliate0"><?php echo Text::_('JNO');?></label>
						</div>
					</div>
				</div>
				<?php
						if($this->item->id)
						{
							$default_layout=$this->item->orientation;
						}
						else
							$default_layout='text_media';
				?>
				<div class="control-group">
					<div class="control-label"><?php  echo $this->form->getLabel('use_image_ratio'); ?></div>
					<div class="controls"><?php echo $this->form->getInput('use_image_ratio'); ?></div>
				</div>

				<div class="control-group" id = "img_width">
					<div class="control-label"><?php  echo $this->form->getLabel('img_width'); ?></div>
					<div class="controls"><?php echo $this->form->getInput('img_width'); ?></div>
					<span id="validate_img_width" name="validate_img_width" class="invalid validate[numeric]"></span>
				</div>
				<div class="control-group" id = "img_height">
					<div class="control-label"><?php echo $this->form->getLabel('img_height'); ?></div>
					<div class="controls"><?php echo $this->form->getInput('img_height'); ?></div>
					<span id="validate_img_height" name="validate_img_height" class="invalid"></span>
				</div>

				<div class="control-group" id = "img_width_ratio">
					<div class="control-label"><?php  echo $this->form->getLabel('img_width_ratio'); ?></div>
					<div class="controls"><?php echo $this->form->getInput('img_width_ratio'); ?></div>
					<span id="validate_img_width_ratio" name="validate_img_width_ratio" class="invalid validate[numeric]"></span>
				</div>
				<div class="control-group" id = "img_height_ratio">
					<div class="control-label"><?php echo $this->form->getLabel('img_height_ratio'); ?></div>
					<div class="controls"><?php echo $this->form->getInput('img_height_ratio'); ?></div>
					<span id="validate_img_height_ratio" name="validate_img_height_ratio" class="invalid"></span>
				</div>

				<div class="control-group" id="max_title">
					<div class="control-label"><?php echo $this->form->getLabel('max_title'); ?></div>
					<div class="controls"><?php echo $this->form->getInput('max_title'); ?></div>
					<span id="validate_max_title" name="validate_max_title" class="invalid"></span>
				</div>
				<div class="control-group" id="max_des">
					<div class="control-label"><?php echo $this->form->getLabel('max_des'); ?></div>
					<div class="controls"><?php echo $this->form->getInput('max_des'); ?></div>
					<span id="validate_max_des" name="validate_max_des" class="invalid"></span>
				</div>
				<div class="control-group" id="layout_row">
					<div  class = "control-label">
						<label id="zoneLayout" for="zoneLayout" class="hasPopover" title=""
						data-content="<?php echo Text::_('COM_SOCIALADS_FORM_DESC_ZONE_LAYOUT');?>"
						data-original-title="<?php echo Text::_('COM_SOCIALADS_FORM_LBL_ZONE_LAYOUT');?>">
							<?php echo Text::_('COM_SOCIALADS_FORM_LBL_ZONE_LAYOUT');?>
						</label>
					</div>
					<div class = "controls">
					<input type="hidden" id="layout" name="ad_layout" value="<?php echo $this->item->layout;?>">
					<div id='layout_ad_ajax'>
					<div id='layout_ad1'>
					</div>
					</div>
					<span id="validate_layout" name="validate_layout" class="invalid"></span>
					</div>
				</div>
				</div>
				</fieldset>
				<div class="tab-pane" id="tab2">
				<fieldset>
					<?php
					$params = ComponentHelper::getParams('com_socialads');
					$params->get('zone_pricing');
					$pricing_opt = $params->get('pricing_options');
					$pricing_opt = (array) $pricing_opt;
					?>
					<div class="controls"><?php echo Text::_('COM_SOCIALADS_ZONE_PRICING_TOOLTIP'); ?> </div>
					<?php if($params->get('zone_pricing'))
					{ ?>
						<?php if(in_array('perclick', $pricing_opt)){ ?>
						<div class="control-group">
							<div class="control-label"><?php echo $this->form->getLabel('per_click'); ?></div>
							<div class="controls"><?php echo $this->form->getInput('per_click'); ?></div>
						</div>
					<?php }?>
					<?php if(in_array('perimpression', $pricing_opt)){ ?>
						<div class="control-group">
							<div class="control-label"><?php echo $this->form->getLabel('per_imp'); ?></div>
							<div class="controls"><?php echo $this->form->getInput('per_imp'); ?></div>
						</div>
					<?php }?>
					<?php if(in_array('perday', $pricing_opt)){ ?>
						<div class="control-group">
							<div class="control-label"><?php echo $this->form->getLabel('per_day'); ?></div>
							<div class="controls"><?php echo $this->form->getInput('per_day'); ?></div>
						</div>
						<?php }
					}?>
			</fieldset>
			</div>
			<!----------------------------WIDGET CODE START--------------------------------------->
		<div class="tab-pane" id="tab3">
		<fieldset>
		<div class="row-fluid">
		<div class="span6">
			<div class="tabbable tabs-left">
				<ul class="nav nav-pills">
					<li class="active" ><a href="#wtab1" data-toggle="tab"><?php echo Text::_('COM_SOCIALADS_ZONE_WIDGET_CUSTOM');?></a></li>
					<?php
					$params=ComponentHelper::getParams('com_socialads');
					if($params->get('social_integration')!='Joomla'){?>
					<li><a  href="#wtab2" data-toggle="tab"><?php echo Text::_('COM_SOCIALADS_ZONE_WIDGET_TARGET');?></a></li>
					<?php } ?>
				</ul>
				<div class="tab-content">
					<div class="tab-pane active" id="wtab1">
						<table id="widget" class="table table-bordered " cellspacing="8px">
							<tr>
								<td  width="25%"><?php echo JHtml::tooltip(JText::_('COM_SOCIALADS_ZONE_NUM_ADS_TOOLTIP'), JText::_('COM_SOCIALADS_ZONE_NUM_ADS'), '', JText::_('COM_SOCIALADS_ZONE_NUM_ADS'));?><span class="star">&nbsp;*</span></td>
								<td >
									<input type="text" name="num_ads" id="num_ads" class="inputbox input-small" size="10" value="2" autocomplete="off"
									onkeyup="saAdmin.checkForAlpha(this,46);"/>
								</td>
							</tr>
							<tr>
								<td  width="25%"><?php echo HTMLHelper::tooltip(Text::_('COM_SOCIALADS_ZONE_ROTATE_ADS_TOOLTIP'), Text::_('COM_SOCIALADS_ZONE_ROTATE_ADS'), '', Text::_('COM_SOCIALADS_ZONE_ROTATE_ADS'));?></td>
								<td >
									<div class="input-append yes_no_toggle">
										<input type="radio" name="rotate" class = "inputbox sa_setting_radio" id="publish1" value="1"  >
										<label class="first btn " type="button" for="publish1"><?php echo Text::_('JYES');?></label>
										<input type="radio" name="rotate" id="publish2" value="0" checked="checked" >
										<label class="last btn btn-danger" type="button" for="publish2"><?php echo Text::_('JNO');?></label>
									</div>
								</td>
							</tr>
							<tr>
								<td  width="25%"><?php echo HTMLHelper::tooltip(Text::_('COM_SOCIALADS_ZONE_ROTATE_ADS_DELAY_TOOLTIP'), Text::_('COM_SOCIALADS_ZONE_ROTATE_ADS_DELAY'), '', Text::_('COM_SOCIALADS_ZONE_ROTATE_ADS_DELAY'));?></td>
								<td >
									<input type="text" name="rotate_delay" id="rotate_delay" class="inputbox input-small" size="10" value="10" autocomplete="off"
									onkeyup="saAdmin.checkForAlpha(this,46);" />
								</td>
							</tr>
							<tr>
								<td  width="25%"><?php echo HTMLHelper::tooltip(Text::_('COM_SOCIALADS_ZONE_RAND_ADS_TOOLTIP'), Text::_('COM_SOCIALADS_ZONE_RAND_ADS'), '', Text::_('COM_SOCIALADS_ZONE_RAND_ADS'));?></td>
								<td >
									<div class="input-append yes_no_toggle">
										<input type="radio" name="rand" id="rand1" value="1"  >
										<label class="first btn" type="button" for="rand1"><?php echo Text::_('JYES');?></label>
										<input type="radio" name="rand" id="rand2" value="0" checked="checked" >
										<label class="last btn btn-danger" type="button" for="rand2"><?php echo Text::_('JNO');?></label>
									</div>
								</td>
							</tr>
							<tr>
								<td  width="25%"><?php echo HTMLHelper::tooltip(Text::_('COM_SOCIALADS_ZONE_IFWID_ADS_TOOLTIP'), Text::_('COM_SOCIALADS_ZONE_IFWID_ADS'), '', Text::_('COM_SOCIALADS_ZONE_IFWID_ADS'));?></td>
								<td >
									<div class="input-append">
										<input type="text" name="if_wid" id="if_wid" class="inputbox input-mini" size="10" value="" placeholder="<?php echo Text::_('COM_SOCIALADS_IF_WID_HOLDER');?>" autocomplete="off" />
										<span class="add-on">px</span>
									</div>
								</td>
							</tr>
							<tr>
								<td  width="25%"><?php echo HTMLHelper::tooltip(Text::_('COM_SOCIALADS_ZONE_IFHT_ADS_TOOLTIP'), Text::_('COM_SOCIALADS_ZONE_IFHT_ADS'), '', Text::_('COM_SOCIALADS_ZONE_IFHT_ADS'));?></td>
								<td >
									<div class="input-append">
									<input type="text" name="if_ht" id="if_ht" class="inputbox input-mini" placeholder="<?php echo Text::_('COM_SOCIALADS_IF_HT_HOLDER');?>" size="10" value="" autocomplete="off" />
										<span class="add-on">px</span>
									</div>
								</td>
							</tr>
							<tr>
								<td  width="25%"><?php echo HTMLHelper::tooltip(Text::_('COM_SOCIALADS_ZONE_IF_SEAMLS_ADS_TOOLTIP'), Text::_('COM_SOCIALADS_ZONE_IF_SEAMLS_ADS'), '', Text::_('COM_SOCIALADS_ZONE_IF_SEAMLS_ADS'));?></td>
								<td >
									<div class="input-append yes_no_toggle">
										<input type="radio" name="if_seam" id="if_seam1" value="1"  checked="checked"  >
										<label class="first btn btn-success" type="button" for="if_seam1"><?php echo Text::_('JYES');?></label>
										<input type="radio" name="if_seam" id="if_seam2" value="0">
										<label class="last btn" type="button" for="if_seam2"><?php echo Text::_('JNO');?></label>
									</div>
								</td>
							</tr>
						</table>

					</div>
					<?php if($params->get('social_integration') !='Joomla'){ ?>
					<div class="tab-pane active" id="wtab2">
					<?php
						if(!empty($this->fields)){ ?>
							<!-- field_target starts here -->
							<div id="field_target">
								<!-- floatmain starts here -->
								<div id="floatmain" >
									<div id="mapping-field-table">
								<!--for loop which shows JS fields with select types-->
								<table class="table table-bordered widget" cellspacing="8px">
									<?php
									if($params->get('social_integration') == "Community Builder")
									{
										// require(JPATH_SITE . "/components/com_comprofiler/plugin/language/default_language/default_language.php");
										global $_CB_framework, $_CB_database, $ueConfig, $mainframe;
										include_once( JPATH_ADMINISTRATOR . '/components/com_comprofiler/plugin.foundation.php' );
									}
									$i=1;
									foreach($this->fields as $key => $field)
									{
										if($field->mapping_fieldtype!='targeting_plugin')
										{ ?>
										<tr>
											<td>
											<div class="control-group">
												<label class="ad-fields-lable "><?php
													if($params->get('social_integration') == 'Community Builder')
													{
														$field->mapping_label = htmlspecialchars( getLangDefinition( $field->mapping_label));
													}
													else
													{
														$field->mapping_label = Text::_("$field->mapping_label");
													}

													echo $field->mapping_label;?>
												</label>
											</td>
											<td>
												<div class="controls">

												   <!--Numeric Range-->
													<?php
													//for easysocial fileds of those app are created..(gender,boolean and address)

													if($field->mapping_fieldtype=="gender")
													{
														$gender[] = HTMLHelper::_('select.option','', Text::_("SELECT"));
														$gender[] = HTMLHelper::_('select.option','2', Text::_("FEMALE"));
														$gender[] = HTMLHelper::_('select.option','1', Text::_("MALE"));
														echo HTMLHelper::_('select.genericlist', $gender, 'mapdata[][' . $field->mapping_fieldname . ',select]', ' class="sa-fields-inputbox" id="mapdata[][' . $field->mapping_fieldname.',select]" size="1"',   'value', 'text', $flds[$field->mapping_fieldname.',select']);
													}
													if($field->mapping_fieldtype=="boolean")
													{
														$boolean[] = HTMLHelper::_('select.option','', Text::_("SELECT"));
														$boolean[] = HTMLHelper::_('select.option','1', Text::_("YES"));
														$boolean[] = HTMLHelper::_('select.option','0', Text::_("NO"));
														echo HTMLHelper::_('select.genericlist', $boolean, 'mapdata[][' . $field->mapping_fieldname.',select]', ' class="sa-fields-inputbox" id="mapdata[][' . $field->mapping_fieldname.',select]" size="1"',   'value', 'text', $flds[$field->mapping_fieldname.',select']);
													}
													/*
													if($fields->mapping_fieldtype=="address")
													{

													}
													*/
													if($field->mapping_fieldtype=="numericrange")
													{
														$lowvar = $field->mapping_fieldname.'_low';
														$highvar = $field->mapping_fieldname.'_high';
														$onkeyup = " ";

														if (isset($flds[$field->mapping_fieldname.'_low']) || isset($this->addata_for_adsumary_edit->$lowvar))
														{
															$grad_low=0;
															$grad_high=2030;
															if($zoneid)
															{
																if(strcmp($this->addata_for_adsumary_edit->$lowvar,$grad_low)==0)
																{
																		$this->addata_for_adsumary_edit->$lowvar = '';
																}
																if((strcmp($this->addata_for_adsumary_edit->$highvar, $grad_high)==0) || (strcmp($this->addata_for_adsumary_edit->$highvar,$grad_low)==0))
																{
																	$this->addata_for_adsumary_edit->$highvar = '';
																}		?>
																<input type="textbox"  class="sa-fields-inputbox" name="mapdata[][<?php echo $field->mapping_fieldname.'_low|numericrange|0'; ?>]" value="<?php echo $this->addata_for_adsumary_edit->$lowvar; ?>" />
																<?php echo Text::_('SA_TO'); ?>
																<input type="textbox" class="sa-fields-inputbox" name="mapdata[][<?php echo $field->mapping_fieldname.'_high|numericrange|1'; ?>]" value="<?php echo $this->addata_for_adsumary_edit->$highvar?>" />
															<?php
															}
															else
															{
																$onkeyup="  Onkeyup = saAdmin.checkForAlpha(this,46)";

																if (strcmp($flds[$field->mapping_fieldname.'_low'], $grad_low) == 0)
																{
																	$flds[$field->mapping_fieldname.'_low'] = '';
																}

																if ((strcmp($flds[$field->mapping_fieldname.'_high'],$grad_high)==0)|| (strcmp($flds[$field->mapping_fieldname.'_high'],$grad_high)==0))
																{
																	$flds[$field->mapping_fieldname.'_high'] = '';
																} ?>
																<input type="textbox"  class="sa-fields-inputbox" name="mapdata[][<?php echo $field->mapping_fieldname.'_low|numericrange|0'; ?>]" value="<?php echo $flds[$field->mapping_fieldname.'_low']?>"
																	Onkeyup = "saAdmin.checkForAlpha(this,46);" />
																	<?php echo Text::_('SA_TO'); ?>
																	<input type="textbox" class="sa-fields-inputbox" name="mapdata[][<?php echo $field->mapping_fieldname.'_high|numericrange|1'; ?>]" value="<?php echo $flds[$field->mapping_fieldname.'_high']?>"  Onkeyup = "saAdmin.checkForAlpha(this,46);" />
															<?php
															} ?>
														<?php
														}
														else
														{ ?>
															<input type="textbox"  class="sa-fields-inputbox" name="mapdata[][<?php echo $field->mapping_fieldname.'_low|numericrange|0'; ?>]" value="" <?php echo $onkeyup; ?> />
															<?php echo Text::_('SA_TO'); ?>
															<input type="textbox" class="sa-fields-inputbox" name="mapdata[][<?php echo $field->mapping_fieldname.'_high|numericrange|1'; ?>]" value=""<?php echo $onkeyup; ?> />
														<?php
														}
													} ?>
													<!--Freetext-->
													<?php if($field->mapping_fieldtype=="textbox")
													{
														$textvar = $field->mapping_fieldname;

														if (isset($flds[$field->mapping_fieldname]) || isset($this->addata_for_adsumary_edit->$textvar))
														{
															if ($zoneid)
															{
															?>
																<input type="textbox" class="sa-fields-inputbox" name="mapdata[][<?php  echo $field->mapping_fieldname; ?>]" value="<?php echo $this->addata_for_adsumary_edit->$textvar; ?>"  />
															<?php
															}
															else
															{ ?>
																<input type="textbox" class="sa-fields-inputbox" name="mapdata[][<?php echo $field->mapping_fieldname; ?>]" value="<?php echo $flds[$field->mapping_fieldname]; ?>" />
															<?php
															}
														}
														else
														{?>
															<input type="textbox" class="sa-fields-inputbox" name="mapdata[][<?php echo $field->mapping_fieldname; ?>]" value="" />
														<?php
														}
													}?>
													<!--Single Select-->
													<?php
														if($field->mapping_fieldtype=="singleselect")
														{
															$singlevar = $field->mapping_fieldname;

															if (isset($flds[$field->mapping_fieldname.',select']) || isset($this->addata_for_adsumary_edit->$singlevar))
															{
																$singleselect = $field->mapping_options;
																$singleselect = explode("\n",$singleselect);

																for ($count = 0;$count < count($singleselect); $count++)
																{
																	$options[] = HTMLHelper::_('select.option',$singleselect[$count],Text::_($singleselect[$count]),'value','text');
																}

																$s = array();
																$s[0]->value = '';
																$s[0]->text = Text::_('COM_SOCIALADS_AD_TARGET_SINGSELECT');
																$options = array_merge($s, $options);

																if ($zoneid)
																{
																	$mdata = str_replace('||', ',', $this->addata_for_adsumary_edit->$singlevar);
																	$mdata = str_replace('|', '', $mdata);
																	echo HTMLHelper::_('select.genericlist', $options, 'mapdata[][' . $field->mapping_fieldname . ',select]', 'class="sa-fields-inputbox" size="1" ' . $display_reach, 'value', 'text', $mdata);
																}
																else
																{
																	echo HTMLHelper::_('select.genericlist', $options, 'mapdata[]['.$field->mapping_fieldname.',select]', ' class="sa-fields-inputbox"'.$display_reach.' id="mapdata[]['.$field->mapping_fieldname.',select]" size="1"',   'value', 'text', $flds[$field->mapping_fieldname.',select']);
																}

																$options= array();
															}
															else
															{
																$singleselect = $field->mapping_options;
																$singleselect = explode("\n", $singleselect);

																for($count = 0;$count<count($singleselect); $count++)
																{
																	$options[] = HTMLHelper::_('select.option', $singleselect[$count], Text::_($singleselect[$count]),'value','text');
																}

																$s = array();
																$s[0] = new stdClass;
																$s[0]->value = '';
																$s[0]->text = Text::_('COM_SOCIALADS_AD_TARGET_SINGSELECT');
																$options = array_merge($s, $options);

																echo HTMLHelper::_('select.genericlist', $options, 'mapdata[][' . $field->mapping_fieldname . ',select]', 'class="sa-fields-inputbox"  id="mapdata[][' . $field->mapping_fieldname . ',select]" size="1"',   'value', 'text', '');
																$options= array();
															}
														}
														// Multiselect
														if ($field->mapping_fieldtype=="multiselect" )
														{
															$multivar = $field->mapping_fieldname;
															$options= array();

															$multivar = $field->mapping_fieldname;
														$options= array();
														if (isset($flds[$field->mapping_fieldname.',select']) || isset($this->addata_for_adsumary_edit->$multivar))
															{
																$multiselect = $field->mapping_options;
																$multiselect = explode("\n",$multiselect);
																if($this->edit_ad_adsumary)
																{
																	$mdata = str_replace('||',',',$this->addata_for_adsumary_edit->$multivar);
																	$mdata = str_replace('|','',$mdata);
																	$multidata = explode(",",$mdata);
																	//print_r($multidata);
																}
																	for($cnt=0;$cnt<count($multiselect); $cnt++)
																	{

																		$options[] = HTMLHelper::_('select.option',$multiselect[$cnt], Text::_($multiselect[$cnt]),'value','text');
																	}

																	if($cnt > 20)
																	{
																		$size = '6';
																	}
																	else
																	{
																		$size = '3';
																	}

																	echo HTMLHelper::_('select.genericlist', $options, 'mapdata[]['.$field->mapping_fieldname.',select]', 'class="sa-fields-inputbox inputbox chzn-done" id="mapdata[]['.$field->mapping_fieldname.',select]" size="'.$size.'"  multiple="multiple" ',   'value', 'text', $multidata);
																	$options= array();
															}
															else
															{
																$multiselect = $field->mapping_options;
																$multiselect = explode("\n",$multiselect);
																for($cnt=0;$cnt<count($multiselect); $cnt++)
																{

																		$options[] = HTMLHelper::_('select.option',$multiselect[$cnt], Text::_($multiselect[$cnt]),'value','text');

																}

																if($cnt > 20)
																{	$size = '6';}
																else
																	$size = '3';
																echo HTMLHelper::_('select.genericlist', $options, 'mapdata[]['.$field->mapping_fieldname.',select]', 'class="sa-fields-inputbox  inputbox chzn-done"  size="'.$size.'" id="mapdata[]['.$field->mapping_fieldname.',select]" multiple="multiple"',   'value', 'text', '');

																$options= array();
															}
														}
														 //daterange
														if($field->mapping_fieldtype=="daterange")
														{
															$this->datelowvar  = $field->mapping_fieldname . '_low';
															$this->datehighvar = $field->mapping_fieldname . '_high';

															if (isset($flds[$field->mapping_fieldname . '_low']) || isset($this->addata_for_adsumary_edit->$this->datelowvar))
															{
																$date_low  = date('Y-m-d 00:00:00', mktime(0, 0, 0, 01, 1, 1910));
																$date_high = date('Y-m-d 00:00:00', mktime(0, 0, 0, 01, 1, 2030));

																if ($zoneid)
																{
																	if (strcmp($this->addata_for_adsumary_edit->$this->datelowvar, $date_low) == 0)
																	{
																		$this->addata_for_adsumary_edit->$this->datelowvar = '';
																	}

																	if (strcmp($this->addata_for_adsumary_edit->$this->datehighvar, $date_high) == 0)
																	{
																		$this->addata_for_adsumary_edit->$this->datehighvar = '';
																	}

																	echo HTMLHelper::_('calendar', $this->addata_for_adsumary_edit->$this->datelowvar, 'mapdata[][' . $field->mapping_fieldname . '_low|daterange|0]', 'mapdata[' . $key . '][' .$field->mapping_fieldname. '][' . $field->mapping_fieldname . '_low]', '%Y-%m-%d', array('class' => 'sa-fields-inputbox input-small'));

																	echo Text::_('COM_SOCIALADS_TO');

																	echo HTMLHelper::_('calendar', $this->addata_for_adsumary_edit->$this->datehighvar, 'mapdata[][' . $field->mapping_fieldname . '_high|daterange|1]', 'mapdata[' . $key . '][' .$field->mapping_fieldname.'][' . $field->mapping_fieldname . '_high]', '%Y-%m-%d', array('class' => 'sa-fields-inputbox input-small'));
																}
																else
																{
																	if (strcmp($flds[$field->mapping_fieldname . '_low'], $date_low) == 0)
																	{
																		$flds[$field->mapping_fieldname . '_low'] = '';
																	}

																	if (strcmp($flds[$field->mapping_fieldname . '_high'], $date_high) == 0)
																	{
																		$flds[$field->mapping_fieldname . '_high'] = '';
																	}

																	echo HTMLHelper::_('calendar', $flds[$field->mapping_fieldname . '_low'], 'mapdata[][' . $field->mapping_fieldname . '_low]', 'mapdata[' . $key . '][' .$field->mapping_fieldname. '][' . $field->mapping_fieldname . '_low]', '%Y-%m-%d', array('class' => 'sa-fields-inputbox input-small'));
																	echo Text::_('COM_SOCIALADS_TO');
																	echo HTMLHelper::_('calendar', $flds[$field->mapping_fieldname . '_high'], 'mapdata[][' . $field->mapping_fieldname . '_high]', 'mapdata[' . $key . '][' .$field->mapping_fieldname. '][' . $field->mapping_fieldname . '_high]', '%Y-%m-%d', array('class' => 'sa-fields-inputbox input-small'));
																}
															}
														else
														{
															if ($zoneid)
															{
																echo HTMLHelper::_('calendar', '', 'mapdata[][' . $field->mapping_fieldname . '_low|daterange|0]', 'mapdata[' . $key . '][' .$field->mapping_fieldname. '][' . $field->mapping_fieldname . '_low]', '%Y-%m-%d', array('class' => 'sa-fields-inputbox','onchange' => 'calculateReach()'));
																echo Text::_('COM_SOCIALADS_TO');
																echo HTMLHelper::_('calendar', '', 'mapdata[][' . $field->mapping_fieldname . '_high|daterange|1]', 'mapdata[' . $key . '][' .$field->mapping_fieldname. '][' . $field->mapping_fieldname . '_high]', '%Y-%m-%d', array('class' => 'sa-fields-inputbox'));
															}
															else
															{
																echo HTMLHelper::_('calendar', '', 'mapdata[][' . $field->mapping_fieldname . '_low|daterange|0]', 'mapdata[' . $key . ']['.$field->mapping_fieldname. '][' . $field->mapping_fieldname . '_low]', '%Y-%m-%d', array('class' => 'sa-fields-inputbox'));
																echo Text::_('COM_SOCIALADS_TO');
																echo HTMLHelper::_('calendar', '', 'mapdata[][' . $field->mapping_fieldname . '_high|daterange|1]', 'mapdata[' . $key . ']['.$field->mapping_fieldname.'][' . $field->mapping_fieldname . '_high]', '%Y-%m-%d', array('class' => 'sa-fields-inputbox'));
															}
														}

														if ($this->datelowvar == null)
														{
															$this->datelow = $field->mapping_fieldname;
														}
														else
														{
															$this->datelowvar .= ',' . $field->mapping_fieldname;
														}

													}

													 //date
															if($field->mapping_fieldtype=="date")
															{
																$datevar = $field->mapping_fieldname;
																if(isset($flds[$field->mapping_fieldname]) || isset($this->addata_for_adsumary_edit->$datevar))
																{
																	if($zoneid)
																	{
																		echo HTMLHelper::_('calendar', $this->addata_for_adsumary_edit->$datevar , 'mapdata[]['.$field->mapping_fieldname.']', 'mapdata[' . $key . ']['.$field->mapping_fieldname.']','%Y-%m-%d', array('class'=>'sa-fields-inputbox'));
																	}
																	else
																	{
																		echo HTMLHelper::_('calendar', $flds[$field->mapping_fieldname] , 'mapdata[]['.$field->mapping_fieldname.']',
																		'mapdata[' . $key . ']['.$field->mapping_fieldname.']','%Y-%m-%d', array('class'=>'sa-fields-inputbox'));
																	}
																}
																else
																{
																	echo HTMLHelper::_('calendar', '', 'mapdata[]['.$field->mapping_fieldname.']', 'mapdata[' . $key . ']['.$field->mapping_fieldname.']','%Y-%m-%d', array('class'=>'sa-fields-inputbox'));
																} ?>
												  <?php 	}?>

												</div>
											</div>
										</td>
									</tr>
								 <?php
											$i++;
										}
									} ?>
								</table>

										<div style="clear:both"></div>
									</div>
								</div><!-- End fo floatmain div -->
							</div><!-- End fo field_target div -->
							<?php }//end for fields not empty condition
							?>
					</div>
					<?php } ?>
				</div>
			</div>
		</div>
		<div class="span6">
			<div class="well">
				<label><?php echo Text::_('COM_SOCIALADS_WIDGET_CODE');?></label>
				<?php
				$widgetCode = "<script>\n var Ad_widget_sitebase = '" . Uri::root() . "';\n";
				$widgetCode .= "</"."script>";
				?>
				<textarea id="wid_code" rows="5" cols="80" onclick="this.select()" spellcheck="false" style="width: 100% !important;"><?php echo $widgetCode;
				  ?></textarea>
				<label><?php echo Text::_('COM_SOCIALADS_WIDGETUNIT_CODE');?></label>
				<textarea id="widunit_code" rows="15" cols="80" onclick="this.select()" spellcheck="false" style="width: 100% !important;"></textarea>
			</div>
		</div>
		</div>
		</fieldset>
		</div>
		</div>
		<?php //} ?>

			<!-----------------WIDGET CODE END------------------------------->
		</div>
	</div>
	<?php
	if (JVERSION >= '3.0')
	{
		echo HTMLHelper::_('bootstrap.endTab');
		echo HTMLHelper::_('bootstrap.endTabSet');
	}
	?>
	<input type="hidden" name="task" value="" />
	<?php echo HTMLHelper::_('form.token'); ?>

</div>
</form>
</div>
