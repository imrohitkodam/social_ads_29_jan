<?php
/**
 * @package    TjFields
 *
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2019 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */
defined('_JEXEC') or die;
use Joomla\CMS\MVC\Model\ListModel;

/**
 * Methods supporting a list of Tjfields option records.
 *
 * @since  _DEPLOY_VERSION_
 */
class TjfieldsModelOptions extends ListModel
{
	/**
	 * Constructor.
	 *
	 * @param   Array  $config  An optional associative array of configuration settings.
	 *
	 * @since  _DEPLOY_VERSION_
	 */
	public function __construct($config = array())
	{
		if (empty($config['filter_fields']))
		{
			$config['filter_fields'] = array(
				'id', 'a.id',
				'field_id', 'a.field_id',
				'options', 'a.options',
				'value', 'a.value'
			);
		}

		parent::__construct($config);
	}

	/**
	 * Build an SQL query to load the list data.
	 *
	 * @return  JDatabaseQuery
	 *
	 * @since  _DEPLOY_VERSION_
	 */
	protected function getListQuery()
	{
		// Filter by client (Set state from external view to render client specific fields)

		// Create a new query object.
		$db    = $this->getDbo();
		$query = $db->getQuery(true);

		// Select the required fields from the table.
		$query->select('*');
		$query->select($db->quoteName(array('id', 'field_id', 'options', 'value')));
		$query->from('`#__tjfields_options` AS a');

		// Filter by group state
		$fieldId = $this->getState('filter.field_id');

		if (is_numeric($fieldId))
		{
			$query->where('a.field_id = ' . (int) $fieldId);
		}

		$query->order($db->quoteName($db->escape($this->getState('list.ordering', 'a.options'))) . ' ' . $db->escape($this->getState('list.direction', 'ASC')));

		return $query;
	}

	/**
	 * Method to get an array of data items.
	 *
	 * @return  mixed  An array of data items on success, false on failure.
	 *
	 * @since  _DEPLOY_VERSION_
	 */
	public function getItems()
	{
		$items = parent::getItems();

		return $items;
	}
}
