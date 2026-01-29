<?php
/**
 * @package     Techjoomla.Libraries
 * @subpackage  Tjtoolbar
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Layout\FileLayout;
use Joomla\CMS\Toolbar\ToolbarButton;
use Joomla\CMS\HTML\HTMLHelper;

/**
 * CsvExportButton
 *
 * @package     Techjoomla.Libraries
 * @subpackage  TjCsv
 * @since       1.0
 */
class JToolbarButtonCsvExport extends ToolbarButton
{
	/**
	 * Fetch the HTML for the button
	 *
	 * @param   string  $buttontext  Text to be used to show
	 * @param   Array   $messages    Array of Succes, exportError, inprogress messages
	 *
	 * @return  string  HTML string for the button
	 *
	 * @since   3.0
	 */
	public function fetchButton($buttontext = '', $messages = null)
	{
		HTMLHelper::script('libraries/techjoomla/assets/js/tjexport.js');
		Factory::getLanguage()->load('lib_techjoomla', JPATH_SITE, null, false, true);
		Text::script('LIB_TECHJOOMLA_CSV_EXPORT_ABORT');
		Text::script('LIB_TECHJOOMLA_CSV_EXPORT_UESR_ABORTED');
		Text::script('LIB_TECHJOOMLA_CSV_EXPORT_CONFIRM_ABORT');

		$input = Factory::getApplication()->input;
		$csv_url = 'index.php?option=' . $input->get('option') . '&view=' . $input->get('view') . '&format=csv';
		$siteUrl = Uri::base();
		$document = Factory::getDocument();
		$document->addScriptDeclaration("var csv_export_url='{$csv_url}';");
		$document->addScriptDeclaration("var tj_csv_site_root='{$siteUrl}';");
		$document->addScriptDeclaration("var csv_export_success='{$messages['success']}';");
		$document->addScriptDeclaration("var csv_export_error='{$messages['error']}';");
		$document->addScriptDeclaration("var csv_export_inprogress='{$messages['inprogress']}';");

		// Store all data to the options array for use with JLayout
		$options = array();
		$options['text'] = isset($messages['btn-name']) ? $messages['btn-name'] : Text::_($buttontext);

		if (isset($messages['text']) && $messages['text'])
		{
			$options['text'] = $messages['text'];
		}

		$options['btnClass'] = 'btn btn-small export btn-secondary';
		$options['doTask'] = "tjexport.exportCsv(0);";

		if (JVERSION >= '4.0.0')
		{
			$options['onclick'] = "tjexport.exportCsv(0);";
			$options['htmlAttributes'] = '';
		}

		$options['class'] = 'icon-download';

		// Instantiate a new JLayoutFile instance and render the layout
		$layout = new FileLayout('joomla.toolbar.standard');

		return $layout->render($options);
	}

	/**
	 * Get the button CSS Id
	 *
	 * @param   string   $type      Unused string.
	 * @param   string   $name      Name to be used as apart of the id
	 * @param   string   $text      Button text
	 * @param   string   $task      The task associated with the button
	 * @param   boolean  $list      True to allow use of lists
	 * @param   boolean  $hideMenu  True to hide the menu on click
	 *
	 * @return  string  Button CSS Id
	 *
	 * @since   3.0
	 */
	public function fetchId($type = 'Standard', $name = '', $text = '', $task = '', $list = true, $hideMenu = false)
	{
	}
}
