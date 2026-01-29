<?php
/**
 * @version    SVN:<SVN_ID>
 * @package    Com_Socialads
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2015 TechJoomla. All rights reserved
 * @license    GNU General Public License version 2, or later
 */

// No direct access.
defined('_JEXEC') or die();

use Joomla\CMS\MVC\Controller\AdminController;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Component\ComponentHelper;

JLoader::register('TjControllerHouseKeeping', JPATH_SITE . "/libraries/techjoomla/controller/houseKeeping.php");

/**
 * Dashboard controller class.
 *
 * @since  1.6
 */
class SocialadsControllerDashboard extends AdminController
{
	use TjControllerHouseKeeping;

	/**
	 * Get donut chart data for dashboard
	 *
	 * @return json
	 *
	 * @since 3.1
	 */
	public function getDonutChartData()
	{
		$params = ComponentHelper::getParams('com_socialads');
		$model  = $this->getModel('dashboard');
		$data   = $model->getPeriodicOrders($params->get('payment_mode'));

		// Output json response
		header('Content-type: application/json');
		echo json_encode($data);
		jexit();
	}

	/**
	 * Get bar chart data for dashboard
	 *
	 * @return json
	 *
	 * @since 3.1
	 */
	public function getBarChartData()
	{
		$allMonths     = SaCommonHelper::getAllmonths();
		$model         = $this->getModel('dashboard');
		$monthlyIncome = $model->getMonthlyIncome();

		// To assign amount from array monthyincome to array allmonths
		for ($i = 0; $i < count($allMonths); $i++)
		{
			for ($j = 0; $j < count($monthlyIncome); $j++)
			{
				if ($allMonths[$i]['digitmonth'] == $monthlyIncome[$j]['monthsname'])
				{
					$allMonths[$i]['amount'] = $monthlyIncome[$j]['tampunt'];
				}
			}
		}

		// Output json response
		header('Content-type: application/json');
		echo json_encode($allMonths);
		jexit();
	}

	/**
	 * Get News Feeds
	 *
	 * @return  json
	 *
	 * @since 3.1
	 */
	public function getNewsFeeds()
	{
		$model = $this->getModel('dashboard');
		$feeds = $model->getNewsFeeds();

		// Output json response
		header('Content-type: application/json');
		echo json_encode($feeds);
		jexit();
	}

	/**
	 * This method contains code from the following projects:
	 * Akeeba Admin Tools, (c) 2010-2019 Nicholas K. Dionysopoulos / Akeeba Ltd
	 *
	 * Update the GeoIP2 database
	 *
	 * @return  json
	 *
	 * @since 3.2.0
	 */
	public function updateMaxmindDb()
	{
		// Load the TjMaxmindProvider if it's not already loaded
		if (!class_exists('TjMaxmindProvider'))
		{
			if (file_exists(JPATH_LIBRARIES . '/tjmaxmind/tjmaxmindprovider.php'))
			{
				if (include_once JPATH_LIBRARIES . '/tjmaxmind/maxmind/vendor/autoload.php')
				{
					include_once JPATH_LIBRARIES . '/tjmaxmind/tjmaxmindprovider.php';
				}
			}
		}

		$url = 'index.php?option=com_socialads';

		if (!class_exists('TjMaxmindProvider'))
		{
			$message = Text::_('COM_SA_LBL_TJMAXMIND_LIBRARY_MISSING');
			$this->setRedirect($url, $message, 'error');

			return;
		}

		$provider       = new TjMaxmindProvider;
		$result         = $provider->updateDatabase($forceCity = true);
		$customRedirect = $this->input->getBase64('returnurl', '');
		$customRedirect = empty($customRedirect) ? '' : base64_decode($customRedirect);

		if ($customRedirect && Uri::isInternal($customRedirect))
		{
			$url = $customRedirect;
		}

		if ($result === true)
		{
			$msg = Text::_('COM_SOCIALADS_MSG_MAXMIND_DB_DOWNLOADED');
			$this->setRedirect($url, $msg);
		}
		else
		{
			$this->setRedirect($url, $result, 'error');
		}
	}
}
