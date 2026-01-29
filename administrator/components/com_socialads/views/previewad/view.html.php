<?php
/**
 * @version    SVN:<SVN_ID>
 * @package    Com_Socialads
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2015 TechJoomla. All rights reserved
 * @license    GNU General Public License version 2, or later
 */

// No direct access
defined('_JEXEC') or die(';)');

use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;


/**
 * View class for adspreview.
 *
 * @since  1.6
 */
class SocialadsViewPreviewad extends HtmlView
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
		$doc = Factory::getDocument();
		$doc->addScript(Uri::root(true) . '/media/com_sa/vendors/flowplayer/flowplayer-3.2.13.min.js');

		parent::display($tpl);
	}
}
