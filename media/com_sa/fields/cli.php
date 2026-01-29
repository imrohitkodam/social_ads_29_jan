<?php
/**
 * @version    SVN: <svn_id>
 * @package    SocialAd
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2019 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

// No direct access
defined('_JEXEC') or die();
use Joomla\CMS\Form\FormField;

use Joomla\CMS\Language\Text;

/**
 * Class for custom cron element
 *
 * @since  __DEPLOY_VERSION_
 */
class JFormFieldCli extends JFormField
{
	protected $type = 'cli';

	/**
	 * Function to genarate html of custom element
	 *
	 * @return  string
	 *
	 * @since  __DEPLOY_VERSION_
	 */
	public function getInput()
	{
		if (version_compare(JVERSION, '4.0', 'le'))
		{
			$return = '';
			$path   = JPATH_SITE . "/cli/" . $this->hint;

			if (file_exists($path))
			{
				$return = '<input type="text" class="input form-control input-xxlarge" onclick="this.select();" value="php ' . $path . '" aria-invalid="false">';
			}
			else
			{
				$return = '<label>' . Text::_('COM_SOCIALADS_FORM_ERR_NO_CLI_COMMAND') . '</label>';
			}
		}
		else 
		{
			$return = '';
			$path   = JPATH_SITE . "/cli/joomla.php";
			$type     = $this->hint == 'archivestats.php' ? 'archivestats' : 'statsemail';
			$command   = JPATH_SITE . "/cli/joomla.php " . $type;

			if (file_exists($path))
			{
				$return = '<input type="text" class="input form-control input-xxlarge" onclick="this.select();" value="php ' . $command . '" aria-invalid="false">';
			}
			else 
			{
				$return = '<label>' . Text::_('COM_SOCIALADS_FORM_ERR_NO_CLI_COMMAND') . '</label>';
			}
		}

		return $return;
	}
}
