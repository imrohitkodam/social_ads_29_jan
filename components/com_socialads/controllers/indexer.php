<?php
/**
 * @version    SVN:<SVN_ID>
 * @package    Com_Socialads
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2018 TechJoomla. All rights reserved
 * @license    GNU General Public License version 2, or later
 */

// No direct access
defined('_JEXEC') or die(';)');

use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;


// Require_once(JPATH_COMPONENT . '/helper.php');
include_once JPATH_COMPONENT . '/controller.php';

/**
 * Indexer controller class.
 *
 * @since  1.6
 */
class SocialadsControllerIndexer extends BaseController
{
	/**
	 * Make Indexing
	 *
	 * @return void
	 *
	 * @since  1.6
	 */
	public function makeIndexing()
	{
			$saParams = ComponentHelper::getParams('com_socialads');

			$input = Factory::getApplication()->input;
			$pkey = $input->get('pkey');
			$indexlimit = $input->get('indexlimit');
			$indexlimitstart = $input->get('indexlimitstart');

			if ($pkey != $saParams->get('cron_key'))
			{
				echo Text::_("CRON_KEY_MSG");

				return;
			}

			$model = $this->getModel('indexer');
			$model->makeIndexing($indexlimitstart, $indexlimit, $pkey);
	}
}
// Class end
