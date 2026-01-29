<?php
/**
 * @version    SVN:<SVN_ID>
 * @package    SocialAds
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2015 TechJoomla. All rights reserved
 * @license    GNU General Public License version 2, or later
 */

// No direct access.
defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\AdminController;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\Utilities\ArrayHelper;

require_once JPATH_COMPONENT . '/controller.php';

/**
 * Campaigns list controller class.
 *
 * @since  1.6
 */
class SocialadsControllerCampaigns extends AdminController
{
	/**
	 * Proxy for getModel.
	 *
	 * @param   string  $name    The model name. Optional.
	 * @param   string  $prefix  The class prefix. Optional.
	 * @param   array   $config  The array of possible config values. Optional.
	 *
	 * @return  JModelLegacy
	 *
	 * @since   1.6
	 */
	public function &getModel($name = 'Campaignform', $prefix = 'SocialadsModel', $config = array())
	{
		$model = parent::getModel($name, $prefix, array('ignore_request' => true));

		return $model;
	}

	/**
	 * delete campaign.
	 *
	 * @return void
	 *
	 * @since  1.6
	 */
	public function delete()
	{
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));
		$app          = Factory::getApplication();
		$cid          = $app->input->get('cid', '', 'array');
		$itemid       = SaCommonHelper::getSocialadsItemid('campaigns');

		if (!is_array($cid) || count($cid) < 1)
		{
			$this->setMessage(Text::sprintf('COM_SOCIALADS_NO_ITEM_SELECTED'), 'warning');
		}
		else
		{
			$model        = $this->getModel();

			// Make sure the item ids are integers
			ArrayHelper::toInteger($cid);

			// Publish the items.
			try
			{
				$successCount = $model->delete($cid);

				if ($successCount === true)
				{
					$this->setMessage(Text::_('COM_SOCIALADS_CAMPAIGN_DELETED'));
				}
				else
				{
					$this->setMessage(Text::_('COM_SOCIALADS_CAMPAIGN_NOT_DELETED'));
				}
			}
			catch (Exception $e)
			{
				$this->setMessage($e->getMessage(), 'error');
			}
		}

		$this->setRedirect(Route::_('index.php?option=com_socialads&view=campaigns&Itemid=' . $itemid, false));
	}

	/**
	 * publish campaign.
	 *
	 * @return void
	 *
	 * @since  1.6
	 */
	public function publish()
	{
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));
		$app    = Factory::getApplication();
		$cid    = $app->input->get('cid', '', 'array');
		$itemid = SaCommonHelper::getSocialadsItemid('campaigns');
		$data   = array('publish' => 1, 'unpublish' => 0);
		$task   = $this->getTask();
		$value  = ArrayHelper::getValue($data, $task, 0, 'int');

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
				$model->publish($cid, $value);

				if ($value === 1)
				{
					$ntext = 'COM_SOCIALADS_N_CAMPAIGNS_PUBLISHED';
				}
				else
				{
					$ntext = 'COM_SOCIALADS_N_CAMPAIGNS_UNPUBLISHED';
				}

				$this->setMessage(Text::plural($ntext, count($cid)));
			}
			catch (Exception $e)
			{
				$this->setMessage($e->getMessage(), 'error');
			}
		}

		$this->setRedirect(Route::_('index.php?option=com_socialads&view=campaigns&Itemid=' . $itemid, false));
	}
}
