<?php
/**
 * @package     TJMaxmind
 * @subpackage  TJMaxmind
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (c) 2009-2019 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later.
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;

require_once 'tjmaxmindprovider.php';
require_once 'maxmind/vendor/autoload.php';

/**
 * TjMaxmind class
 *
 * @since  1.0
 */
class TJMaxmind
{
	/**
	 * Returns user's machine IP address
	 *
	 * @return  string
	 *
	 * @since  1.0
	 **/
	public function getUserIP()
	{
		$jinput = Factory::getApplication()->input;

		if (!empty($jinput->server->get('REMOTE_ADDR')))
		{
			$httpForwarded = $jinput->server->get('HTTP_X_FORWARDED_FOR', '', '');

			if (!empty($httpForwarded))
			{
				$ip = $httpForwarded;

				if ($ip != '' && strtolower($ip) != 'unknown')
				{
					$addresses = explode(',', $ip);

					return trim($addresses[ count($addresses) - 1 ]);
				}
			}

			$clientIp = trim($jinput->server->get('HTTP_CLIENT_IP', '', ''));

			if (!empty($clientIp))
			{
				return $clientIp;
			}

			return trim($jinput->server->get('REMOTE_ADDR', '', ''));
		}

		if ($ip = getenv('HTTP_X_FORWARDED_FOR'))
		{
			if (strtolower($ip) != 'unknown')
			{
				$addresses = explode(',', $ip);

				return trim($addresses[count($addresses) - 1]);
			}
		}

		if ($ip = getenv('HTTP_CLIENT_IP'))
		{
			return trim($ip);
		}

		return trim(getenv('REMOTE_ADDR'));
	}

	/**
	 * Returns user's geo location from his/her IP address using Maxmind Legacy database
	 *
	 * @param   string  $ip  IP address
	 *
	 * @return  array
	 *
	 * @since  1.0
	 **/
	public function getUserLocationFromIP($ip)
	{
		$formatted_data = array(
			'country' => '',
			'region' => '',
			'city' => ''
		);

		$dbfile = JPATH_PLUGINS . '/system/tjmaxmind/db/GeoLite2-City.mmdb';

		if (!file_exists($dbfile))
		{
			return $formatted_data;
		}

		// Trim whitespace
		$ip = trim($ip);

		try
		{
			$provider = new TjMaxmindProvider;
			$record = $provider->getCityRecord($ip);

			if ($record)
			{
				$formatted_data = array(
					'country' => $record->country->name,
					'region' => $record->mostSpecificSubdivision->name,
					'city' => $record->city->name
				);
			}
		}
		catch (\Exception $e)
		{
			// Silence is peace
		}

		return $formatted_data;
	}
}
