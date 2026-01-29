<?php
/**
 * @version    SVN:<SVN_ID>
 * @package    Com_Socialads
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2015 TechJoomla. All rights reserved
 * @license    GNU General Public License version 2, or later
 */

// No direct access.
defined('_JEXEC') or die();
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;


/**
 * importfields model class.
 *
 * @since  1.6
 */
class SocialadsModelImportfields extends BaseDatabaseModel
{
	/**
	 * Constructor that retrieves the ID from the request
	 *
	 * @since 1.6
	 **/
	public function __construct()
	{
		parent::__construct();
		$input = Factory::getApplication()->input;
		$array = $input->get('cid', 0, 'ARRAY');

		if (!empty($array[0]))
		{
			$this->setId((int) $array[0]);
		}

		$this->params = ComponentHelper::getParams('com_socialads');
	}

	/**
	 *  Method to set the import fields id
	 *
	 * @param   integer  $id  Id is set
	 *
	 * @return void
	 *
	 * @since 1.6
	 **/
	public function setId($id)
	{
		// Set id and wipe data
		$this->_id		= $id;
		$this->_data	= null;
	}

	/**
	 * function to check addata
	 *
	 * @return void
	 *
	 * @since 1.6
	 **/
	public function getAdData()
	{
		$query = $this->_db->getQuery(true);
		$query->select('COUNT(*)');
		$query->from($this->_db->quoteName('#__ad_data'));
		$this->_db->setQuery($query);

		return $this->_db->loadResult();
	}

	/**
	 * function to get column fields
	 *
	 * @return void
	 *
	 * @since 1.6
	 **/
	public function getcolfields()
	{
		$db = Factory::getDBO();
		$config = Factory::getConfig();
		$return = array();

		$dbname = $config->get('db');
		$dbprefix = $config->get('dbprefix');

		$query = "SELECT table_name
				FROM information_schema.tables
				WHERE table_schema = '" . $dbname . "'
					AND table_name = '" . $dbprefix
					. "ad_fields'";
		$db->setQuery($query);
		$coltable = $db->loadResult();

		if (!empty($coltable))
		{
			$query = "SHOW COLUMNS  FROM #__ad_fields";
			$this->_db->setQuery($query);

			return $this->_db->loadobjectList();
		}
		else
		{
			return $return;
		}
	}

	/**
	 * function to check pluginsdata
	 *
	 * @return void
	 *
	 * @since 1.6
	 **/
	public function getPluginData()
	{
		$condtion = array(0 => 'socialadstargeting');

		$query = $this->_db->getQuery(true);
		$query->select($this->_db->quoteName('extension_id', 'id'));
		$query->select($this->_db->quoteName('name'));
		$query->select($this->_db->quoteName('element'));
		$query->select($this->_db->quoteName('enabled'));
		$query->from($this->_db->quoteName('#__extensions'));
		$query->where($this->_db->quoteName('folder') . ' IN (' . implode(',', $this->_db->quote($condtion)) . ')');

		$this->_db->setQuery($query);

		return $this->_db->loadobjectList();
	}

	/**
	 * function to update pluginsdata
	 *
	 * @param   Object  $data  data is set
	 *
	 * @return  boolean
	 *
	 * @since 1.6
	 **/
	public function updatePluginData($data)
	{
		$dataCount = count($data->get('plugin', array(), 'ARRAY'));

		for ($count = 0; $count < $dataCount; $count++)
		{
			$chk = 0;

			if (isset($data->get('pluginchk')[$count]))
			{
				if ($data->get('pluginchk')[$count] == "on")
				{
					$chk = 1;
				}
				else
				{
					$chk = 0;
				}
			}

			$res = new stdClass;
			$pluginname = "";
			$pk = "";
			$pluginname = '#__extensions';
			$pk = 'extension_id';
			$res->extension_id = str_replace("plugin", "", $data->get('pluginchk')[$count]);
			$res->enabled = $chk;

			if (!$this->_db->updateObject($pluginname, $res, $pk))
			{
				echo $this->_db->stderr();

				return false;
			}
		}

		return true;
	}

