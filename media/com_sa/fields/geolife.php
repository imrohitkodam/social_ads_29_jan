<?php
/**
 * @version    SVN:<SVN_ID>
 * @package    SocialAds
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2015 TechJoomla. All rights reserved
 * @license    GNU General Public License version 2, or later
 */

// No direct access
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\Form\FormField;

use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Language\Text;

/**
 * Class for custom geolife element
 *
 * @since  1.0.0
 */
class JFormFieldGeolife extends JFormField
{
	protected $type = 'Geolife';

	/**
	 * Function to genarate html of custom element
	 *
	 * @return  string  html
	 *
	 * @since  1.0.0
	 */
	public function getInput()
	{
		$html              = '<div class="span9">';
		$maxmindDbFilePath = JPATH_PLUGINS . '/system/tjmaxmind/db/GeoLite2-City.mmdb';

		if (File::exists($maxmindDbFilePath))
		{
			$html .= '<div class="alert alert-success"><span class="icon-save"></span>' . Text::_('COM_SOCIALADS_GEOLITECITY_INSTALLED') . '</div>';
		}
		else
		{
			$html .= '<div class="alert alert-error"><span class="icon-cancel"></span>'
			. Text::_('COM_SOCIALADS_GEOLITECITY_INSTALLATION_1')
			. '</div><div class="alert alert-info geo_target_instructions mt-0">'
			. Text::_('COM_SOCIALADS_GEOLITECITY_INSTALLATION_2')
			. ' <a target="_blank" href="https://www.maxmind.com/"> '
			. Text::_('COM_SOCIALADS_GEOLITECITY_CLICK_HERE')
			. '</a><br>'
			. Text::_('COM_SOCIALADS_GEOLITECITY_INSTALLATION_3')
			. '<br>'
			. Text::_('COM_SOCIALADS_GEOLITECITY_INSTALLATION_4')
			. '<br>'
			. Text::_('COM_SOCIALADS_GEOLITECITY_INSTALLATION_5')
			. '<br>'
			. Text::_('COM_SOCIALADS_GEOLITECITY_INSTALLATION_6')
			. '</div>';
		}

		// Condition to check if mbstring is enabled
		if (!function_exists('mb_convert_encoding'))
		{
			$html .= '<div class="alert alert-error">' . Text::_('COM_SOCIALADS_MB_EXT') . '</div>';
		}

		$html .= '</div>';

		return $html;
	}
}
