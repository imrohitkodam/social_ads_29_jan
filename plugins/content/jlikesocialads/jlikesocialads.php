<?php
/**
 * @package    JGive
 *
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2018 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Factory;


// Import library dependencies

/**
 * SocialadsViewReport
 *
 * @package     Socialads
 * @subpackage  Socialads report view class
 * @since       1.2.1
 */
class PlgContentjlikeSocialads extends CMSPlugin
{
	/**
	 * Function used to show add
	 *
	 * @param   STRING  $context  $cont_id  Content id
	 * @param   ARRAY   $addata   $addata   add data
	 *
	 * @return  $html
	 *
	 * @since  1.0.0
	 */
	public function onAfterSaAdDispay($context, $addata)
	{
		$app = Factory::getApplication();

		if ($app->getName() != 'site')
		{
			return;
		}

		$html = '';
		$app = Factory::getApplication();

		if ($context != 'com_socialads.viewad' and $app->scope != 'mod_socialads')
		{
			return;
		}

		// Not to show anything related to commenting
		$show_comments = -1;
		$show_like_buttons = 1;

		Factory::getApplication()->input->set('data',
			json_encode(
				array(
					'cont_id' => $addata['id'],
					'element' => $context,
					'title' => $addata['title'],
					'url' => $addata['url'],
					'plg_name' => 'jlikesocialads',
					'show_comments' => $show_comments,
					'show_like_buttons' => $show_like_buttons
				)
			)
		);

		$helperPath = JPATH_SITE . '/components/com_jlike/helper.php';

		if (file_exists(JPATH_SITE . '/components/com_jlike/helper.php'))
		{
			// Require_once $path;
			JLoader::register('ComjlikeHelper', $helperPath);
			JLoader::load('ComjlikeHelper');

			$jlikehelperObj = new ComjlikeHelper;
			$html = $jlikehelperObj->showlike();
		}

		return $html;
	}
}
