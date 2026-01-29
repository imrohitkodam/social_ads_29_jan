<?php
/**
 * @version    SVN: <svn_id>
 * @package    Tjfields
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2015 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

/**
 * Class for Geo helper to get region and states
 *
 * @package     Tjfields
 * @subpackage  component
 * @since       1.0
 */
class TjGeoHelper
{
	public $_tjlang, $_db;
	
	/**
	 * Toolbar name
	 *
	 * @var    string
	 */
	protected $name = array();

	/**
	 * Stores the singleton instances of various TjGeoHelper.
	 *
	 * @var    JToolbar
	 * @since  2.5
	 */
	protected static $instances = array();

	/**
	 * Constructor
	 *
	 * @param   string  $name  The TjGeoHelper name.
	 *
	 * @since   1.5
	 */
	public function __construct($name = 'TjGeoHelper')
	{
		$this->name = $name;

		// Load lang file for countries
		$this->_tjlang = Factory::getLanguage();
		$this->_tjlang->load('tjgeo.countries', JPATH_SITE, null, false, true);
		$this->_tjlang->load('tjgeo.regions', JPATH_SITE, null, false, true);
		$this->_tjlang->load('tjgeo.cities', JPATH_SITE, null, false, true);
		$this->_db = Factory::getDbo();
	}

	/**
	 * Returns the global JToolbar object, only creating it if it
	 * doesn't already exist.
	 *
	 * @param   string  $name  The name of the TjGeoHelper.
	 *
	 * @return  JToolbar  The JToolbar object.
	 *
	 * @since   1.5
	 */
	public static function getInstance($name = 'TjGeoHelper')
	{
		if (empty(self::$instances[$name]))
		{
			self::$instances[$name] = new TjGeoHelper($name);
		}

		return self::$instances[$name];
	}

	/**
	 * Returns the country name fro ID
	 *
	 * @param   int  $countryId  The name of the TjGeoHelper.
	 *
	 * @return  text  $country  country name
	 *
	 * @since   1.5
	 */
	public function getCountryNameFromId($countryId)
	{
		if (empty($countryId))
		{
			return false;
		}

		$query = $this->_db->getQuery(true);
		$query->select('country, country_jtext');
		$query->from('#__tj_country');

		if (!empty($countryId))
		{
			$query->where('id = ' . $countryId);
		}

		$this->_db->setQuery($query);
		$country = $this->_db->loadObject();

		$countryName = $this->getCountryText($country->country_jtext);

		if ($countryName)
		{
			return $countryName;
		}
		else
		{
			return $country->country;
		}
	}

	/**
	 * Returns the jtext for country
	 *
	 * @param   string  $countryJtext  jtext string
	 *
	 * @return  jtext  $countryJtext  value of country language string
	 *
	 * @since   1.5
	 */
	public function getCountryText($countryJtext)
	{
		if ($this->_tjlang->hasKey(strtoupper($countryJtext)))
		{
			return Text::_($countryJtext, true);
		}
		elseif ($countryJtext !== '')
		{
			return null;
		}
	}

	/**
	 * Returns the country list for partcular client like jgive
	 *
	 * @param   string  $component_nm  name of component
	 *
	 * @return  countrylist
	 *
	 * @since   1.5
	 */
	public function getCountryList($component_nm = "")
	{
		$query = $this->_db->getQuery(true);
		$query->select("`id`, `country`,`country_jtext`,`country_dial_code`")->from('#__tj_country');

		if ($component_nm)
		{
			$query->where($component_nm . "=1");
		}

		$query->order($this->_db->escape('ordering ASC'));
		$this->_db->setQuery((string) $query);
		$countryList = $this->_db->loadAssocList();

		// Get jtext value.
		foreach ($countryList as $key => $country)
		{
			if ($country['country_jtext'])
			{
				$jtext = $this->getCountryText($country['country_jtext']);

				if ($jtext)
				{
					$countryList[$key]['country'] = $jtext;
				}
			}
		}
		// Get trasalated string.
		return $countryList;
	}

	/**
	 * Gives region list according.( field region gives you region name in current language) .
	 *
	 * @param   string  $countryId     id of country
	 * @param   string  $component_nm  name of component
	 * @param   string  $orderingCol   order by table column eg region
	 *
	 * @return  regionlist
	 *
	 * @since   1.5
	 */
	public function getRegionList($countryId, $component_nm = "", $orderingCol = "region")
	{
		$this->_db = Factory::getDBO();
		$query     = $this->_db->getQuery(true);
		$query->select("id, region,region_jtext");
		$query->from('#__tj_region');
		$query->where('country_id=' . $this->_db->quote($countryId));
		$query->order($this->_db->escape($orderingCol . ' ASC'));

		if ($component_nm)
		{
			$query->where($component_nm . "=1");
		}

		$this->_db->setQuery((string) $query);
		$regionList = $this->_db->loadAssocList();

		// Get jtext value.
		foreach ($regionList as $key => $region)
		{
			if ($region['region_jtext'])
			{
				$jtext = $this->getRegionText($region['region_jtext']);

				if ($jtext)
				{
					$regionList[$key]['region'] = $jtext;
				}
			}
		}
		// Get trasalated string.
		return $regionList;
	}

	/**
	 * Gives region list according to country ID
	 *
	 * @param   string  $countryId  id of country
	 *
	 * @return  regionlist
	 *
	 * @since   1.5
	 */
	public function getRegionListFromCountryID($countryId)
	{
		if (is_numeric($countryId))
		{
			$query = "SELECT r.id,r.region FROM #__tj_region AS r LEFT JOIN #__tj_country as c
					ON r.country_id=c.id where c.id=\"" . $countryId . "\"";
			$this->_db->setQuery($query);
			$rows = $this->_db->loadAssocList();

			return $rows;
		}
	}

