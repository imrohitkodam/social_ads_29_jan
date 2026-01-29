<?php
/**
 * @version    SVN:<SVN_ID>
 * @package    Com_Socialads
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2015 TechJoomla. All rights reserved
 * @license    GNU General Public License version 2, or later
 */

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;

// Import Joomla view library

/**
 * HTML View class for the Aniket Component
 *
 * @since  1.6
 */
class SocialadsViewWallet extends HtmlView
{
	/**
	 * Overwriting JView display method
	 *
	 * @param   boolean  $cachable   parameter.
	 * @param   boolean  $urlparams  url parameter.
	 * @param   array    $tpl        An optional associative array.
	 *
	 * @return  array
	 *
	 * @since 1.6
	 */
	public function display($cachable = false, $urlparams = false,$tpl = null)
	{
		$this->params = ComponentHelper::getParams('com_socialads');
		$mainframe = Factory::getApplication();
		$input = Factory::getApplication()->input;
		$option = $input->get('option', '', 'STRING');
		$month = $mainframe->getUserStateFromRequest($option . 'month', 'month', '', 'int');
		$year = $mainframe->getUserStateFromRequest($option . 'year', 'year', '', 'int');
		$builadModel = $this->getModel();
		$user_id = $mainframe->getUserStateFromRequest($option, 'user', '0', 'int');
		$builadModel->setState('list.limit', $builadModel->getTotal());
		$this->wallet = $builadModel->getItems();
		$lists['month'] = $month;
		$lists['year'] = $year;
		$this->lists = $lists;
		$this->months = array(
			0 => Text::_('COM_SOCIALADS_WALLET_MONTH'),
			1 => Text::_('JANUARY_SHORT'),
			2 => Text::_('FEBRUARY_SHORT'),
			3 => Text::_('MARCH_SHORT'),
			4 => Text::_('APRIL_SHORT'),
			5 => Text::_('MAY_SHORT'),
			6 => Text::_('JUNE_SHORT'),
			7 => Text::_('JULY_SHORT'),
			8 => Text::_('AUGUST_SHORT'),
			9 => Text::_('SEPTEMBER_SHORT'),
			10 => Text::_('OCTOBER_SHORT'),
			11 => Text::_('NOVEMBER_SHORT'),
			12 => Text::_('DECEMBER_SHORT'),
		);
		$lists['month'] = $month;
		$lists['year'] = $year;
		$this->lists = $lists;
		$this->month = array();

		foreach ($this->months as $key => $value) :
			$this->month[] = HTMLHelper::_('select.option', $key, $value);
		endforeach;

		// Year filter
		$this->year = array();
		$curYear = date('Y');
		$this->year = range($curYear, 2000, 1);

		foreach ($this->year as $key => $value)
		{
			unset($this->year[$key]);
			$this->year[$value] = $value;
		}

		foreach ($this->year as $key => $value) :
			$year1[] = HTMLHelper::_('select.option', $key, $value);
		endforeach;

		parent::display($tpl);
	}
}
