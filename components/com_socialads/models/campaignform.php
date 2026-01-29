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

use Joomla\CMS\MVC\Model\FormModel;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Utilities\ArrayHelper;
use Joomla\Registry\Registry;

/**
 * Socialads model.
 *
 * @since  1.6
 */
class SocialadsModelCampaignForm extends FormModel
{
	protected $item = null;

	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @return void
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
		if ($this->item === null)
		{
			$this->item = false;

			if (empty($id))
			{
				$id = $this->getState('campaign.id');
			}

			// Get a level row instance.
			$table = $this->getTable();

			// Attempt to load the row.
			if ($table !== false && $table->load($id))
			{
				$user = Factory::getUser();
				$id   = $table->id;
				$canEdit = $user->authorise('core.edit', 'com_socialads') || $user->authorise('core.create', 'com_socialads');

				if (!$canEdit && $user->authorise('core.edit.own', 'com_socialads'))
				{
					$canEdit = $user->id == $table->created_by;
				}

				if (!$canEdit)
				{
					throw new Exception(Text::_('JERROR_ALERTNOAUTHOR'), 500);
				}

				// Check published state.
				if ($published = $this->getState('filter.published'))
				{
					if ($table->state != $published)
					{
						return $this->item;
					}
				}

				// Convert the JTable to a clean JObject.
				$properties  = $table->getProperties(1);
				$this->item = ArrayHelper::toObject($properties, 'JObject');
			}
		}

