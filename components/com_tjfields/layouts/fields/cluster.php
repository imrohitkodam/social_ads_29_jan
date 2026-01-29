<?php
/**
 * @package	TJ-Fields
 * @author	 TechJoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2019 TechJoomla. All rights reserved.
 * @license	GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

if (!key_exists('field', $displayData))
{
	return;
}

$field = $displayData['field'];

if ($field->value)
{
	JLoader::import("/components/com_cluster/includes/cluster", JPATH_ADMINISTRATOR);
	$clusterModel = ClusterFactory::model('Cluster', array('ignore_request' => true));
	$cluster = $clusterModel->getItem($field->value);
	echo $cluster->name;
}