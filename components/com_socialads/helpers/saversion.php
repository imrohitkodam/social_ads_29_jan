<?php
/**
 * @version    SVN:<SVN_ID>
 * @package    SocialAds
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2019 TechJoomla. All rights reserved
 * @license    GNU General Public License version 2, or later
 */

use Joomla\CMS\Factory;

defined('_JEXEC') or die();

/**
 * Version information class for the SocialAds.
 *
 * @since  3.2.0
 */
class SaVersion
{
	/**
	 * Product name.
	 *
	 * @var    string
	 * @since  3.2.0
	 */
	const PRODUCT = 'SocialAds';

	/**
	 * Major release version.
	 *
	 * @var    integer
	 * @since  3.2.0
	 */
	const MAJOR_VERSION = 5;

	/**
	 * Minor release version.
	 *
	 * @var    integer
	 * @since  3.2.0
	 */
	const MINOR_VERSION = 0;

	/**
	 * Patch release version.
	 *
	 * @var    integer
	 * @since  3.2.0
	 */
	const PATCH_VERSION = 2;

	/**
	 * Release version.
	 *
	 * @var    string
	 * @since  3.2.0
	 */
	const RELEASE = '5.0';

	/**
	 * Maintenance version.
	 *
	 * @var    string
	 * @since  3.2.0
	 */
	const DEV_LEVEL = '2';

	/**
	 * Development status.
	 *
	 * @var    string
	 * @since  3.2.0
	 */
	const DEV_STATUS = 'Stable';

	/**
	 * Build number.
	 *
	 * @var    string
	 * @since  3.2.0
	 */
	const BUILD = '';

	/**
	 * Code name.
	 *
	 * @var    string
	 * @since  3.2.0
	 */
	const CODENAME = 'TechJoomla';

	/**
	 * Release date.
	 *
	 * @var    string
	 * @since  3.2.0
	 */
	const RELDATE = '17-March-2025';

	/**
	 * Release time.
	 *
	 * @var    string
	 * @since  3.2.0
	 */
	const RELTIME = '11:39';

	/**
	 * Release timezone.
	 *
	 * @var    string
	 * @since  3.2.0
	 */
	const RELTZ = 'GMT';

	/**
	 * Copyright Notice.
	 *
	 * @var    string
	 * @since  3.2.0
	 */
	const COPYRIGHT = 'Copyright (C) 2009 - 2025 Techjoomla. All rights reserved.';

	/**
	 * Link text.
	 *
	 * @var    string
	 * @since  3.2.0
	 */
	const URL = '<a href="https://www.techjoomla.com">TechJoomla!</a> is Joomla product dev.';

	/**
	 * Gets a "PHP standardized" version string for the current JGive.
	 *
	 * @return  string  Version string.
	 *
	 * @since   3.2.0
	 */
	public function getShortVersion()
	{
		return self::MAJOR_VERSION . '.' . self::MINOR_VERSION . '.' . self::PATCH_VERSION;
	}

	/**
	 * Gets a version string for the current JGive with all release information.
	 *
	 * @return  string  Complete version string.
	 *
	 * @since   3.2.0
	 */
	public function getLongVersion()
	{
		return self::PRODUCT . ' ' . $this->getShortVersion() . ' ' . self::RELDATE;
	}

	/**
	 * Generate a media version string for assets
	 * Public to allow third party developers to use it
	 *
	 * @return  string
	 *
	 * @since   3.2.0
	 */
	public function generateMediaVersion()
	{
		return md5($this->getLongVersion() . Factory::getConfig()->get('secret'));
	}

	/**
	 * Gets a media version which is used to append to JGive core media files.
	 *
	 * This media version is used to append to JGive core media in order to trick browsers into
	 * reloading the CSS and JavaScript, because they think the files are renewed.
	 * The media version is renewed after JGive core update, install, discover_install and uninstallation.
	 *
	 * @return  string  The media version.
	 *
	 * @since   3.2.0
	 */
	public function getMediaVersion()
	{
		return $this->generateMediaVersion();
	}
}
