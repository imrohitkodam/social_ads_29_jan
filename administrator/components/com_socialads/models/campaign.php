<?php
/**
 * @version    SVN:<SVN_ID>
 * @package    Com_Socialads
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2015 TechJoomla. All rights reserved
 * @license    GNU General Public License version 2, or later
 */

// No direct access.
defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Utilities\ArrayHelper;
use Joomla\Registry\Registry;
use Joomla\CMS\Language\Text;

/**
 * Methods supporting a list of Socialads records.
 *
 * @since  1.6
 */
class SocialadsModelCampaign extends AdminModel
{
	/**
	 * @var   string  The prefix to use with controller messages.
	 *
	 * @since  1.6
	 */
	protected $text_prefix = 'COM_SOCIALADS';

	/**
	 * Constructor.
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 *
	 * @since   3.2
	 */
	public function __construct($config = array())
	{
		$config['event_after_delete'] = 'onAfterSocialAdCampaignDelete';

		parent::__construct($config);
	}

	/**
	 * Returns a Table object, always creating it.
	 *
	 * @param   string  $type    The table type to instantiate
	 * @param   string  $prefix  A prefix for the table class name. Optional.
	 * @param   array   $config  Configuration array for model. Optional.
	 *
	 * @return  JTable    A database object
	 */
	public function getTable($type = 'Campaign', $prefix = 'SocialadsTable', $config = array())
	{
		return Table::getInstance($type, $prefix, $config);
	}

	/**
	 * Method to get the record form.
	 *
	 * @param   array    $data      An optional array of data for the form to interogate.
	 * @param   boolean  $loadData  True if the form is to load its own data (default case), false if not.
	 *
	 * @return  JForm   A JForm object on success, false on failure
	 *
	 * @since  1.6
	 */
	public function getForm($data = array(), $loadData = true)
	{
		// Initialise variables.
		$app	= Factory::getApplication();

		// Get the form.
		$form = $this->loadForm('com_socialads.campaign', 'campaign', array('control' => 'jform', 'load_data' => $loadData));

		if (empty($form))
		{
			return false;
		}

		return $form;
	}

	/**
	 * Method to get the data that should be injected in the form.
	 *
	 * @return  mixed  The data for the form.
	 *
	 * @since  1.6
	 */
	protected function loadFormData()
	{
		// Check the session for previously entered form data.
		$data = Factory::getApplication()->getUserState('com_socialads.edit.campaign.data', array());

		if (empty($data))
		{
			$data = $this->getItem();
		}

		return $data;
	}

	/**
	 * Method to get a single record.
	 *
	 * @param   integer  $pk  The id of the primary key.
	 *
	 * @return  mixed   Object on success, false on failure.
	 *
	 * @since  1.6
	 */
	public function getItem($pk = null)
	{
		if ($item = parent::getItem($pk))
		{
			// Do any procesing on fields here if needed
		}

		return $item;
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
		if (empty($table->id))
		{
			// Set ordering to the last item if not set

			if (@$table->ordering === '')
			{
				$db = Factory::getDbo();
				$query = $db->getQuery(true);
				$query->select('MAX(ordering)');
				$query->from($db->quoteName('#__ad_campaign'));
				$db->setQuery($query);
				$max = $db->loadResult();
				$table->ordering = $max + 1;
			}
		}

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
	 * Method to save the campaign data.
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

		$table = $this->getTable();
		$isNew = $id ? false : true;

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
		$user    = Factory::getUser();
		$table   = $this->getTable();

		// Access checks.
		foreach ($pks as $i => $pk)
		{
			$table->reset();

			if ($table->load($pk))
			{
				$editStateAuthorise = $user->authorise('core.edit.state', 'com_socialads') == 1 ? true : false;
				$editOwnAuthorise   = $user->authorise('core.edit.own', 'com_socialads') == 1 ? true : false;

				if ($editStateAuthorise === false || ($editOwnAuthorise === false && $table->created_by !== $user->id))
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
		if (isset($data['start_date']) && isset($data['end_date']) && $data['start_date'] && $data['end_date'] && $data['start_date'] > $data['end_date'])
		{
			$app = Factory::getApplication();
			$app->enqueueMessage(Text::_("COM_SOCIALADS_START_DATE_MUST_BE_LESS_FROM_END_DATE"), "error");

			return false;
		}

		return parent::validate($form, $data, $group);
	}
}
