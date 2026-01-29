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

use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;


include_once JPATH_SITE . '/components/com_socialads/controller.php';

/**
 * Campaign controller class.
 *
 * @since  1.6
 */
class SocialadsControllerRender extends SocialadsController
{
	/**
	 * Method to display a view.
	 *
	 * @param   boolean  $cachable   If true, the view output will be cached
	 * @param   array    $urlparams  An array of safe url parameters and their variable types, for valid values see {@link JFilterInput::clean()}.
	 *
	 * @return  JController  This object to support chaining.
	 *
	 * @since   1.5
	 */
	public function display($cachable = false, $urlparams = false)
	{
		return true;

		/*parent::display();*/
	}

	/**
	 * Check if new ads available
	 *
	 * @return  [type]  [description]
	 */
	public function checkIfAdsAvailable()
	{
		$input = Factory::getApplication()->input;
		$checkAdAndGethtml = $input->get('checkAdAndGethtml', '', 'INT');
		$sa_params = ComponentHelper::getParams('com_socialads');

		if ($input->get('zone_id'))
		{
			// $check = $adRetriever->checkIfAdsAvailable($input->get('ad_id','','INT'),$input->get('module_id','','STRING'),$input->get('zone_id','','INT'));

			$check = SaAdEngineHelper::getInstance()->checkIfAdsAvailable(
				$input->get('ad_id', '', 'INT'),
				$input->get('module_id', '', 'STRING'),
				$input->get('zone_id', '', 'INT')
			);

			if ($checkAdAndGethtml)
			{
				if ($check && $check->ad_id)
				{
					$ad_data        = new stdClass;
					$ad_data->ad_id = $check->ad_id;
					$module_id      = $input->get('module_id', '', 'STRING');
					$cache          = Factory::getCache('mod_socialads');

					if ($sa_params->get('enable_caching') == 1)
					{
						$cache->setCaching(1);
					}
					else
					{
						$cache->setCaching(0);
					}

					// $addata  = $cache->call(array($adRetriever, 'getAdDetails'), $ad_data);
					$addata  = $cache->get(array(SaAdEngineHelper::getInstance(), 'getAdDetails'), $ad_data);

					$get_ad_forratation = 1;

					// $adHTML  = $cache->call(array($adRetriever, 'getAdHtml'), $addata, 0, $get_ad_forratation, $module_id );
					$adHTML  = $cache->get(array(SaAdEngineHelper::getInstance(), 'getAdHtml'), $addata, 0, $get_ad_forratation, $module_id);

					$respone['adHTML'] = $adHTML;
					$respone['check'] = $check;

					header('Content-type: application/json');
					echo json_encode($respone);
					// echo $adHTML;
				}
				else 
				{
					header('Content-type: application/json');
					echo json_encode($check);
				}
			}
			else 
			{
				header('Content-type: application/json');
				echo json_encode($check);
			}

			jexit();
		}
	}

	/**
	 * Get Ad Html
	 *
	 * @return  html
	 */
	public function getAdHtml()
	{
		$input = Factory::getApplication()->input;

		// SocialAds config parameters
		$sa_params = ComponentHelper::getParams('com_socialads');

		if ($input->get('ad_id'))
		{
			$ad_data        = new stdClass;
			$ad_data->ad_id = $input->get('ad_id', '', 'INT');
			$module_id      = $input->get('module_id', '', 'STRING');
			$cache          = Factory::getCache('mod_socialads');

			if ($sa_params->get('enable_caching') == 1)
			{
				$cache->setCaching(1);
			}
			else
			{
				$cache->setCaching(0);
			}

			// $addata  = $cache->call(array($adRetriever, 'getAdDetails'), $ad_data);
			$addata  = $cache->get(array(SaAdEngineHelper::getInstance(), 'getAdDetails'), $ad_data);

			$get_ad_forratation = 1;

			// $adHTML  = $cache->call(array($adRetriever, 'getAdHtml'), $addata, 0, $get_ad_forratation, $module_id );
			$adHTML  = $cache->get(array(SaAdEngineHelper::getInstance(), 'getAdHtml'), $addata, 0, $get_ad_forratation, $module_id);

			header('Content-type: application/html');
			echo $adHTML;
		}

		jexit();
	}
}
