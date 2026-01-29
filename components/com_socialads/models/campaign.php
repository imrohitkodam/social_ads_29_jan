<?php
/**
 * @version    SVN:<SVN_ID>
 * @package    SocialAds
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2015 TechJoomla. All rights reserved
 * @license    GNU General Public License version 2, or later
 */
// No direct access.
defined('_JEXEC') or die;
use Joomla\CMS\Object\CMSObject;

use Joomla\CMS\MVC\Model\ItemModel;
use Joomla\CMS\Factory;
use Joomla\CMS\Table\Table;

/**
 * Socialads model.
 *
 * @since  1.6
 */
class SocialadsModelCampaign extends ItemModel
{
	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @return  void
	 *
	 * @since    1.6
	 */
	protected function populateState()
	{
		$app = Factory::getApplication('com_socialads');

		// Load state from the request userState on edit or from the passed variable on default
		if (Factory::getApplication()->input->get('layout') == 'edit')
		{
			$id = Factory::getApplication()->getUserState('com_socialads.edit.campaign.id');
		}
		else
		{
			$id = Factory::getApplication()->input->get('id');
			Factory::getApplication()->setUserState('com_socialads.edit.campaign.id', $id);
		}

		$this->setState('campaign.id', $id);

		// Load the parameters.
		$params       = $app->getParams();
		$params_array = $params->toArray();

		if (isset($params_array['item_id']))
		{
			$this->setState('campaign.id', $params_array['item_id']);
		}

		$this->setState('params', $params);
	}

	/**
	 * Method to get an ojbect.
	 *
	 * @param   integer  $id  The id of the object to get.
	 *
	 * @return    mixed    Object on success, false on failure.
	 */
	public function &getData($id = null)
	{
		if ($this->_item === null)
		{
			$this->_item = false;

			if (empty($id))
			{
				$id = $this->getState('campaign.id');
			}

			// Get a level row instance.
			$table = $this->getTable();

			// Attempt to load the row.
			if ($table->load($id))
			{
				// Check published state.
				if ($published = $this->getState('filter.published'))
				{
					if ($table->state != $published)
					{
						return $this->_item;
					}
				}

				// Convert the JTable to a clean JObject.
				$properties  = $table->getProperties(1);
				$this->_item = JArrayHelper::toObject($properties, 'JObject');
			}
		}

		if (isset($this->_item->created_by))
		{
			$this->_item->created_by_name = Factory::getUser($this->_item->created_by)->name;
		}

		return $this->_item;
	}

	/**
	 * get item by alias.
	 *
	 * @param   ARRAY  $type    Campaign type
	 *
	 * @param   ARRAY  $prefix  prefix
	 *
	 * @param   ARRAY  $config  config
	 *
	 * @return void
	 *
	 * @since  1.6
	 */
	public function getTable($type = 'Campaign', $prefix = 'SocialadsTable', $config = array())
	{
		$this->addTablePath(JPATH_ADMINISTRATOR . '/components/com_socialads/tables');

		return Table::getInstance($type, $prefix, $config);
	}

	/**
	 * get item by alias.
	 *
	 * @param   ARRAY  $alias  array of alias
	 *
	 * @return void
	 *
	 * @since  1.6
	 */
	public function getItemIdByAlias($alias)
	{
		$table = $this->getTable();

		$table->load(array('alias' => $alias));

		return $table->id;
	}

	/**
	 * Method to check in an item.
	 *
	 * @param   integer  $id  The id of the row to check out.
	 *
	 * @return    boolean        True on success, false on failure.
	 *
	 * @since    1.6
	 */
	public function checkin($id = null)
	{
		// Get the id.
		$id = (!empty($id)) ? $id : (int) $this->getState('campaign.id');

		if ($id)
		{
			// Initialise the table
			$table = $this->getTable();

			// Attempt to check the row in.
			if (method_exists($table, 'checkin'))
			{
				if (!$table->checkin($id))
				{
					return false;
				}
			}
		}

		return true;
	}

	/**
	 * Method to check out an item for editing.
	 *
	 * @param   integer  $id  The id of the row to check out.
	 *
	 * @return  boolean  True on success, false on failure.
	 *
	 * @since    1.6
	 */
	public function checkout($id = null)
	{
		// Get the user id.
		$id = (!empty($id)) ? $id : (int) $this->getState('campaign.id');

		if ($id)
		{
			// Initialise the table
			$table = $this->getTable();

			// Get the current user object.
			$user = Factory::getUser();

			// Attempt to check the row out.
			if (method_exists($table, 'checkout'))
			{
				if (!$table->checkout($user->get('id'), $id))
				{
					return false;
				}
			}
		}

		return true;
	}

	/**
	 * get cat name
	 *
	 * @param   ARRAY  $id  array of campaign ids
	 *
	 * @return void
	 *
	 * @since  1.6
	 */
	public function getCategoryName($id)
	{
		$db    = Factory::getDbo();
		$query = $db->getQuery(true);
		$query->select($db->qn('title'))
			->from($db->qn('#__categories'))
			->where($db->qn('id') . ' = ' . $id);
		$db->setQuery($query);

		return $db->loadObject();
	}

	/**
	 * delete campaign.
	 *
	 * @param   ARRAY  $id  array of campaign ids
	 *
	 * @return void
	 *
	 * @since  1.6
	 */
	public function delete($id)
	{
		$table = $this->getTable();

		return $table->delete($id);
	}

	/**
	 * Prepare and sanitise the table prior to saving.
	 *
	 * @param   integer  $table  The id of table is passed
	 *
	 * @return  integer on success
	 *
	 * @since  1.6
	 */
	protected function prepareTable($table)
	{
		/* define which columns can have NULL values */
		$defnull = array('start_date','end_date');
		foreach ($defnull as $val)
		{
			/* define the rules when the value is set NULL */
			if (!strlen($table->$val))
			{
				$table->$val = NULL;
			}
		}
	}
}
