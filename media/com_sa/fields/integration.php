<?php
/**
 * @version    SVN: <svn_id>
 * @package    Com_Tjlms
 * @copyright  Copyright (C) 2005 - 2014. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * Shika is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */

// No direct access
defined('_JEXEC') or die;

use Joomla\CMS\Form\FormField;
use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;

/**
 * Class for custom Integration element
 *
 * @since  1.0.0
 */
class JFormFieldIntegration extends JFormField
{
	/**
	 * Function to genarate html of custom element
	 *
	 * @return  HTML
	 *
	 * @since  1.0.0
	 */
	public function getInput()
	{
		if (empty($this->options))
		{
			return $this->fetchElement($this->name, $this->value, $this->element, '');
		}
		else
		{
			return $this->fetchElement($this->name, $this->value, $this->element, $this->options['controls']);
		}
	}

	/**
	 * Function to genarate html of custom element
	 *
	 * @param   STRING  $name          Name of the element
	 * @param   STRING  $value         Default value of the element
	 * @param   STRING  $node          asa
	 * @param   STRING  $control_name  asda
	 *
	 * @return  HTML
	 *
	 * @since  1.0.0
	 */
	public function fetchElement($name, $value, $node, $control_name)
	{
		$communityfolder = JPATH_SITE . '/components/com_community';
		$esfolder = JPATH_SITE . '/components/com_easysocial';
		$cbfolder = JPATH_SITE . '/components/com_comprofiler';
		$jsString =	"<script>
					function checkIfExtInstalled(selectBoxName, extention)
					{
						var flag = 0;
						if (extention == 'JomSocial')
						{
							";

								if (!Folder::exists($communityfolder))
								{
									$jsString .= " flag = 1";
								}

							$jsString .= "
						}
						else if (extention == 'EasySocial')
						{
							";

								if (!Folder::exists($esfolder))
								{
									$jsString .= " flag = 1";
								}

							$jsString .= "
						}
						else if (extention == 'Community Builder')
						{
							";

								if (!Folder::exists($cbfolder))
								{
									$jsString .= " flag = 1";
								}

							$jsString .= "
						}

						if (flag == 1)
						{
								var extentionName = jQuery('#jformsocial_integration').val();
								alert(extentionName+' not installed');
								jQuery('#jformsocial_integration').val('Joomla');
								jQuery('select').trigger('liszt:updated');
						}
					}

				</script>";
		echo   $jsString;

		$options[] = HTMLHelper::_('select.option', 'Joomla', Text::_('COM_SOCIALADS_FORM_NONE'));
		$options[] = HTMLHelper::_('select.option', 'JomSocial', Text::_('COM_SOCIALADS_FORM_JS'));
		$options[] = HTMLHelper::_('select.option', 'EasySocial', Text::_('COM_SOCIALADS_FORM_ES'));
		$options[] = HTMLHelper::_('select.option', 'Community Builder', Text::_('COM_SOCIALADS_FORM_CB'));

		$fieldName = $name;

		return HTMLHelper::_('select.genericlist',
											$options, $fieldName,
						'class="inputbox form-select btn-group" onchange="checkIfExtInstalled(this.name, this.value)" ',
						'value', 'text', $value, $control_name . $name
						);
	}
}
