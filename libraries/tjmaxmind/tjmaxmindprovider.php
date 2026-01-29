<?php
/**
 * @package     TJMaxmind
 * @subpackage  TJMaxmindProvider
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (c) 2009-2019 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later.
 */

/**
 * @package		akgeoip
 * @copyright	Copyright (c)2013-2017 Nicholas K. Dionysopoulos
 * @license		GNU General Public License version 3, or later
 */

/**
 * This library contains code from the following projects:
 * -- Composer, (c) Nils Adermann <naderman@naderman.de>, Jordi Boggiano <j.boggiano@seld.be>
 * -- GeoIPv2, (c) MaxMind www.maxmind.com
 * -- Guzzle, (c) 2011 Michael Dowling, https://github.com/mtdowling <mtdowling@gmail.com>
 * -- MaxMind DB Reader PHP API, (c) MaxMind www.maxmind.com
 * -- Symfiny, (c) 2004-2013 Fabien Potencier
 *
 * Third party software is distributed as-is, each one having its own copyright and license.
 * For more information please see the respective license and readme files, found under
 * the maxmind/vendor directory of this library.
 * This product includes GeoLite2 data created by MaxMind, available from
 * <a href="https://www.maxmind.com">https://www.maxmind.com</a>.
 */

defined('_JEXEC') or die();

use Joomla\CMS\Factory;
use GeoIp2\Database\Reader;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Http\HttpFactory;
use Joomla\CMS\Filesystem\Folder;

/**
 * TJMaxmindProvider Class.
 *
 * @since  1.0.0
 */
class TJMaxmindProvider
{
	/**
	 * The MaxMind GeoLite database reader
	 *
	 * @var    Reader
	 *
	 * @since  2.0
	 */
	private $reader = null;

	/**
	 * Records for IP addresses already looked up
	 *
	 * @var    array
	 *
	 * @since  2.0
	 */
	private $lookups = array();

	/**
	 * City records for IP addresses already looked up
	 *
	 * @var    array
	 *
	 * @since  2.0
	 */
	private $cityLookups = array();

	/**
	 * Do I have a database with city-level information?
	 *
	 * @var    bool
	 *
	 * @since  2.0
	 */
	private $hasCity = true;

	/**
	 * Public constructor. Loads up the GeoLite2 database.
	 */
	public function __construct()
	{
		if (!function_exists('bcadd') || !function_exists('bcmul') || !function_exists('bcpow'))
		{
			require_once __DIR__ . '/maxmind/fakebcmath.php';
		}

		// If I have a city-level database prefer it
		$filePath = JPATH_PLUGINS . '/system/tjmaxmind/db/GeoLite2-City.mmdb';

		try
		{
			$this->reader = new Reader($filePath);
		}
		// If anything goes wrong, MaxMind will raise an exception, resulting in a WSOD. Let's be sure to catch everything
		catch (\Exception $e)
		{
			$this->reader = null;
		}
	}

	/**
	 * Gets a raw country record from an IP address
	 *
	 * @param   string  $ip  The IP address to look up
	 *
	 * @return  mixed  A \GeoIp2\Model\Country record if found, false if the IP address is not found, null if the db can't be loaded
	 */
	public function getCountryRecord($ip)
	{
		if ($this->hasCity)
		{
			return $this->getCityRecord($ip);
		}

		if (!array_key_exists($ip, $this->lookups))
		{
			try
			{
				$this->lookups[$ip] = null;

				if (!is_null($this->reader))
				{
					$this->lookups[$ip] = $this->reader->country($ip);
				}
			}
			catch (\GeoIp2\Exception\AddressNotFoundException $e)
			{
				$this->lookups[$ip] = false;
			}
			catch (\Exception $e)
			{
				// GeoIp2 could throw several different types of exceptions. Let's be sure that we're going to catch them all
				$this->lookups[$ip] = null;
			}
		}

		return $this->lookups[$ip];
	}

	/**
	 * Gets the ISO country code from an IP address
	 *
	 * @param   string  $ip  The IP address to look up
	 *
	 * @return  mixed  A string with the country ISO code if found, false if the IP address is not found, null if the db can't be loaded
	 */
	public function getCountryCode($ip)
	{
		$record = $this->getCountryRecord($ip);

		if ($record === false)
		{
			return false;
		}

		if (is_null($record))
		{
			return false;
		}

		return $record->country->isoCode;
	}

