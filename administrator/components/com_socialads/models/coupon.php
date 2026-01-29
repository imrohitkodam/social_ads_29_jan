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

/**
 * Socialads model.
 *
 * @since  1.6
 */
class SocialadsModelCoupon extends AdminModel
{
	/**
	 * Constructor.
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 *
	 * @since   3.2
	 */
	public function __construct($config = array())
	{
		$config['event_after_save'] = 'onAfterSocialAdCouponSave';
		$config['event_after_delete'] = 'onAfterSocialAdCouponDelete';
		$config['event_change_state'] = 'onAfterSocialAdCouponChangeState';

		parent::__construct($config);
	}

	/**
	 * @var   string  The prefix to use with controller messages.
	 *
	 * @since  1.6
	 */
	protected $text_prefix = 'COM_SOCIALADS';
	/**
	 * Returns a reference to the a Table object, always creating it.
	 *
	 * @param   string  $type    The table type to instantiate
	 * @param   string  $prefix  A prefix for the table class name. Optional.
	 * @param   array   $config  Configuration array for model. Optional.
	 *
	 * @return  JTable   A database object
	 *
	 * @since  1.6
	 */
	public function getTable($type = 'Coupon', $prefix = 'SocialadsTable', $config = array())
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
		$app = Factory::getApplication();

		// Get the form.
		$form = $this->loadForm('com_socialads.coupon', 'coupon', array('control' => 'jform', 'load_data' => $loadData));

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
		$data = Factory::getApplication()->getUserState('com_socialads.edit.coupon.data', array());

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
				$query->from($db->quoteName('#__ad_coupon'));
				$db->setQuery($query);
				$max = $db->loadResult();
				$table->ordering = $max + 1;
			}
		}
	}

	/**
	 * To return a code
	 *
	 * @param   integer  $code  code get
	 *
	 * @return  integer on success
	 *
	 * @since  1.6
	 */
	public function getcode($code)
	{
		$db = Factory::getDBO();
		$query = $db->getQuery(true);
		$query->select($db->quoteName('id'));
		$query->from($db->quoteName('#__ad_coupon'));
		$query->where($db->quoteName('code') . ' = ' . $db->quote($db->escape(trim($code))));
		$db->setQuery($query);
		$exists = $db->loadResult();

		if ($exists)
		{
			return 1;
		}
		else
		{
			return 0;
		}
	}

	/**
	 * To check for code
	 *
	 * @param   integer  $code  code
	 * @param   integer  $id    The id of table is passed
	 *
	 * @return  integer on success
	 *
	 * @since  1.6
	 */
	public function getselectcode($code,$id)
	{
		$db	= Factory::getDBO();
		$query = $db->getQuery(true);
		$query->select($db->quoteName('code'));
		$query->from($db->quoteName('#__ad_coupon'));
		$query->where($db->quoteName('id') . ' <> ' . $id);
		$query->where($db->quoteName('code') . ' = ' . $db->quote($db->escape(trim($code))));

		$db->setQuery($query);
		$exists = $db->loadResult();

		if ($exists)
		{
			return 1;
		}
		else
		{
			return 0;
		}
	}

	/**
	 * Method to validate the form data.
	 *
	 * @param   \JForm  $form   The form to validate against.
	 * @param   array   $data   The data to validate.
	 * @param   string  $group  The name of the field group to validate.
	 *
	 * @return  array|boolean  Array of filtered data if valid, false otherwise.
	 *
	 * @since   3.4.0
	 */

	public function validate($form, $data, $group = null)
	{
		$return = true;

		$todaydate = Factory::getDate('now', 'UTC');

		if (empty($data['from_date']))
		{
			$data['from_date'] = Factory::getDate('now', Factory::getConfig()->get('offset'))->toSql(true);
		}

		if (empty($data['exp_date']))
		{
			$data['exp_date'] = null;
		}

		if (!empty($data['max_use']) && !empty($data['max_per_user']))
		{
			if ($data['max_use'] < $data['max_per_user'])
			{
				$data['max_per_user'] = null;
			}
		}

		$data = parent::validate($form, $data, $group);

		return (!$return) ? false : $data;
	}
}
