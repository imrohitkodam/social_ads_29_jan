<?php
/**
 * @version    SVN:<SVN_ID>
 * @package    TJFields
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2018 TechJoomla. All rights reserved
 * @license    GNU General Public License version 2, or later
 */

defined('JPATH_PLATFORM') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;

JLoader::import('components.com_tjfields.models.fields.tjfile', JPATH_ADMINISTRATOR);

/**
 * Form Field Image class
 * Supports a multi line area for entry of plain text with count char
 *
 * @since  __DEPLOY_VERSION__
 */
class JFormFieldImage extends JFormFieldTjFile
{
	/**
	 * The form field type.
	 *
	 * @var    string
	 * @since  __DEPLOY_VERSION__
	 */
	protected $type = 'Image';

	/**
	 * The accepted file type list.
	 *
	 * @var    mixed
	 * @since  __DEPLOY_VERSION__
	 */
	protected $accept;

	/**
	 * The SimpleXMLElement object representing the `<field>` tag for the form field object.
	 *
	 * @var    mixed
	 * @since  __DEPLOY_VERSION__
	 */
	protected $element;

	/**
	 * Name of the layout being used to render the field
	 *
	 * @var    string
	 * @since  __DEPLOY_VERSION__
	 */
	protected $layout = 'joomla.form.field.file';

	/**
	 * Method to get certain otherwise inaccessible properties from the form field object.
	 *
	 * @param   string  $name  The property name for which to the the value.
	 *
	 * @return  mixed  The property value or null.
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function __get($name)
	{
		require_once JPATH_SITE . '/components/com_tjfields/helpers/tjfields.php';

		switch ($name)
		{
			case 'accept':

				return $this->accept;
				break;

			case 'element';

				return $this->element;
				break;
		}

		return parent::__get($name);
	}

	/**
	 *Method to set certain otherwise inaccessible properties of the form field object.
	 *
	 * @param   string  $name   The property name for which to the the value.
	 * @param   mixed   $value  The value of the property.
	 *
	 * @return  void
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function __set($name, $value)
	{
		switch ($name)
		{
			case 'accept':
				$this->accept = (string) $value;
				break;

			case 'multiple':
				$this->multiple = (string) $value;
				break;

			default:
				parent::__set($name, $value);
		}
	}

	/**
	 * Method to attach a JForm object to the field.
	 *
	 * @param   SimpleXMLElement  $element  The SimpleXMLElement object representing the `<field>` tag for the form field object.
	 * @param   mixed             $value    The form field value to validate.
	 * @param   string            $group    The field name group control value. This acts as as an array container for the field.
	 *                                      For example if the field has name="foo" and the group value is set to "bar" then the
	 *                                      full field name would end up being "bar[foo]".
	 *
	 * @return  boolean  True on success.
	 *
	 * @see     JFormField::setup()
	 * @since   __DEPLOY_VERSION__
	 */
	public function setup(SimpleXMLElement $element, $value, $group = null)
	{
		$return = parent::setup($element, $value, $group);

		if ($return)
		{
			$this->accept = (string) $this->element['accept'];
			$this->multiple = (string) $this->element['multiple'];
		}

		return $return;
	}

	/**
	 * Method to get the field input markup.
	 *
	 * @return  string  The field input markup.
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	protected function getInput()
	{
		$layoutData = $this->getLayoutData();
		$html = $this->getRenderer($this->layout)->render($layoutData);

		if ($this->size)
		{
			$sizes = array();
			$sizes[] = HTMLHelper::_('number.bytes', ini_get('post_max_size'), '');
			$sizes[] = HTMLHelper::_('number.bytes', ini_get('upload_max_filesize'), '');
			$sizes[] = $this->size * 1024 * 1024;

			$maxSize = HTMLHelper::_('number.bytes', min($sizes));
			$fileMaxSize = '<strong>' . $maxSize . '</strong>';
			$html = str_replace(substr($html, strpos($html, '<strong>'), strpos($html, '</strong>')), $fileMaxSize, $html);
		}

		// Load backend language file
		$lang = Factory::getLanguage();
		$lang->load('com_tjfields', JPATH_SITE);

		if (!empty($layoutData["value"]))
		{
			$data = parent::buildData($layoutData);

			if (!empty($data->mediaLink))
			{
				$html .= '<div class="control-group">';
				$html .= $data->html;
				$html .= $this->renderImage($data, $layoutData);
				$html .= $this->canDownloadFile($data, $layoutData);
				$html .= $this->canDeleteFile($data, $layoutData);
				$html .= '</div>';
			}
		}

		return $html;
	}

	/**
	 * Method to render image file.
	 *
	 * @param   object  $data        file data.
	 * @param   array   $layoutData  layoutData
	 *
	 * @return  string
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	protected function renderImage($data, $layoutData)
	{
		$path = Uri::root() . 'images/tjmedia/';

		if (!empty($data->tjFieldFieldTable))
		{
			$path .= str_replace(".", "/", $data->tjFieldFieldTable->get('client') . '/');
		}

		return '<img src="' . $path . $layoutData['value'] . '" height=
		"' . $layoutData['field']->element->attributes()->height . '"width="' . $layoutData['field']->element->attributes()->width . '" ></img><br>';
	}
}