	/**
	 * Gets the country name from an IP address
	 *
	 * @param   string  $ip      The IP address to look up
	 * @param   string  $locale  The locale of the country name, e.g 'de' to
	 * return the country names in German. If not specified the English (US)
	 * names are returned.
	 *
	 * @return  mixed  A string with the country name if found, false if the IP
	 *  address is not found, null if the db can't be loaded
	 */
	public function getCountryName($ip, $locale = null)
	{
		$record = $this->getCountryRecord($ip);

		if ($record === false)
		{
			return false;
		}

		if (is_null($record))
		{
			return false;
		}

		if (empty($locale))
		{
			return $record->country->name;
		}

		return $record->country->names[$locale];
	}

	/**
	 * Gets the continent ISO code from an IP address
	 *
	 * @param   string  $ip      The IP address to look up
	 * @param   string  $locale  The locale of the country name, e.g 'de' to
	 * return the country names in German. If not specified the English (US)
	 * names are returned.
	 *
	 * @return  mixed  A string with the country name if found, false if the IP address is not found, null if the db can't be loaded
	 */
	public function getContinent($ip, $locale = null)
	{
		$record = $this->getCountryRecord($ip);

		if ($record === false)
		{
			return false;
		}

		if (is_null($record))
		{
			return false;
		}

		return $record->continent->code;
	}

	/**
	 * Gets the continent name from an IP address
	 *
	 * @param   string  $ip      The IP address to look up
	 * @param   string  $locale  The locale of the country name, e.g 'de' to
	 * return the country names in German. If not specified the English (US)
	 * names are returned.
	 *
	 * @return  mixed  A string with the country name if found, false if the IP address is not found, null if the db can't be loaded
	 */
	public function getContinentName($ip, $locale = null)
	{
		$record = $this->getCountryRecord($ip);

		if ($record === false)
		{
			return false;
		}

		if (is_null($record))
		{
			return false;
		}

		if (empty($locale))
		{
			return $record->continent;
		}

		return $record->continent->names[$locale];
	}

	/**
	 * Gets a raw city record from an IP address
	 *
	 * @param   string  $ip  The IP address to look up
	 *
	 * @return  mixed  A \GeoIp2\Model\City record if found, false if the IP address is not found, null if the db can't be loaded
	 */
	public function getCityRecord($ip)
	{
		if (!$this->hasCity)
		{
			return null;
		}

		$needsToLoad = !array_key_exists($ip, $this->cityLookups);

		if ($needsToLoad)
		{
			try
			{
				if (!is_null($this->reader))
				{
					$this->cityLookups[$ip] = $this->reader->city($ip);
				}
				else
				{
					$this->cityLookups[$ip] = null;
				}
			}
			catch (\GeoIp2\Exception\AddressNotFoundException $e)
			{
				$this->cityLookups[$ip] = false;
			}
			catch (\Exception $e)
			{
				// GeoIp2 could throw several different types of exceptions. Let's be sure that we're going to catch them all
				$this->cityLookups[$ip] = null;
			}
		}

		return $this->cityLookups[$ip];
	}

	/**
	 * Gets the continent ISO code from an IP address
	 *
	 * @param   string  $ip      The IP address to look up
	 * @param   string  $locale  The locale of the country name, e.g 'de' to
	 * return the country names in German. If not specified the English (US)
	 * names are returned.
	 *
	 * @return  mixed  A string with the country name if found, false if the IP address is not found, null if the db can't be loaded
	 */
	public function getCity($ip, $locale = null)
	{
		$record = $this->getCityRecord($ip);

		if ($record === false)
		{
			return false;
		}

		if (is_null($record))
		{
			return false;
		}

		return $record->city->name;
	}