	/**
	 * get Easy social fields
	 *
	 * @return Boolean
	 *
	 * @since 1.6
	 **/
	public function _getESFields()
	{
		// TODO: Use ignore field list from defines
		$eschk = SaCommonHelper::checkForSocialIntegration();
		$steps = '';

		if (!empty($eschk))
		{
			// Relationship not working
			$query = $this->_db->getQuery(true);
			$query->select($this->_db->quoteName('element'));
			$query->from($this->_db->quoteName('#__social_apps'));
			$query->where($this->_db->quoteName('element') . " IN (
				'boolean','checkbox','birthday','calendar','birthday','country','datetime',
				'dropdown','email','gender', 'multilist','textarea','textbox','multidropdown','url')");

			$this->_db->setQuery($query);
			$socialtypes = $this->_db->loadColumn();

			$qry = $this->_db->getQuery(true);
			$qry->select('m.*');
			$qry->select($this->_db->quoteName('f.title', 'field_label'));
			$qry->select($this->_db->quoteName('f.unique_key', 'mapping_fieldname'));
			$qry->select($this->_db->quoteName('a.element', 'type'));
			$qry->select($this->_db->quoteName('f.id', 'id'));
			$qry->from($this->_db->quoteName('#__social_fields', 'f'));
			$qry->join('LEFT', $this->_db->quoteName('#__ad_fields_mapping', 'm') . 'ON' . $this->_db->quoteName('f.id') . '=' . $this->_db->quoteName('m.mapping_fieldid'));
			$qry->join('LEFT', $this->_db->quoteName('#__social_apps', 'a') . 'ON' . $this->_db->quoteName('a.id') . '=' . $this->_db->quoteName('f.app_id'));
			$qry->where($this->_db->quoteName('f.state'). ' = ' . 1);
			$qry->where($this->_db->quoteName('a.type'). ' = ' . $this->_db->quote('fields'));
			$qry->where($this->_db->quoteName('a.element') . ' IN (' . implode(',', $this->_db->quote($socialtypes)) . ')');
			$qry->order($this->_db->quoteName('f.id') . ' ASC');

			$this->_db->setQuery($qry);
			$steps = $this->_db->loadobjectList();
		}
		// Empty check
		return $steps;
	}

	/**
	 * get Jom social fields
	 *
	 * @return Boolean
	 *
	 * @since 1.6
	 **/
	public function _getJSFields()
	{
		// TODO: Use ignore field list from defines
		// $SaCommonHelper = new SaCommonHelper;
		$jschk = SaCommonHelper::checkForSocialIntegration();

		if (!empty($jschk))
		{
			$qry = $this->_db->getQuery(true);
			$qry->select('m.*');
			$qry->select($this->_db->quoteName('f.name', 'field_label'));
			$qry->select($this->_db->quoteName('f.fieldcode', 'mapping_fieldname'));
			$qry->select($this->_db->quoteName('f.type'));
			$qry->select($this->_db->quoteName('f.id', 'id'));
			$qry->from($this->_db->quoteName('#__community_fields', 'f'));
			$qry->join('LEFT', $this->_db->quoteName('#__ad_fields_mapping', 'm') . 'ON' . $this->_db->quoteName('f.id') . '=' . $this->_db->quoteName('m.mapping_fieldid'));
			$qry->where($this->_db->quoteName('f.published'). ' = ' . 1);
			$qry->where($this->_db->quoteName('f.type'). ' <> ' . $this->_db->quote('group'));
			$qry->order($this->_db->quoteName('f.id') . ' ASC');

			$this->_db->setQuery($qry);

			return $this->_db->loadobjectList();
		}
	}

