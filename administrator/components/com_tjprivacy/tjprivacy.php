<?php
/**
 * @version    SVN: <svn_id>
 * @package    TJPrivacy
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2017-2018 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

// No direct access to this file
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;

// Access check.
if (!Factory::getUser()->authorise('core.manage', 'com_tjprivacy'))
{
	throw new Exception(Text::_('JERROR_ALERTNOAUTHOR'));
}

// Get an instance of the controller prefixed by HelloWorld
$controller = BaseController::getInstance('Tjprivacy');

// Perform the Request task
$input = Factory::getApplication()->input;
$controller->execute($input->getCmd('task'));

// Redirect if set by the controller
$controller->redirect();
