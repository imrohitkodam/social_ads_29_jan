<?php
/**
 * @package    Tjfields
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2021 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

JLoader::import("/techjoomla/media/storage/local", JPATH_LIBRARIES);

defined('JPATH_PLATFORM') or die;

use Joomla\CMS\Form\FormField;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Factory;

JLoader::import('components.com_tjfields.models.fields.file', JPATH_ADMINISTRATOR);

/**
 * Form Field class for the image capture field.
 * Provides an input field for files
 *
 * @link   http://www.w3.org/TR/html-markup/input.file.html#input.file
 * @since  __DEPLOY_VERSION__
 */
class JFormFieldCaptureImage extends JFormFieldFile
{
	/**
	 * The form field type.
	 *
	 * @var    string
	 * @since  __DEPLOY_VERSION__
	 */
	protected $type = 'Captureimage';

	/**
	 * The accepted Captureimage type list.
	 *
	 * @var    mixed
	 * @since  __DEPLOY_VERSION__
	 */
	protected $accept;

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
		}

		return parent::__get($name);
	}

	/**
	 * Method to set certain otherwise inaccessible properties of the form field object.
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
		}

		return $return;
	}

	/**
	 * Method to get the field input markup for the file field.
	 * Field attributes allow specification of a maximum file size and a string
	 * of accepted file extensions.
	 *
	 * @return  string  The field input markup.
	 *
	 * @note    The field does not include an upload mechanism.
	 * @see     JFormFieldMedia
	 * @since   __DEPLOY_VERSION__
	 */
	protected function getInput()
	{
		HTMLHelper::script('media/com_tjfields/vendors/webcamjs/webcam.min.js');
		HTMLHelper::script('administrator/components/com_tjfields/assets/js/captureimage.min.js');

		$layoutData = $this->getLayoutData();

		$height = isset($layoutData['field']->element->attributes()->height) ? $layoutData['field']->element->attributes()->height : 240;
		$width = isset($layoutData['field']->element->attributes()->width) ? $layoutData['field']->element->attributes()->width : 320;

		$required = ($this->required) ? "required='required'" : "";
		$hasValue = (!empty($this->value)) ? '1' : '0';

		$html = "<input type='hidden' $required name='" . $this->name . "' id='" . $this->id . "' value='" . $this->value . "'>";
		$html .= "<input type='hidden' id='" . $this->id . "__hasvalue' value='" . $hasValue . "'>";
		$html .= "<input type='hidden' id='" . $this->id . "__camera_height' value='" . $height . "'>";
		$html .= "<input type='hidden' id='" . $this->id . "__camera_width' value='" . $width . "'>";

		// Load backend language file
		$lang = Factory::getLanguage();
		$lang->load('com_tjfields', JPATH_SITE);

		$displayTakePictureButton = (!empty($this->value)) ? 'style="display:none;"' : '';
		$displayTakeAnotherButton = (!empty($this->value)) ? '' : 'style="display:none;"';
		$displayCapturedImgDiv = (!empty($this->value)) ? 'style="display:none;"' : '';
		$displayUsePictureButton = (!empty($this->value)) ? 'style="display:none;"' : '';
		$displayCameraSwitchButton = (!empty($this->value)) ? 'style="display:none;"' : '';

		$html .= '<div id="' . $this->id . '_capture_img' . '"></div>';
		$html .= '<div  ' . $displayCapturedImgDiv . ' id="' . $this->id . '_captured_img' . '"><img /></div>';
		$html .= '<br>';
		$html .= '<input ' . $displayTakePictureButton . ' type="button" id="' . $this->id . '_take_snapshot' . '" value="' . Text::_("COM_TJFIELDS_FILE_CAPTURE_IMAGE_TAKE_PHOTO") . '" onClick="' . "take_snapshot('" . $this->id . "')" . '">';
		$html .= '<input ' . $displayUsePictureButton . ' type="button" id="' . $this->id . '_use' . '" value="' . Text::_("COM_TJFIELDS_FILE_USE_CAPTURED_IMAGE") . '" onClick="' . "use_snapshot('" . $this->id . "')" . '">';
		$html .= '<input ' . $displayCameraSwitchButton . ' type="button" id="' . $this->id . '_switch_camera' . '" value="' . Text::_("COM_TJFIELDS_FILE_SWITCH_CAMERA") . '" onClick="' . "switch_camera('" . $this->id . "')" . '">';
		$html .= '<input ' . $displayTakeAnotherButton . ' type="button" id="' . $this->id . '_take_another' . '" value="' . Text::_("COM_TJFIELDS_FILE_CAPTURE_IMAGE_TAKE_ANOTHER") . '" onClick="' . "set_camera('" . $this->id . "')" . '">';
		$html .= '<br><br>';

		if (!empty($layoutData["value"]))
		{
			$data = $this->buildData($layoutData);
			$html .= '<div class="control-group">';
			$html .= $data->html;

			if (!empty($data->mediaLink))
			{
				$html .= $this->canDownloadFile($data, $layoutData);
				$html .= $this->canDeleteFile($data, $layoutData);
			}

			$html .= '</div>';
		}

		return $html;
	}
}
