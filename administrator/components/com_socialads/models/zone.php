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
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Plugin\PluginHelper;

/**
 * Socialads model.
 *
 * @since  1.6
 */
class SocialadsModelZone extends AdminModel
{
	/**
	 * @var   string  $text_prefix  The prefix to use with controller messages.
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
		$config['event_after_delete'] = 'onAfterSocialAdZoneDelete';
		$config['event_change_state'] = 'onAfterSocialAdZoneChangeState';

		parent::__construct($config);
	}

	/**
	 * Returns a reference to the a Table object, always creating it.
	 *
	 * @param   string  $type    The table type to instantiate
	 * @param   string  $prefix  A prefix for the table class name. Optional.
	 * @param   array   $config  Configuration array for model. Optional.
	 *
	 * @return  JTable  A database object
	 *
	 * @since  1.6
	 */
	public function getTable($type = 'Zone', $prefix = 'SocialadsTable', $config = array())
	{
		return Table::getInstance($type, $prefix, $config);
	}

	/**
	 * Method to get the record form.
	 *
	 * @param   array    $data      An optional array of data for the form to interogate.
	 * @param   boolean  $loadData  True if the form is to load its own data (default case), false if not.
	 *
	 * @return  JForm  A JForm object on success, false on failure
	 *
	 * @since1.6
	 */
	public function getForm($data = array(), $loadData = true)
	{
		// Initialise variables.
		$app = Factory::getApplication();

		// Get the form.
		$form = $this->loadForm('com_socialads.zone', 'zone', array('control' => 'jform', 'load_data' => $loadData));

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
		$data = Factory::getApplication()->getUserState('com_socialads.edit.zone.data', array());

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
	 * @return  mixed  Object on success, false on failure.
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
				$query->from($db->quoteName('#__ad_zone'));
				$db->setQuery($query);
				$max = $db->loadResult();
				$table->ordering = $max + 1;
			}
		}
	}

	/**
	 * Method to save the form data.
	 *
	 * @param   array  $data  The form data.
	 *
	 * @return   mixed  The user id on success, false on failure.
	 *
	 * @since  1.6
	 */
	public function save($data)
	{
		$com_params = ComponentHelper::getParams('com_socialads');
		$id = (!empty($data['id'])) ? $data['id'] : (int) $this->getState('zone.id');
		$state = (!empty($data['com_socialads'])) ? 1 : 0;

		$isNew = (!empty($id)) ? false : true;
		$user = Factory::getUser();
		$app = Factory::getApplication()->input;

		$data1 = $app->post->getArray(array());

		$layout = $data1['layout_select'];

		// Get all socialads layout plugins name
		$layoutPlugin   = PluginHelper::getPlugin('socialadslayout');
		$plgLayoutArray = array();

		foreach ($layoutPlugin as $plg)
		{
			$plgLayoutArray[] = $plg->name;
		}
		
		// Check here is provided layout valid
		if (!empty(array_diff($layout, $plgLayoutArray)))
		{
			return false;
		}

		$layout = implode(',', $layout);
		$layout = str_replace(',', '|', $layout);

		$data['layout'] = $layout;

		if ($data1['affiliate'] == 1)
		{
			$data['ad_type'] = "|" . $data['ad_type'] . "||affiliate|";
		}
		else
		{
			$data['ad_type'] = "|" . $data['ad_type'] . "|";
		}

		$table = $this->getTable();

		// Bind data
		if (!$table->bind($data))
		{
			$this->setError($table->getError());

			return false;
		}

		// Validate country codes to check for duplication
		if (!$table->check())
		{
			$this->setError($table->getError());

			return false;
		}

		// Attempt to save data
		if (parent::save($data))
		{
			/*
			 * +Manoj - Tweak - to avoid redrect where it shows record with id=1
			 * return true;
			 * $app->setUserState('com_socialads.edit.zone.id', $table->id);
			 */

			$table = $this->getTable();
			$key = $table->getKeyName();
			$pk = (!empty($data[$key])) ? $data[$key] : (int) $this->getState($this->getName() . '.id');

			PluginHelper::importPlugin('socialads');
			Factory::getApplication()->triggerEvent('onAfterSocialAdZoneSave', array($pk, $isNew));

			return $pk;
		}

		return true;
	}
}
