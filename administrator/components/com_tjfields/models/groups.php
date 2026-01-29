<?php
/**
 * @version    CVS: 1.0.0
 * @package    Com_Tjfield
 * @author     Techjoomla <contact@techjoomla.com>
 * @copyright  2016  Techjoomla
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\ListModel;

/**
 * Methods supporting a list of Tjfields records.
 *
 * @since  1.6
 *
 */

class TjfieldsModelGroups extends ListModel
{
	/**
	 * Constructor.
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 *
	 * @see     JController
	 * @since    1.6
	 */
	public function __construct($config = array())
	{
		if (empty($config['filter_fields']))
		{
			$config['filter_fields'] = array(
					'id', 'a.id',
					'ordering', 'a.ordering',
					'state', 'a.state',
					'created_by', 'a.created_by',
					'name', 'a.name',
					'client', 'a.client',
					'client_type', 'a.client_type',
				);
		}

		parent::__construct($config);
	}

	/**
	 * Method to auto-populate the model state.
	 *
	 * This method should only be called once per instantiation and is designed
	 * to be called on the first call to the getState() method unless the model
	 * configuration flag to ignore the request is set.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @param   string  $ordering   An optional ordering field.
	 * @param   string  $direction  An optional direction (asc|desc).
	 *
	 * @return  void
	 *
	 * @since   12.2
	 */
	protected function populateState($ordering = 'a.id', $direction = 'desc')
	{
		// Initialise variables.
		$app = Factory::getApplication('administrator');

		// Load the filter state.
		$search = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
		$this->setState('filter.search', $search);

		$published = $this->getUserStateFromRequest($this->context . '.filter.state', 'filter_published', '', 'string');
		$this->setState('filter.state', $published);

		// Load the parameters.
		$params = ComponentHelper::getParams('com_tjfields');
		$this->setState('params', $params);

		// List state information.
		parent::populateState($ordering, $direction);
	}

	/**
	 * Method to get a store id based on model configuration state.
	 *
	 * This is necessary because the model is used by the component and
	 * different modules that might need different sets of data or different
	 * ordering requirements.
	 *
	 * @param   string  $id  A prefix for the store id.
	 *
	 * @return	string	A store id.
	 *
	 * @since	1.6
	 */
	protected function getStoreId($id = '')
	{
		// Compile the store id.
		$id .= ':' . $this->getState('filter.search');
		$id .= ':' . $this->getState('filter.state');

		return parent::getStoreId($id);
	}

	/**
	 * Build an SQL query to load the list data.
	 *
	 * @return	JDatabaseQuery
	 *
	 * @since	1.6
	 */
	protected function getListQuery()
	{
		// Create a new query object.
		$db    = $this->getDbo();
		$query = $db->getQuery(true);
		$input = Factory::getApplication()->input;

		// Select the required fields from the table.
		$query->select($this->getState('list.select', 'a.*'));
		$query->from('`#__tjfields_groups` AS a');

		// Join over the user field 'created_by'
		$query->select('user.name AS created_by_name');
		$query->join('LEFT', '#__users AS user ON user.id = a.created_by');

		// Filter by client
		$client = $this->getState('filter.client');

		if ($client)
		{
			$query->where('a.client = ' . $db->quote($client));
		}
		else
		{
			$query->where('a.client= ' . $db->quote($input->get('client', '', 'STRING')));
		}

		// Filter by published state
		$published = $this->getState('filter.state');

		if (is_numeric($published))
		{
			$query->where('a.state = ' . (int) $published);
		}
		elseif ($published === '')
		{
			$query->where('(a.state IN (0, 1))');
		}

		// Filter by search in title
		$search = $this->getState('filter.search');

		if (!empty($search))
		{
			if (stripos($search, 'id:') === 0)
			{
				$query->where('a.id = ' . (int) substr($search, 3));
			}
			else
			{
				$search = $db->Quote('%' . $db->escape($search, true) . '%');
				$query->where('( a.name LIKE ' . $search . '  OR  a.client LIKE ' . $search . ' )');
			}
		}

		// Add the list ordering clause.
		$orderCol  = $this->state->get('list.ordering');
		$orderDirn = $this->state->get('list.direction');

		if ($orderCol && $orderDirn)
		{
			$query->order($db->escape($orderCol . ' ' . $orderDirn));
		}

		return $query;
	}

	/**
	 * Build an SQL query to load the list data.
	 *
	 * @return	JDatabaseQuery
	 *
	 * @since	1.6
	 */
	public function getItems()
	{
		$items = parent::getItems();

		return $items;
	}

	/**
	 * Build an SQL query to load the list data.
	 *
	 * @param   INT  $items  id.
	 * @param   INT  $state  id.
	 *
	 * @return	JDatabaseQuery
	 *
	 * @since	1.6
	 */
	public function setItemState($items, $state)
	{
		if (is_array($items))
		{
			foreach ($items as $id)
			{
				$db = Factory::getDBO();
				$query = "UPDATE  #__tjfields_groups SET state = $state where id=" . $id;
				$db->setQuery($query);

				try
				{
					$db->execute();
				}
				catch (\RuntimeException $e)
				{
					$this->setError($e->getMessage());

					return false;
				}
			}
		}

		return true;
	}

	/**
	 * Build an SQL query to load the list data.
	 *
	 * @param   array  $id  id.
	 *
	 * @return	JDatabaseQuery
	 *
	 * @since	1.6
	 */
	public function deletegroup($id)
	{
		if (count($id) > 1)
		{
			$group_to_delet = implode(',', $id);
			$db    = Factory::getDBO();
			$query = "DELETE FROM #__tjfields_groups where id IN (" . $group_to_delet . ")";
			$db->setQuery($query);

			try
			{
				$db->execute();
			}
			catch (\RuntimeException $e)
			{
				$this->setError($e->getMessage());

				return false;
			}
		}
		else
		{
			$db = Factory::getDBO();
			$query = "DELETE FROM #__tjfields_groups where id =" . $id[0];
			$db->setQuery($query);

			try
			{
				$db->execute();
			}
			catch (\RuntimeException $e)
			{
				$this->setError($e->getMessage());

				return false;
			}
		}

		return true;
	}
}
