<?php
/**
 * @version    SVN:<SVN_ID>
 * @package    Com_Socialads
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2018 TechJoomla. All rights reserved
 * @license    GNU General Public License version 2, or later
 */

// No direct access
defined('_JEXEC') or die;

use \Joomla\CMS\Factory;
use \Joomla\CMS\Uri\Uri;
use \Joomla\CMS\Router\Route;
use \Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Component\ComponentHelper;


/**
 * View class for a list of Socialads.
 *
 * @since  1.6
 */
class SocialadsViewadsummary extends HtmlView
{
	/**
	 * Display the view
	 *
	 * @param   array  $tpl  An optional associative array.
	 *
	 * @return  array
	 *
	 * @since 1.6
	 */
	public function display($tpl = null)
	{
		$user = Factory::getUser();

		if (!$user->id)
		{
			$msg = Text::_('COM_SOCIALADS_LOGIN_MSG');
			$uri = Uri::getInstance()->toString();
			$url = urlencode(base64_encode($uri));
			$this->mainframe->enqueueMessage($msg, 'success');
			$this->mainframe->redirect(Route::_('index.php?option=com_users&view=login&return=' . $url, false));
		}

		$params = ComponentHelper::getParams('com_socialads');
		$currentBSViews = $params->get('bootstrap_version', "bs3");
		$this->bsVersion = $currentBSViews;

		$input         = Factory::getApplication()->input;
		$this->adid    = $input->get('adid', 0, 'INT');
		$model         = $this->getModel('adsummary');
		$this->items   = $this->get('Items');
		$this->ad_type = $model->getadtype($this->adid);

		// Get data for donut chart
		$this->statsforpie = $model->getstatsforpiechart();

		// Get data for line chart
		$this->statsforbar = $model->getstatsforlinechart();

		// Get ad preview
		$this->preview = SaAdEngineHelper::getAdHtml($this->adid, 1);

		if ($input->post->get('from'))
		{
			$this->from_date = $input->post->get('from');
		}
		else
		{
			$this->from_date = date('Y-m-d', strtotime(date('Y-m-d') . ' - 30 days'));
		}

		if ($input->post->get('to'))
		{
			$this->to_date = $input->post->get('to');
		}
		else
		{
			$this->to_date = date('Y-m-d');
		}

		parent::display($tpl);
	}
}
