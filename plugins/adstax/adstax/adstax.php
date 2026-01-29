<?php
/**
 * @version    SVN:<SVN_ID>
 * @package    Js_Events
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2015 TechJoomla. All rights reserved
 * @license    GNU General Public License version 2, or later
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\CMSPlugin;

$lang = Factory::getLanguage();
$lang->load('plg_adstax_adstax', JPATH_ADMINISTRATOR);

/**
 * Plugin class to add tax in Socialads.
 *
 * @since  1.6
 */
class PlgAdstaxAdsTax extends CMSPlugin
{
	/**
	 * Methode to add tax
	 *
	 * @param   integer  $amt  Tax amount
	 *
	 * @return  array
	 *
	 * @since   1.6
	 */
	public function onAfterSocialAdAddTax($amt)
	{
		$taxAssign   = $this->params->get('tax_per');
		$taxValue = ($taxAssign * $amt) / 100;
		$return[]  = $taxAssign . "%";
		$return[]  = $taxValue;

		return $return;
	}
}
