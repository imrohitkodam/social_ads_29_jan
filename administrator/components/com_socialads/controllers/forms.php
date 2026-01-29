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
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Session\Session;
use Joomla\String\StringHelper;
use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\MVC\Controller\AdminController;

// Load socialads Controller for list views
require_once __DIR__ . '/salist.php';

/**
 * ads list controller class.
 *
 * @since  1.6
 */
class SocialadsControllerForms extends SocialadsControllerSalist
{
	/**
	 * Proxy for getModel.
	 *
	 * @param   string  $name    An ad_id for perticular ad
	 * @param   string  $prefix  To find prefix
	 * @param   array   $config  An optional associative array of configuration settings.
	 *
	 * @return  model
	 *
	 * @since  1.6
	 */
	public function getModel($name = 'form', $prefix = 'SocialadsModel', $config = array())
	{
		$model = parent::getModel($name, $prefix, array('ignore_request' => true));

		return $model;
	}

	/**
	 * Method to save the submitted ordering values for records via AJAX.
	 *
	 * @return  void
	 *
	 * @since   3.0
	 */
	public function saveOrderAjax()
	{
		// Get the input
		$input = Factory::getApplication()->input;
		$pks   = $input->post->get('cid', array(), 'array');
		$order = $input->post->get('order', array(), 'array');

		// Sanitize the input
		ArrayHelper::toInteger($pks);
		ArrayHelper::toInteger($order);

		// Get the model
		$model = $this->getModel();

		// Save the ordering
		$return = $model->saveorder($pks, $order);

		if ($return)
		{
			echo "1";
		}

		// Close the application
		Factory::getApplication()->close();
	}

	/**
	 * Method to save status.
	 *
	 * @return  void
	 *
	 * @since   3.0
	 */
	public function save()
	{
		$app    = Factory::getApplication();
		$input  = $app->input->post;
		$id     = $input->get('ad_id', '', 'INT');
		$status = $input->get('status', '', 'STRING');
		$sa_params = ComponentHelper::getParams('com_socialads');
		$db              = Factory::getDbo();
		$model = $this->getModel('forms');
		$link = 'index.php?option=com_socialads&view=forms';
		$initialFee = $sa_params->get('initial_fee_for_ad_placement');
		$needToPayInitialFee = $sa_params->get('need_to_pay_initial_fee');

		$query = $db->getQuery(true);
		$query->select($db->quoteName('a.created_by'))
			->select($db->quoteName('a.ad_title'))
			->select($db->quoteName('a.ad_noexpiry'))
			->select($db->quoteName('a.ad_alternative'))
			->select($db->quoteName('a.ad_affiliate'))
			->select($db->quoteName('a.pay_initial_fee'))
			->select($db->quoteName('u.name'))
			->select($db->quoteName('u.email'))
			->from($db->quoteName('#__ad_data', 'a'))
			->join('INNER', $db->quoteName('#__users', 'u') . ' ON ' . $db->quoteName('a.created_by') . ' = ' . $db->quoteName('u.id'))
			->where($db->quoteName('a.ad_id') . ' = ' . $id);

		$db->setQuery($query);
		$result	= $db->loadObject();

		if ($sa_params->get('payment_mode') == 'wallet_mode' && $status == 1 && $needToPayInitialFee && $initialFee 
			&& $result->ad_noexpiry == 0 && $result->ad_alternative == 0 && $result->ad_affiliate == 0 && $result->pay_initial_fee == 0)
		{
			$query = $db->getQuery(true);
			$query->select($db->quoteName('balance'))
				->select($db->quoteName('user_id'))
				->from($db->quoteName('#__ad_wallet_transc'))
				->where($db->quoteName('user_id') . ' = ' . $result->created_by)
				->order($db->quoteName('time') . ' DESC');

			$db->setQuery($query);
			$walletTrasaction	= $db->loadObject();

			if ($initialFee > $walletTrasaction->balance)
			{
				$this->setRedirect($link, Text::_('INITIAL_CHARGE_ERROR_SAVING_MSG'), 'error');

				return 0;
			}

			$date1 = microtime(true);
			$createdDate = $date1;
			$date2 = date('Y-m-d');
			$todayDate = $date2;
			$query1 = $db->getQuery(true);
			// $query1->select($db->quoteName('id'))
			// 	->from($db->quoteName('#__ad_wallet_transc'))
			// 	->where("DATE(FROM_UNIXTIME(time)) = '" . $db->quote($todayDate))
			// 	->where($db->quoteName('type') . ' = ' . $db->quote('C'))
			// 	->where($db->quoteName('type_id') . " IS NULL");

			$query1 = "SELECT id FROM #__ad_wallet_transc WHERE DATE(FROM_UNIXTIME(time)) = '" . $todayDate . "' AND type_id IS NULL"
				. " AND type = 'C' AND user_id = " . $result->created_by . " ";
			$db->setQuery($query1);
			$check = $db->loadresult();

			if ($check)
			{
				$query3 = $db->getQuery(true);
				$query3 = "UPDATE #__ad_wallet_transc SET time ='" . $createdDate . "', spent = spent +"
						. $initialFee . ",balance = " . $walletTrasaction->balance . " - " . $initialFee . " where id=" . $check;
				$db->setQuery($query3);
				$db->execute();
			}
			else
			{
				$query4 = $db->getQuery(true);
				$query4 = "INSERT INTO #__ad_wallet_transc
						(time, user_id, spent, earn, balance, type, comment)
						VALUES ('" . $createdDate . "'," . $result->created_by . "," . $initialFee . ",'0'," . $walletTrasaction->balance . " - " .
						$initialFee . ", 'C' ,'COM_SOCIALADS_INITIAL_FEE_MESSAGE')";
				$db->setQuery($query4);
				$db->execute();
			}

			$query = $db->getQuery(true);
			$query->update($db->quoteName('#__ad_data'))
				->set($db->quoteName('pay_initial_fee') . ' = ' . 1)
				->set($db->quoteName('pay_initial_fee_amout') . ' = ' . $initialFee)
				->where($db->quoteName('ad_id') . ' = ' . $id);
			$db->setQuery($query);

			$db->execute();
		}

		$store = $model->store();

		if ($store)
		{
			$msg = Text::_('FIELD_SAVING_MSG');
		}
		else
		{
			$msg = Text::_('FIELD_ERROR_SAVING_MSG');
		}

		$this->setRedirect($link, $msg);
	}

	/**
	 * Method to update zone
	 *
	 * @return  void
	 *
	 * @since   3.0
	 */
	public function updatezone()
	{
		$model      = $this->getModel('forms');
		$upDateZone = $model->updatezone();

		if ($upDateZone)
		{
			$msg = Text::_('FIELD_SAVING_MSG');
		}
		else
		{
			$msg = Text::_('FIELD_ERROR_SAVING_MSG');
		}

		$link = 'index.php?option=com_socialads&view=forms';
		$this->setRedirect($link, $msg);
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
		$model   = $this->getModel("forms");
		$CSVData = $model->adCsvExport();
	}

	/**
	 * publish ads.
	 *
	 * @return void
	 *
	 * @since  3.1.15
	 */
	public function publish()
	{
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));
		$app     = Factory::getApplication();
		$cid     = $app->input->get('cid', '', 'array');
		$data   = array('publish' => 1, 'unpublish' => 0);
		$stateId = $app->input->get('task', '', 'STRING');
		$value  = ArrayHelper::getValue($data, $stateId, 0, 'int');
		$db    = Factory::getDBO();

