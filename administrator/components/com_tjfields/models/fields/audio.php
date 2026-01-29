<?php
/**
 * @version    SVN:<SVN_ID>
 * @package    TJFields
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2019 TechJoomla. All rights reserved
 * @license    GNU General Public License version 2, or later
 */

defined('JPATH_PLATFORM') or die;

use Joomla\CMS\Form\FormField;

JLoader::import('components.com_tjfields.models.fields.video', JPATH_ADMINISTRATOR);

/**
 * Form Field Audio class
 * Supports a multi line area for entry of plain text with count char
 *
 * @since  DEPLOY_VERSION
 */
class JFormFieldAudio extends JFormFieldVideo
{
	/**
	 * The form field type.
	 *
	 * @var    string
	 * @since  DEPLOY_VERSION
	 */
	protected $type = 'Audio';

	/**
	 * The SimpleXMLElement object representing the `<field>` tag for the form field object.
	 *
	 * @var    mixed
	 * @since  DEPLOY_VERSION
	 */
	protected $element;

	/**
	 * Name of the layout being used to render the field
	 *
	 * @var    string
	 * @since  DEPLOY_VERSION
	 */
	protected $layout = 'joomla.form.field.url';

	/**
	 * Method to get certain otherwise inaccessible properties from the form field object.
	 *
	 * @param   string  $name  The property name for which to the the value.
	 *
	 * @return  mixed  The property value or null.
	 *
	 * @since   DEPLOY_VERSION
	 */
	public function __get($name)
	{
		require_once JPATH_SITE . '/components/com_tjfields/helpers/tjfields.php';

		switch ($name)
		{
			case 'element';

				return $this->element;
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
	 * @since   DEPLOY_VERSION
	 */
	public function __set($name, $value)
	{
		switch ($name)
		{
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
	 * @since   DEPLOY_VERSION
	 */
	public function setup(SimpleXMLElement $element, $value, $group = null)
	{
		$return = parent::setup($element, $value, $group);

		return $return;
	}

	/**
	 * Method to get the field input markup.
	 *
	 * @return  string  The field input markup.
	 *
	 * @since   DEPLOY_VERSION
	 */
	protected function getInput()
	{
		require_once JPATH_SITE . '/components/com_tjfields/helpers/tjfields.php';

		$layoutData = $this->getLayoutData();

		// Trim the trailing line in the layout file
		$html = rtrim($this->getRenderer($this->layout)->render($layoutData), PHP_EOL);

		if (empty($layoutData['value']))
		{
			return $html;
		}

		$html .= $this->renderAudio($layoutData, $layoutData['value']);

		if (!empty($layoutData['value']) && $layoutData['field']->element->attributes()->showvideolink)
		{
			$html .= '<br><a target="_blank" href=' . $layoutData['value'] . '> ' . $layoutData['value'] . '</a>';
		}

		$html .= parent::addMediaplayer($layoutData);

		return $html;
	}

	/**
	 * Method to get the data to be passed to the layout for rendering.
	 *
	 * @return  array
	 *
	 * @since   DEPLOY_VERSION
	 */
	protected function getLayoutData()
	{
		$data = parent::getLayoutData();

		// Initialize some field attributes.
		$maxLength    = !empty($this->maxLength) ? ' maxlength="' . $this->maxLength . '"' : '';

		// Note that the input type "url" is suitable only for external URLs, so if internal URLs are allowed
		// we have to use the input type "text" instead.
		$inputType    = $this->element['relative'] ? 'type="text"' : 'type="url"';

		$extraData = array(
			'maxLength' => $maxLength,
			'inputType' => $inputType,
		);

		return array_merge($data, $extraData);
	}

	/**
	 * Method to render audio file.
	 *
	 * @param   array   $layoutData  layoutData.
	 * @param   string  $audioUrl    audioUrl
	 *
	 * @return  string
	 *
	 * @since   DEPLOY_VERSION
	 */
	protected function renderAudio($layoutData, $audioUrl)
	{
		$html = '';
		$autoPlay= '';
		$muted = '';

		if (isset($layoutData['field']->element->attributes()->autoplay))
		{
			$autoPlay = 'autoplay';
		}

		if (isset($layoutData['field']->element->attributes()->muted))
		{
			$muted = 'muted';
		}

		$html .= '
				<audio ' . $autoPlay . ' id="player_' . $layoutData['field']->id . '"
					height="' . $layoutData['field']->element->attributes()->height . '"
					width="' . $layoutData['field']->element->attributes()->width . '
					preload="auto"
					poster ="' . $layoutData['field']->element->attributes()->poster . '"
					controls playsinline webkit-playsinline
					src="' . $audioUrl . '" ' . $muted . '>
				</audio>';

		return $html;
	}
}
