<?php
/**
 * @version    SVN:<SVN_ID>
 * @package    TJFields
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2019 TechJoomla. All rights reserved
 * @license    GNU General Public License version 2, or later
 */

defined('JPATH_PLATFORM') or die;

use Joomla\CMS\Form\FormHelper;

JFormHelper::loadFieldClass('textarea');

/**
 * Form Field Textareacounter class
 * Supports a multi line area for entry of plain text with count char
 *
 * @since  11.1
 */
class JFormFieldFileUploadPath extends JFormFieldTextarea
{
	/**
	 * The form field type.
	 *
	 * @var    string
	 * @since  11.1
	 */
	protected $type = 'Fileuploadpath';

	/**
	 * Method to get the textarea field input markup.
	 * Use the rows and columns attributes to specify the dimensions of the area.
	 *
	 * @return  string  The field input markup.
	 *
	 * @since   11.1
	 */
	protected function getInput()
	{
		$layoutData = $this->getLayoutData();
		$client = $layoutData['field']->form->getData()->get('client');
		$client = str_replace('.', '_', $client);

		if (empty($this->value))
		{
			$this->value = JPATH_SITE . '/media/' . $client . '/';
			$this->value = str_replace('/', DIRECTORY_SEPARATOR, $this->value);
		}

		$html = parent::getInput();

		return $html;
	}
}