	/**
	 * Method gives region name ( for current language if exist) from  region Id.
	 *
	 * @param   string  $regionId  id of region
	 *
	 * @return  regionlist
	 *
	 * @since   1.5
	 */
	public function getRegionNameFromId($regionId)
	{
		if (empty($regionId))
		{
			return false;
		}

		$query = $this->_db->getQuery(true);
		$query->select('region, region_jtext');
		$query->from('#__tj_region');

		if ($regionId)
		{
			$query->where('id = ' . $regionId);
		}

		$this->_db->setQuery($query);
		$res = $this->_db->loadObject();

		// Get jtext value.
		$jtext = $this->getRegionText($res->region_jtext);

		if ($jtext)
		{
			return $jtext;
		}
		else
		{
			return $res->region;
		}
	}

	/**
	 * Method gives region name in current  language if exist.
	 *
	 * @param   string  $jtext  Jtext constant for region .
	 *
	 * @since   1.1
	 * @return   Region name;
	 */
	public function getRegionText($jtext)
	{
		if ($this->_tjlang->hasKey(strtoupper($jtext)))
		{
			return Text::_($jtext, true);
		}
		elseif ($jtext !== '')
		{
			return null;
		}
	}

	/**
	 * Returns the countryID from country code (2 digit country code like IN for india )
	 *
	 * @param   string  $countryCode  2 digit country code like IN for india
	 *
	 * @return  object  country object which includes id, country name accourding to curren language && country_jtext, country_jtext;
	 *
	 * @since   1.1
	 */
	public function getCountryFromTwoDigitCountryCode($countryCode)
	{
		if (empty($countryCode))
		{
			return false;
		}

		$countryCode = strtoupper($countryCode);

		try
		{
			$query = $this->_db->getQuery(true);
			$query->select('id,country,country_jtext');
			$query->from('#__tj_country');
			$query->where("country_code = '" . $countryCode . "'");
			$this->_db->setQuery($query);
			$country = $this->_db->loadObject();
		}
		catch (Exception $e)
		{
			echo $e->getMessage();

			return false;
		}

		if ($country)
		{
			$countryName = "";

			if (!empty($country->country_jtext))
			{
				$countryName = $this->getCountryText($country->country_jtext);
			}
			else
			{
				$countryName = $country->country;
			}

			$country->country = $countryName;

			return $country;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Returns the Region from region name
	 *
	 * @param   integer  $countryId   2 digit country code like IN for india
	 * @param   string   $regionName  State/region name
	 *
	 * @return  object  country object which includes id, country name accourding to curren language && country_jtext, country_jtext;
	 *
	 * @since   1.1
	 */
	public function getRegionFromRegionName($countryId, $regionName)
	{
		if (empty($countryId) || empty($regionName))
		{
			return false;
		}

		$countryId = strtoupper($countryId);

		try
		{
			$query = $this->_db->getQuery(true);
			$query->select('id,region,region_jtext');
			$query->from('#__tj_region');
			$query->where("country_id = '" . $countryId . "'");
			$query->where("LOWER(region) = '" . strtolower($regionName) . "'");
			$this->_db->setQuery($query);
			$region = $this->_db->loadObject();
		}
		catch (Exception $e)
		{
			echo $e->getMessage();

			return false;
		}

		if ($region)
		{
			$regionName = "";

			if (!empty($region->region_jtext))
			{
				$regionName = $this->getRegionText($region->region_jtext);
			}
			else
			{
				$regionName = $region->region;
			}

			$region->region = $regionName;

			return $region;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Gives city list according.( field city gives you city name in current language) .
	 *
	 * @param   string  $countryId     id of country
	 * @param   string  $component_nm  name of component
	 * @param   string  $orderingCol   order by table column eg region
	 *
	 * @return  citylist
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function getCityList($countryId, $component_nm = "", $orderingCol = "city")
	{
		$this->_db = Factory::getDbo();
		$query     = $this->_db->getQuery(true);
		$query->select($this->_db->qn(array('id', 'city', 'city_jtext')));
		$query->from($this->_db->qn('#__tj_city'));
		$query->where($this->_db->qn('#__tj_city.country_id') . ' = ' . (int) $countryId);
		$query->order($this->_db->qn('#__tj_city.' . $orderingCol) . ' ASC');

		if ($component_nm)
		{
			$query->where($this->_db->qn('#__tj_city.' . $component_nm) . ' = 1');
		}

		$this->_db->setQuery($query);
		$cityList = $this->_db->loadAssocList();

		foreach ($cityList as $key => $city)
		{
			if ($city['city_jtext'])
			{
				$jtext = $this->getCityText($city['city_jtext']);

				if ($jtext)
				{
					$cityList[$key]['city'] = $jtext;
				}
			}
		}

		return $cityList;
	}

	/**
	 * Method gives city name in current  language if exist.
	 *
	 * @param   string  $jtext  Jtext constant for city .
	 *
	 * @since   __DEPLOY_VERSION__
	 *
	 * @return   city name;
	 */
	public function getCityText($jtext)
	{
		if ($this->_tjlang->hasKey(strtoupper($jtext)))
		{
			return Text::_($jtext, true);
		}
		elseif ($jtext !== '')
		{
			return null;
		}
	}
}
