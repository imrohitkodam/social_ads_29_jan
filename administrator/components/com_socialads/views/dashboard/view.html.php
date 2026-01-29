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

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Text;

/**
 * View class for a list of Socialads.
 *
 * @since  1.6
 */
class SocialadsViewDashboard extends HtmlView
{
	protected $items;

	protected $pagination;

	protected $state;

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
		SocialadsHelper::addSubmenu('dashboard');
		$input = Factory::getApplication()->input;
		$model = $this->getModel('dashboard');

		// Get params
		$this->params = ComponentHelper::getParams('com_socialads');

		// Set state filter
		$this->publish_states = array(
			'' => Text::_('JOPTION_SELECT_PUBLISHED'),
			'1' => Text::_('JPUBLISHED'),
			'0' => Text::_('JUNPUBLISHED')
		);

		$this->allincome           = $model->getAllOrdersIncome();
		$this->totalads            = $model->getTotalAds();
		$this->pendingorders       = $model->getPendingOrders($this->params->get('payment_mode'));
		$this->averagectr          = $model->getAverageCtr();
		$this->totalorders         = $model->getTotalOrders($this->params->get('payment_mode'));
		$this->topads              = $model->getTopAds();
		$this->periodicorderscount = $model->getPeriodicOrdersCount();

		/*  // TODO remove after maxmind auto db download implementation done
		$this->needsDbUpdate = $model->dbNeedsUpdate();
		*/
		$maxmindDbFilePath     = JPATH_PLUGINS . '/system/tjmaxmind/db/GeoLite2-City.mmdb';
		$this->maxmindDbExists = file_exists($maxmindDbFilePath);
		$this->hasTJMaxmindPlg = $model->hasTJMaxmindPlugin();
		$this->geoTargeting    = $this->params->get('geo_targeting');
		$this->downloadid      = $this->params->get('downloadid');
		$this->currency        = $this->params->get('currency');

		// @TODO - use userstate instead of post here and in model
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

		// Get installed version from xml file
		$xml           = simplexml_load_file(JPATH_COMPONENT . '/socialads.xml');
		$version       = (string) $xml->version;
		$this->version = $version;

		// Get new version
		$this->latestVersion = $model->getLatestVersion();

		$this->addToolbar();

		if (JVERSION < '4.0')
		{
			$this->sidebar = JHtmlSidebar::render();
		}

		parent::display($tpl);
	}

	/**
	 * Add the page title and toolbar.
	 *
	 * @return  void
	 *
	 * @since  1.6
	 */
	protected function addToolbar()
	{
		require_once JPATH_COMPONENT . '/helpers/socialads.php';

		if (JVERSION >= '3.0')
		{
			ToolbarHelper::title(Text::_('COM_SOCIALADS_TITLE_DASHBOARD'), 'list');
		}
		else
		{
			ToolbarHelper::title(Text::_('COM_SOCIALADS_TITLE_DASHBOARD'), 'dashboard.png');
		}

		$toolbar = Toolbar::getInstance('toolbar');
		$toolbar->appendButton(
		'Custom', '<a id="tjHouseKeepingFixDatabasebutton" class="btn btn-default hidden"><span class="icon-refresh"></span>'
		. Text::_('COM_SOCIALADS_FIX_DATABASE') . '</a>');

		ToolbarHelper::preferences('com_socialads');

		// Set sidebar action
		if (JVERSION >= '3.0')
		{
			JHtmlSidebar::setAction('index.php?option=com_socialads&view=dashboard');
			$this->extra_sidebar = '';
		}
	}
}
