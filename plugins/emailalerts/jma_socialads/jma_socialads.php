<?php
/**
 * @package     JMailAlerts
 * @subpackage  jma_socialads
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2024 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later
 */

// Do not allow direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Router\Route;

$jmailAlertsPluginPath    = JPATH_SITE . '/components/com_jmailalerts/helpers/plugins.php';
$jmaIntegrationHelperPath = JPATH_SITE . '/plugins/system/plg_sys_jma_integration/plg_sys_jma_integration/plugins.php';

// Include plugin helper file
// Else condition is needed when JMA integration plugin is used on sites where JMA is not installed
if (File::exists($jmailAlertsPluginPath))
{
	include_once $jmailAlertsPluginPath;
}
elseif (File::exists($jmaIntegrationHelperPath))
{
	include_once $jmaIntegrationHelperPath;
}

/**
 * Class for JMA SA plugin
 *
 * @since  2.4.4
 */
class PlgEmailalertsjma_Socialads extends JMailAlertsPlugin
{
	public $extension = 'com_socialads';

	/**
	 * Plugin trigger to get latest matching records
	 *
	 * @param   string  $id               Userid or email id for user whom email will be sent
	 * @param   string  $lastEmailDate    Timestamp when last email was sent to that user
	 * @param   array   $userParams       Array of user's alert preference considering data tags
	 * @param   int     $fetchOnlyLatest  Decide to send only fresh content or not
	 *
	 * @return  array
	 *
	 * @since  2.5.0
	 */
	public function onEmail_jma_socialads($id, $lastEmailDate, $userParams, $fetchOnlyLatest)
	{
		// Installation check
		$this->checkExtensionExists($this->extension);

		if (!$this->parentExtensionExists)
		{
			return $this->returnArray;
		}

		$userParams['alt_ad']      = 1;
		$userParams['ad_rotation'] = 0;

		$input                    = Factory::getApplication()->input;
		require_once JPATH_ROOT . '/components/com_socialads/helpers/engine.php';
		require_once JPATH_ROOT . '/components/com_socialads/helpers/common.php';

		$html     = '<span>';
		$css      = '';
		$simulate = '';
		$sim_flag = $input->get('flag', 0, 'INT');

		// To check if called from simulate in admin
		if ($sim_flag == 1)
		{
			$simulate = '&amp;simulate=1';
		}

		$adsdata = array();
		$adsdata = SaAdEngineHelper::getInstance()->fillslots($userParams);

		if (!empty($adsdata))
		{
			// $random_ads = $adRetriever->getRandomId($adsdata,$userParams);
			$itemid = SaCommonHelper::getSocialadsItemid('adform');

			foreach ($adsdata as $key => $random_ad1)
			{
				foreach ($random_ad1 as $key => $random_ad)
				{
					if ($random_ad->ad_id != -999)
					{
						$addata = SaAdEngineHelper::getInstance()->getAdDetails($random_ad);
					}
					else
					{
						$addata = null;
					}

					if ($addata)
					{
						$html .= '<div>';
							$html .= SaAdEngineHelper::getInstance()->getAdHTML($addata);
								$html .= '<img alt="" src="' . Uri::root() . 'index.php?option=com_socialads&amp;task=getTransparentImage&amp;adid='
										. $random_ad->ad_id . $simulate . '"  border="0"  width="1" height="1"/>';
						$html .= '</div>';

						$cssfile = JPATH_SITE . '/plugins/socialadslayout/plug_' . $addata->layout . '/plug_' . $addata->layout . '/layout.css';

						$css .= file_get_contents($cssfile);
					}
				}
			}

			if ($userParams['create'] == 1)
			{
				$html .= '<div style="clear:both;"></div><a class ="create" target="_blank" href="'
				. Route::_(Uri::root() . 'index.php?option=com_socialads&view=adform&Itemid=' . $itemid, false)
				. '">' . $userParams['create_text'] . '</a>';
			}
		}

		$html .= '</span>';

		if (!empty($adsdata))
		{
			$this->returnArray[1] = $html;
			$this->returnArray[2] = $css;
		}

		return $this->returnArray;
	}
}
