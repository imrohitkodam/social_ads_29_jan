<?php
/**
 * @version    SVN: <svn_id>
 * @package    Tjfields
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2016 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

defined('_JEXEC') or die;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Factory;

JLoader::register('TjfieldsHelper', JPATH_SITE . '/components/com_tjfields/helpers/tjfields.php');
JLoader::load('TjfieldsHelper');
TjfieldsHelper::getLanguageConstantForJs();

// Include dependancies
jimport('joomla.application.component.controller');

// Execute the task.
$controller	= BaseController::getInstance('Tjfields');
$controller->execute(Factory::getApplication()->input->get('task'));
$controller->redirect();