		if (!is_array($cid) || count($cid) < 1)
		{
			$this->setMessage(Text::sprintf('COM_SOCIALADS_NO_ITEM_SELECTED'), 'warning');
		}
		else
		{
			$model = $this->getModel();

			// Make sure the item ids are integers
			ArrayHelper::toInteger($cid);

			// Publish the items.
			try
			{
				$state        = $stateId == 'publish' ? 0 : 1;
				$adIds = $cid;

				$query = $db->getQuery(true);
				$query->select('COUNT(*)');
				$query->from($db->quoteName('#__ad_data'));
				$query->where($db->quoteName('state') . ' = ' . $state);
				$query->where($db->quoteName('ad_id') . ' IN (' . implode(',', $db->quote($adIds)) .  ')');
				$db->setQuery($query);
				$count = $db->loadResult();

				$model->publish($cid, $value);

				if ($value === 1)
				{
					$ntext = 'COM_SOCIALADS_N_ADS_PUBLISHED';
				}
				else
				{
					$ntext = 'COM_SOCIALADS_N_ADS_UNPUBLISHED';
				}

				$this->setMessage(Text::plural($ntext, $count));
			}
			catch (Exception $e)
			{
				$this->setMessage($e->getMessage(), 'error');
			}
		}

		$this->setRedirect(Route::_('index.php?option=com_socialads&view=forms', false));
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

		if (!is_array($cid) || count($cid) < 1)
		{
			$this->setMessage(Text::sprintf('COM_SOCIALADS_NO_ITEM_SELECTED'), 'warning');
		}
		else
		{
			try
			{
				foreach($cid as $adId)
				{
					$model = $this->getModel();
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
						$designAd_data = array();

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
						$designAd_data['altadbutton'] = $adData->ad_alternative ? 'on' : 'off';
						$designAd_data['ad_guest'] = $adData->ad_guest;

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
						}

						if ($response === false)
						{
							return false;
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
				$this->setRedirect(Route::_('index.php?option=com_socialads&view=forms', false));
			}
		}

		$app->enqueueMessage(Text::_('COM_SOCIALADS_COPY_ADS_WARNING_MESSAGE'), 'info');

		if ($count)
		{
			$this->setMessage(Text::plural('COM_SOCIALADS_N_ADS_COPIED', $count));
		}

		$this->setRedirect(Route::_('index.php?option=com_socialads&view=forms', false));
	}
}
