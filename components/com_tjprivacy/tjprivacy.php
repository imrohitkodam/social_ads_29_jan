<?php
/**
 * @version    SVN: <svn_id>
 * @package    TJPrivacy
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2017-2018 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

defined('_JEXEC') or die;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Factory;

// Require the base controller
require_once JPATH_COMPONENT . '/controller.php';

// Execute the task.
$controller = BaseController::getInstance('Tjprivacy');
$controller->execute(Factory::getApplication()->input->get('task'));
$controller->redirect();