	/**
	 * Get Community Builder fields
	 *
	 * @return Boolean
	 *
	 * @since 1.6
	 **/
	public function _getCBFields()
	{
		// TODO: Use plugin id field list from defines

		$cbchk = SaCommonHelper::checkForSocialIntegration();

		if (!empty($cbchk))
		{
			$qry = $this->_db->getQuery(true);
			$qry->select('m.*');
			$qry->select($this->_db->quoteName('f.title', 'field_label'));
			$qry->select($this->_db->quoteName('f.name', 'mapping_fieldname'));
			$qry->select($this->_db->quoteName('f.type'));
			$qry->select($this->_db->quoteName('f.fieldid', 'id'));
			$qry->from($this->_db->quoteName('#__comprofiler_fields', 'f'));
			$qry->join('LEFT', $this->_db->quoteName('#__ad_fields_mapping', 'm') . 'ON' . $this->_db->quoteName('f.fieldid') . '=' . $this->_db->quoteName('m.mapping_fieldid'));
			$qry->where($this->_db->quoteName('f.published'). ' = ' . 1);
			$qry->where($this->_db->quoteName('f.sys'). ' <> ' . 1);

			$this->_db->setQuery($qry);

			return $this->_db->loadobjectList();
		}
	}

	/**
	 * function for getting jomsocial fields
	 *
	 * @return fields
	 *
	 * @since 3.0
	 */
	public function getImportFields()
	{
		$integration = $this->params->get('social_integration');

		if ($integration == 'Community Builder')
		{
			$integration = "CB";
		}
		elseif($integration == 'JomSocial')
		{
			$integration = "JS";
		}
		elseif($integration == 'EasySocial')
		{
			$integration = "ES";
		}

		switch ($integration)
		{
			case 'CB':
				return $this->_getCBFields();
			break;

			case 'JS':
				return $this->_getJSFields();
			break;

			case 'ES':
				return $this->_getESFields();
			break;
		}

		return;
	}
	// Function getImportFields ends here

	/**
	 * function for getting fields options
	 *
	 * @param   string  $type  Type of options
	 *
	 * @return void
	 *
	 * @since 3.0
	 */
	public function getFieldOptions($type = 'text')
	{
		$integration = $this->params->get('social_integration');

		if ($integration == 'Community Builder')
		{
			$integration = "CB";
		}
		else
		{
				$integration = "JS";
		}

		switch ($integration)
		{
			case 'CB':
				return $this->_getCBOptions($type);
			break;

			case 'JS':
				return $this->_getJSOptions($type);
			break;
		}
	}

	/**
	 * function for getting jomsocial options
	 *
	 * @param   string  $type  Type of options
	 *
	 * @return void
	 *
	 * @since 3.0
	 */
	public function _getJSOptions($type)
	{
		switch ($type)
		{
			case 'text':
			case 'textarea':
			case 'email':
			case 'url':

				return 'mappinglistt';
			break;

			case 'date':
			case 'time':
			case 'integer':
			case 'birthdate';

			return 'mappinglistd';
			break;

			default:

			return 'mappinglists';
			break;
		}
	}

	/**
	 * function for getting community Builder options
	 *
	 * @param   string  $type  Type of options
	 *
	 * @return void
	 *
	 * @since 3.0
	 */
	public function _getCBOptions($type)
	{
		switch ($type)
		{
			case 'text':
			case 'textarea':
			case 'editorta';
			case 'emailaddress':
			case 'webaddress':
			return 'mappinglistt';
			break;

			case 'date':
			case 'time':
			return 'mappinglistd';
			break;

			default:
			return 'mappinglists';
			break;
		}
	}

	/**
	 * function to create table
	 *
	 * @return table
	 *
	 * @since 3.0
	 */
	public function create_ad_fields()
	{
		$query = "CREATE TABLE IF NOT EXISTS `#__ad_fields` (
			`adfield_id` int(11) NOT NULL auto_increment,
			`adfield_ad_id` int(11) NOT NULL,
			PRIMARY KEY  (`adfield_id`)
			) ENGINE=MyISAM";
			$this->_db->setQuery($query);
			$this->_db->execute();