		return $this->item;
	}

	/**
	 * Proxy for getModel.
	 *
	 * @param   string  $type    The model name. Optional.
	 * @param   string  $prefix  The class prefix. Optional.
	 * @param   array   $config  The array of possible config values. Optional.
	 *
	 * @return  JModelLegacy
	 *
	 * @since   1.6
	 */
	public function getTable($type = 'Campaign', $prefix = 'SocialadsTable', $config = array())
	{
		$this->addTablePath(JPATH_ADMINISTRATOR . '/components/com_socialads/tables');

		return Table::getInstance($type, $prefix, $config);
	}

	/**
	 * Method to get item id using alies
	 *
	 * @param   STRING  $alias  alies
	 *
	 * @return    boolean        True on success, false on failure.
	 *
	 * @since    1.6
	 */
	public function getItemIdByAlias($alias)
	{
		$table = $this->getTable();

		$table->load(array( 'alias' => $alias ));

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
	 * @return    boolean       True on success, false on failure.
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
	 * Method to get the profile form.
	 *
	 * The base form is loaded from XML
	 *
	 * @param   array    $data      An optional array of data for the form to interogate.
	 * @param   boolean  $loadData  True if the form is to load its own data (default case), false if not.
	 *
	 * @return  boolean|object JForm    A JForm object on success, false on failure
	 *
	 * @since    1.6
	 */
	public function getForm($data = array(), $loadData = true)
	{
		// Get the form.
		$form = $this->loadForm('com_socialads.campaign', 'campaignform', array('control'   => 'jform', 'load_data' => $loadData));

		if (empty($form))
		{
			return false;
		}

		return $form;
	}

	/**
	 * Method to get the data that should be injected in the form.
	 *
	 * @return    mixed    The data for the form.
	 *
	 * @since    1.6
	 */
	protected function loadFormData()
	{
		$data = Factory::getApplication()->getUserState('com_socialads.edit.campaign.data', array());

		if (empty($data))
		{
			$data = $this->getData();
		}

		return $data;
	}

	/**
	 * Method to save the form data.
	 *
	 * @param   array  $data  The form data.
	 *
	 * @return  mixed   The user id on success, false on failure.
	 *
	 * @since    1.6
	 */
	public function save($data)
	{
		$id    = (!empty($data['id'])) ? $data['id'] : (int) $this->getState('campaign.id');
		$state = (!empty($data['state'])) ? 1 : 0;
		$data['ordering'] = (isset($data['ordering'])) ? $data['ordering'] : 0;
		$data['checked_out'] = (isset($data['checked_out'])) ? $data['checked_out'] : 0;

		$defnull = array('start_date','end_date');
		foreach ($defnull as $val)
		{
			/* define the rules when the value is set NULL */
			if (!strlen($data[$val]))
			{
				$data[$val] = NULL;
			}
		}
		
		$isNew = $id ? false : true;
		$user  = Factory::getUser();

		if ($id)
		{
			// Check the user can edit this item
			$authorised = $user->authorise('core.edit', 'com_socialads') || $authorised = $user->authorise('core.edit.own', 'com_socialads');

			if ($user->authorise('core.edit.state', 'com_socialads') !== true && $state == 1)
			{
				// The user cannot edit the state of the item.
				$data['state'] = 0;
			}
		}
		else
		{
			// Check the user can create new items in this section
			$authorised = $user->authorise('core.create', 'com_socialads');

			if ($user->authorise('core.edit.state', 'com_socialads') !== true && $state == 1)
			{
				// The user cannot edit the state of the item.
				$data['state'] = 0;
			}
		}

		if ($authorised !== true)
		{
			throw new Exception(Text::_('JERROR_ALERTNOAUTHOR'), 403);
		}

		$data['created_by'] = $user->id;

		$table = $this->getTable();

		if ($table->save($data) === true)
		{
			PluginHelper::importPlugin('socialads');
			Factory::getApplication()->triggerEvent('onAfterSocialAdCampaignSave', array($table, $isNew));

			return $table->id;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Get permission details to save
	 *
	 * @return  boolean|object table
	 *
	 * @since   1.5
	 */
	public function getCanSave()
	{
		$table = $this->getTable();

		return $table !== false;
	}

	/**
	 * Method to delete campaign
	 *
	 * @param   array  $cid  The array of campaign ids.
	 *
	 * @return  boolean true or false
	 *
	 * @since   2.2
	 */
	public function delete($cid)
	{
		$app     = Factory::getApplication();
		$context = $app->input->get('option');
		$user    = Factory::getUser();

		if (!empty($cid))
		{
			foreach ($cid as $i => $campId)
			{
				$table = $this->getTable('Campaign', 'SocialadsTable');
				$table->load((int) $campId);

				// Checking here is log-in user has access to delete
				$deleteOwnAuthorise = $user->authorise('core.delete', 'com_socialads') == 1 ? true : false;

				if ($table->created_by !== $user->get('id') && $app->isClient("site"))
				{
					// Prune items that you can't change.
					unset($cid[$i]);

					return false;
				}
				elseif ($deleteOwnAuthorise === false)
				{
					// Prune items that you can't change.
					unset($cid[$i]);

					return false;
				}

				if (!$table->delete((int) $campId))
				{
					return false;
				}

				PluginHelper::importPlugin('socialads');
				Factory::getApplication()->triggerEvent('onAfterSocialAdCampaignDelete', array($context, $table));
			}

			return true;
		}
	}

	/**
	 * Method to change the published state of one or more records.
	 *
	 * @param   array    &$pks   A list of the primary keys to change.
	 * @param   integer  $value  The value of the published state.
	 *
	 * @return  boolean  True on success.
	 *
	 * @since   3.1.15
	 */
	public function publish(&$pks, $value = 1)
	{
		$app     = Factory::getApplication();
		$context = $app->input->get('option');
		$pks     = (array) $pks;
		$user = Factory::getUser();
		$table = $this->getTable();

		// Access checks.
		foreach ($pks as $i => $pk)
		{
			$table->reset();

			if ($table->load($pk))
			{
				$editStateAuthorise = $user->authorise('core.edit.state', 'com_socialads') == 1 ? true : false;
				$editOwnAuthorise   = $user->authorise('core.edit.own', 'com_socialads') == 1 ? true : false;

				if ($editStateAuthorise === false || ($editOwnAuthorise === false && ($table->created_by !== $user->get('id') && $app->isClient("site"))))
				{
					// Prune items that you can't change.
					unset($pks[$i]);

					return false;
				}
			}
		}

		// Check if there are items to change
		if (!count($pks))
		{
			return true;
		}

		// Attempt to change the state of the records.
		if (!$table->publish($pks, $value, $user->get('id')))
		{
			$this->setError($table->getError());

			return false;
		}

		PluginHelper::importPlugin('socialads');
		Factory::getApplication()->triggerEvent('onAfterSocialAdCampaignChangeState', array($context, $pks, $value));

		return true;
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

	/**
	 * Method to validate the Organization contact form data from server side.
	 *
	 * @param   \JForm  $form   The form to validate against.
	 * @param   array   $data   The data to validate.
	 * @param   string  $group  The name of the field group to validate.
	 *
	 * @return  array|boolean  Array of filtered data if valid, false otherwise.
	 *
	 * @since   4.2.1
	 */
	public function validate($form, $data, $group = null)
	{
		if (isset($data['start_date']) && isset($data['end_date']) && $data['start_date'] > $data['end_date'])
		{
			$app = Factory::getApplication();
			$app->enqueueMessage(Text::_("COM_SOCIALADS_START_DATE_MUST_BE_LESS_FROM_END_DATE"), "error");
			
			return false;
		}

		return parent::validate($form, $data, $group);
	}
}
