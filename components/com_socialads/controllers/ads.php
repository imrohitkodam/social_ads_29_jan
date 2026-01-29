<?php
/**
 * @version    SVN: <svn_id>
 * @package    Socialads
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2015 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

// No direct access.
defined('_JEXEC') or die();

use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Session\Session;
use Joomla\String\StringHelper;
use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\MVC\Controller\AdminController;

$lang = Factory::getLanguage();

require_once JPATH_COMPONENT . '/controller.php';
/**
 * Controller for ads view.
 *
 * @since  1.6
 */
class SocialadsControllerAds extends AdminController
{
	/**
	 * Method to publish records.
	 *
	 * @return void
	 *
	 * @since 3.0
	 */
	public function publish()
	{
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));
		$app     = Factory::getApplication();
		$cid     = $app->input->get('cid', '', 'array');
		$itemid  = SaCommonHelper::getSocialadsItemid('ads');
		$data    = array('publish' => 1, 'unpublish' => 0);
		$stateId = $app->input->get('task', '', 'STRING');
		$value   = ArrayHelper::getValue($data, $stateId, 0, 'int');

		if (!is_array($cid) || count($cid) < 1)
		{
			$this->setMessage(Text::sprintf('COM_SOCIALADS_NO_ITEM_SELECTED'), 'warning');
		}
		else
		{
			$model = $this->getModel('adform');

			// Make sure the item ids are integers
			ArrayHelper::toInteger($cid);

			try
			{
				$model->publish($cid, $value);

				if ($value === 1)
				{
					$ntext = 'COM_SOCIALADS_N_ADS_PUBLISHED';
				}
				else
				{
					$ntext = 'COM_SOCIALADS_N_ADS_UNPUBLISHED';
				}

				$this->setMessage(Text::plural($ntext, count($cid)));
			}
			catch (Exception $e)
			{
				$this->setMessage($e->getMessage(), 'error');
			}
		}

		$this->setRedirect(Route::_('index.php?option=com_socialads&view=ads&Itemid=' . $itemid, false));
	}

	/**
	 * Method to delete ad records.
	 *
	 * @return void
	 *
	 * @since 3.0
	 */
	public function delete()
	{
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));
		$input        = Factory::getApplication()->input;
		$adid         = $input->post->get('cid', array(), 'array');
		$itemid       = SaCommonHelper::getSocialadsItemid('ads');
		$model        = $this->getModel('adform');
		$successCount = $model->delete($adid);

		if ($successCount === true)
		{
			$this->setMessage(Text::_('COM_SOCIALADS_AD_DELETED'));
		}
		else
		{
			$this->setMessage(Text::_('COM_SOCIALADS_AD_NOT_DELETED'));
		}

		$this->setRedirect(Route::_('index.php?option=com_socialads&view=ads&Itemid=' . $itemid, false));
	}

	/**
	 * Method to add records.
	 *
	 * @return void
	 *
	 * @since 3.0
	 */
	public function addNew()
	{
		$itemId = SaCommonHelper::getSocialadsItemid('adform');

		$link = Route::_('index.php?option=com_socialads&view=adform&Itemid=' . $itemId, false);
		$this->setRedirect($link);
	}

	/**
	 * Method to Edit records.
	 *
	 * @return void
	 *
	 * @since 3.0
	 */
	public function edit()
	{
		$input = Factory::getApplication()->input;
		$cid   = $input->get('cid', '', 'array');
		ArrayHelper::toInteger($cid);

		$itemId = SaCommonHelper::getSocialadsItemid('adform');

		$link = Route::_('index.php?option=com_socialads&view=adform&ad_id=' . $cid[0] . '&Itemid=' . $itemId, false);

		$this->setRedirect($link);
	}

	/**
	 * Method get CSV report
	 *
	 * @return  void
	 *
	 * @since   3.0
	 */
	public function adCsvExport()
	{
		$model   = $this->getModel("ads");
		$CSVData = $model->adCsvExport();
	}

	/**
	 * Method save as copy
	 *
	 * @return  void
	 *
	 * @since   4.0
	 */
	public function saveAsCopy()
	{
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));
		$app     = Factory::getApplication();
		$cid     = $app->input->get('cid', '', 'array');
		$session   = Factory::getSession();
		$count        = 0;
		$user = Factory::getUser();
		$isAdCopied = true;
		$itemId = SaCommonHelper::getSocialadsItemid('ads');

		if (!is_array($cid) || count($cid) < 1)
		{
			$this->setMessage(Text::sprintf('COM_SOCIALADS_NO_ITEM_SELECTED'), 'warning');

			$this->setRedirect(Route::_('index.php?option=com_socialads&view=ads&Itemid=' . $itemId, false));
		}
		else
		{
			try
			{
				foreach($cid as $adId)
				{
					$model = $this->getModel('adform');
					$result     = false;
					$db    = Factory::getDBO();
					$sa_params = ComponentHelper::getParams('com_socialads');
					$userId = $user->id;
					$query = $db->getQuery(true)
						->select('*')
						->from($db->qn('#__ad_data'))
						->where($db->qn('ad_id') . ' = ' . $db->q($adId));
					$db->setQuery($query);

					$adData = $db->loadObject();

					if ($adData->ad_alternative == 1 || $adData->ad_noexpiry == 1 || $sa_params->get('payment_mode') == 'wallet_mode')
					{
						$designAd_data        = array();

						$dbZone    = Factory::getDBO();
						$query = $dbZone->getQuery(true)
							->select('*')
							->from($dbZone->qn('#__ad_zone'))
							->where($dbZone->qn('id') . ' = ' . $dbZone->q($adData->ad_zone));
						$dbZone->setQuery($query);

						$zone = $dbZone->loadObject();

						$designAd_data['ad_creator_id'] = $adData->created_by;
						$designAd_data['ad_zone_id'] = $adData->ad_zone;
						$designAd_data['adtype'] = str_replace("|", "", $zone->ad_type);
						$designAd_data['addata']['ad_url1'] = $adData->ad_url1;
						$designAd_data['addata']['ad_url2'] = $adData->ad_url2;
						$designAd_data['addata']['ad_title'] = StringHelper::increment($adData->ad_title);
						$designAd_data['addata']['ad_body'] = $adData->ad_body;
						$designAd_data['layout'] = $adData->layout;
						$designAd_data['old_image'] = $adData->ad_image;
						$designAd_data['old_ad_id'] = $adData->ad_id;
						$designAd_data['unlimited_ad'] = $adData->ad_noexpiry;
						$designAd_data['ad_guest'] = $adData->ad_guest;
						$designAd_data['altadbutton'] = $adData->ad_alternative ? 'on' : 'off';

						$adminApproval = 0;

						$response = $model->saveDesignAd($designAd_data, $adminApproval, $isAdCopied);

						if ($response === false)
						{
							return false;
						}

						if (!$adData->ad_alternative)
						{
							$response = $model->saveTargetingData($designAd_data, $isAdCopied);

							if ($response === false)
							{
								return false;
							}

							$designAd_data['datefrom']     = $adData->ad_startdate;
							$designAd_data['chargeoption'] = $adData->ad_payment_type;
							$designAd_data['unlimited_ad'] = $adData->ad_noexpiry;
							$designAd_data['ad_campaign']  = $adData->camp_id;
							$designAd_data['pricing_opt']  = $adData->ad_payment_type;

							$response = $model->savePricingData($designAd_data, $isAdCopied);

							if ($response === false)
							{
								return false;
							}
						}

						$session->clear('ad_id');
						$result   = true;
					}

					if ($result)
					{
						$count++;
					}
				}
			}
			catch (Exception $e)
			{
				$this->setMessage($this->setMessage(Text::plural('COM_SOCIALADS_ERR_SOMETHING_WENT_WRONG')), 'error');
				$this->setRedirect(Route::_('index.php?option=com_socialads&view=ads&Itemid=' . $itemId, false));
			}
		}

		$app->enqueueMessage(Text::_('COM_SOCIALADS_COPY_ADS_WARNING_MESSAGE'), 'info');

		if ($count)
		{
			$this->setMessage(Text::plural('COM_SOCIALADS_N_ADS_COPIED', $count));
		}

		$this->setRedirect(Route::_('index.php?option=com_socialads&view=ads&Itemid=' . $itemId, false));
	}
}
