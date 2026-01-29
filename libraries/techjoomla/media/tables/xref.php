<?php
/**
 * @package     Techjoomla.Libraries
 * @subpackage  TjMedia
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

defined('_JEXEC') or die;

use Joomla\CMS\Table\Table;

/**
 * TJMediaTableXref
 *
 * @since  1.0.0
 */
class TJMediaTableXref extends Table
{
	/**
	 * Constructor
	 *
	 * @param   JDatabaseDriver  &$db  Database object
	 *
	 * @since  1.0.0
	 */
	public function __construct(&$db)
	{
		parent::__construct('#__tj_media_files_xref', 'id', $db);
	}
}