			return;
	}

	/**
	 * Function store importfields starts here
	 *
	 * @return boolean
	 *
	 * @since 3.0
	 **/
	public function store()
	{
		$data = Factory::getApplication()->input->post;
		$this->updatePluginData($data);

		if ($data->get('resetall', '', 'STRING') == 1)
		{
			$query = "DROP TABLE IF EXISTS `#__ad_fields`";
			$this->_db->setQuery($query);
			$this->_db->execute();

			$query = "TRUNCATE TABLE `#__ad_fields_mapping`";
			$this->_db->setQuery($query);
			$this->_db->execute();

			return 1;
		}

		if ($data->get('boxchecked', '', 'STRING') == 0)
		{
			// Return false;
		}

		$i      = 0;
		$iarray = array();
		$addcol = '';

		foreach ($data->get('mappinglist', array(), 'ARRAY') as $mapping)
		{
			// Prepare import fields for saving into ad_fields_mapping table
			$field = $this->getTable('importfields');
			$field->mapping_fieldid = $mapping['fieldid'];
			$field->mapping_fieldtype = $mapping['fieldtype'];

			if (!$mapping['fieldtype'])
			{
				continue;
			}

			$field->mapping_label   = $mapping['label'];
			$field->mapping_options = (isset($mapping['options'])) ? $mapping['options'] : '';
			$field->mapping_match   = $data->get('match', array(), 'ARRAY')[$mapping['fieldid']];
			$fieldname = strtolower(
									preg_replace('/\s*[^a-zA-Z0-9]+\s*/i', '_', $data->get('mappinglist', array(), 'ARRAY')[$mapping['fieldid']]['fieldcode'])
								);

			if ($mapping['fieldtype'] == "numericrange")
			{
				$addcol = " add " . $fieldname . '_low' . " int(11), add " . $fieldname . '_high' . " int(11) ";
				$field->mapping_fieldname = $fieldname;
			}

			elseif ($mapping['fieldtype'] == "daterange")
			{
				$addcol = " add " . $fieldname . '_low' . " datetime, add " . $fieldname . '_high' . " datetime ";
				$field->mapping_fieldname = $fieldname;
			}
			elseif ($mapping['fieldtype'] == "date")
			{
				$addcol = " add " . $fieldname . " datetime ";
				$field->mapping_fieldname = $fieldname;
			}
			elseif ($mapping['fieldtype'] == "singleselect")
			{
				$addcol = " add " . $fieldname . " text ";
				$field->mapping_fieldname = $fieldname;
				$iarray[] = $fieldname;
			}
			elseif($mapping['fieldtype'] == "multiselect")
			{
				$addcol = " add " . $fieldname . " text ";
				$field->mapping_fieldname = $fieldname;
				$iarray[] = $fieldname;
			}
			else
			{
				$addcol = " add " . $fieldname . " text ";
				$field->mapping_fieldname = $fieldname;
				$iarray[] = $fieldname;
			}

			// Store the table to the database
			if (!$field->store())
			{
				$this->setError($this->_db->getErrorMsg());

				return false;
			}

			$this->create_ad_fields();

			// Write query to add the column
			$query = "ALTER table #__ad_fields $addcol";
			$this->_db->setQuery($query);
			$this->_db->execute();

			if (($mapping['fieldtype'] != 'numericrange') && ($mapping['fieldtype'] != 'daterange'))
			{
				// Write query to set data type to none
				$query = "ALTER TABLE `#__ad_fields` CHANGE `$fieldname` `$fieldname` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ";
				$this->_db->setQuery($query);
				$this->_db->execute();
			}
		}

		return 2;
	}
	// End function store()
}
