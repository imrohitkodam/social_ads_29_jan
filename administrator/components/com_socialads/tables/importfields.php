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

use Joomla\CMS\Table\Table;

/**
 * social_targetting Table class
 *
 * @since  1.6
 **/
class TableImportfields extends Table
{
	/**
	 * Constructor.
	 *
	 * @param   JDatabase  &$db  A database connector object
	 *
	 * @since   1.6
	 */
	public function __construct(&$db)
	{
		parent::__construct('#__ad_fields_mapping', 'mapping_id', $db);
	}
}
