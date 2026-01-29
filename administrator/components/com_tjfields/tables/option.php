<?php
/**
 * @package    Tjfields
 *
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\Table\Table;

/**
 * Field Table class
 *
 * @since  1.1
 */
class TjfieldsTableOption extends Table
{
	/**
	 * Constructor
	 *
	 * @param   JDatabase  &$db  A database connector object
	 */
	public function __construct(&$db)
	{
		parent::__construct('#__tjfields_options', 'id', $db);
	}
}