	/**
	 * Downloads and installs a fresh copy of the GeoLite2 Country database
	 *
	 * @param   bool  $forceCity  Should I force the download of the city-level library?
	 *
	 * @return  mixed  True on success, error string on failure
	 */
	public function updateDatabase($forceCity = false)
	{
		$outputFile = $deleteFile = '';

		if ($this->hasCity || $forceCity)
		{
			$outputFile = $deleteFile = JPATH_PLUGINS . '/system/tjmaxmind/db/GeoLite2-City.mmdb';
		}

		// Sanity check
		if (!function_exists('gzinflate'))
		{
			return Text::_('LIB_TJMAXMIND_ERR_NOGZSUPPORT');
		}

		// Try to download the package, if I get any exception I'll simply stop here and display the error
		try
		{
			$compressed = $this->downloadDatabase($forceCity);
		}
		catch (\Exception $e)
		{
			return $e->getMessage();
		}

		// Write the downloaded file to a temporary location
		$tmpdir = $this->getTempFolder();
		$target = $tmpdir . '/GeoLite2-City.mmdb.gz';
		$ret = File::write($target, $compressed);

		if ($ret === false)
		{
			return Text::_('LIB_TJMAXMIND_ERR_WRITEFAILED');
		}

		unset($compressed);

		// Decompress the file
		$uncompressed = '';

		$zp = gzopen($target, 'rb');

		if ($zp === false)
		{
			return Text::_('LIB_TJMAXMIND_ERR_CANTUNCOMPRESS');
		}

		if ($zp !== false)
		{
			while (!gzeof($zp))
			{
				$uncompressed .= gzread($zp, 102400);
			}

			gzclose($zp);

			if (!unlink($target))
			{
				File::delete($target);
			}
		}

		// Double check if MaxMind can actually read and validate the downloaded database
		try
		{
			// The Reader want a file, so let me write again the file in the temp directory
			File::write($target, $uncompressed);
			$reader = new Reader($target);
		}
		catch (\Exception $e)
		{
			File::delete($target);

			// MaxMind could not validate the database, let's inform the user
			return Text::_('LIB_TJMAXMIND_ERR_INVALIDDB');
		}

		File::delete($target);

		// Check the size of the uncompressed data. When MaxMind goes into overload, we get crap data in return.
		if (strlen($uncompressed) < 1048576)
		{
			return Text::_('LIB_TJMAXMIND_ERR_MAXMINDRATELIMIT');
		}

		// Check the contents of the uncompressed data. When MaxMind goes into overload, we get crap data in return.
		if (stristr($uncompressed, 'Rate limited exceeded') !== false)
		{
			return Text::_('LIB_TJMAXMIND_ERR_MAXMINDRATELIMIT');
		}

		// Remove old file
		JLoader::import('joomla.filesystem.file');

		if (File::exists($outputFile))
		{
			if (!File::delete($outputFile))
			{
				return Text::_('LIB_TJMAXMIND_ERR_CANTDELETEOLD');
			}
		}

		// Write the update file
		if (!File::write($outputFile, $uncompressed))
		{
			return Text::_('LIB_TJMAXMIND_ERR_CANTWRITE');
		}

		return true;
	}

	/**
	 * Download the compressed database for the provider
	 *
	 * @param   bool  $forceCity  Should I force the download of the city-level database
	 *
	 * @return  string  The compressed data
	 *
	 * @since   1.0.1
	 *
	 * @throws  Exception
	 */
	private function downloadDatabase($forceCity = false)
	{
		// Download the latest MaxMind GeoCountry Lite database
		$url = '';

		if ($this->hasCity || $forceCity)
		{
			$url = 'http://geolite.maxmind.com/download/geoip/database/GeoLite2-City.mmdb.gz';
		}

		// I should have F0F installed, but let's double check in order to avoid errors
		if (file_exists(JPATH_LIBRARIES . '/f0f/include.php'))
		{
			require_once JPATH_LIBRARIES . '/f0f/include.php';
		}

		$http = HttpFactory::getHttp();

		// Let's bubble up the exception, we will take care in the caller
		$response   = $http->get($url);
		$compressed = $response->body;

		// Generic check on valid HTTP code
		if ($response->code > 299)
		{
			throw new \Exception(Text::_('LIB_TJMAXMIND_ERR_MAXMIND_GENERIC'));
		}

		// An empty file indicates a problem with MaxMind's servers
		if (empty($compressed))
		{
			throw new \Exception(Text::_('LIB_TJMAXMIND_ERR_EMPTYDOWNLOAD'));
		}

		// Sometimes you get a rate limit exceeded
		if (stristr($compressed, 'Rate limited exceeded') !== false)
		{
			throw new \Exception(Text::_('LIB_TJMAXMIND_ERR_MAXMINDRATELIMIT'));
		}

		return $compressed;
	}

	/**
	 * Reads (and checks) the temp Joomla folder
	 *
	 * @return string
	 */
	private function getTempFolder()
	{
		$jRegistry = Factory::getConfig();
		$tmpdir    = $jRegistry->get('tmp_path');

		JLoader::import('joomla.filesystem.folder');

		// Make sure the user doesn't use the system-wide tmp directory. You know, the one that's
		// being erased periodically and will cause a real mess while installing extensions (Grrr!)
		if (realpath($tmpdir) == '/tmp')
		{
			// Someone inform the user that what he's doing is insecure and stupid, please. In the
			// meantime, I will fix what is broken.
			$tmpdir = JPATH_SITE . '/tmp';
		}
		// Make sure that folder exists (users do stupid things too often; you'd be surprised)
		elseif (!Folder::exists($tmpdir))
		{
			// Darn it, user! WTF where you thinking? OK, let's use a directory I know it's there...
			$tmpdir = JPATH_SITE . '/tmp';
		}

		return $tmpdir;
	}
}
