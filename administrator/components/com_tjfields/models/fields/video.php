<?php
/**
 * @version    SVN:<SVN_ID>
 * @package    TJFields
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2018 TechJoomla. All rights reserved
 * @license    GNU General Public License version 2, or later
 */

defined('JPATH_PLATFORM') or die;

use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;

JFormHelper::loadFieldClass('url');

/**
 * Form Field video class
 * Supports a multi line area for entry of plain text with count char
 *
 * @since  DEPLOY_VERSION`
 */
class JFormFieldVideo extends JFormFieldUrl
{
	/**
	 * The form field type.
	 *
	 * @var    string
	 * @since  DEPLOY_VERSION
	 */
	protected $type = 'Video';

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
		$layoutData = $this->getLayoutData();

		// Trim the trailing line in the layout file
		$html = rtrim($this->getRenderer($this->layout)->render($layoutData), PHP_EOL);

		if (empty($layoutData['value']))
		{
			return $html;
		}

		if (isset($layoutData['field']->element->attributes()->display_video))
		{
			$html .= '<br>
					<!-- Trigger the modal with a button -->
					<button type="button" class="btn btn-info" data-toggle="modal" data-target="#myModal_' . $layoutData['field']->id . '">'
					. Text::_("COM_TJFIELDS_FIELD_VIDEO_PLAY_BUTTON") . '</button>
					<div class="modal fade" id="myModal_' . $layoutData['field']->id . '" role="dialog">
						<div class="modal-dialog" style="width:'. ((int)($layoutData['field']->element->attributes()->width)+40) .'px;">
							<!-- Modal content-->
							<div class="modal-content">
								<div class="modal-header">
									<button type="button" class="close" data-dismiss="modal">&times;</button>
									<h4 class="modal-title">' . Text::_("COM_TJFIELDS_FIELD_VIDEO_TITLE") . '</h4>
								</div>
								<div class="modal-body">';
									$html .= $this->rendervideo($layoutData, $layoutData['value']);
									$html .= '
								</div>
								<div class="modal-footer">
									<button type="button" class="btn btn-default" data-dismiss="modal">' . Text::_("COM_TJFIELDS_FIELD_VIDEO_POP_UP_CLOSE") . '</button>
								</div>
							</div>
						</div>
					</div>';
		}
		else
		{
			$html .= $this->rendervideo($layoutData, $layoutData['value']);
		}

		if (!empty($layoutData['value']) && $layoutData['field']->element->attributes()->showvideolink)
		{
			$html .= '<br><a target="_blank" href=' . $layoutData['value'] . '> ' . $layoutData['value'] . '</a>';
		}

		$html .= $this->addMediaplayer($layoutData);

		return $html;
	}

	/**
	 * Method to get the data to be passed to the layout for rendering.
	 *
	 * @return  array
	 *
	 * @since DEPLOY_VERSION
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
	 * Method to render video.
	 *
	 * @param   array   $layoutData  layoutData.
	 * @param   string  $videoUrl    videoUrl
	 *
	 * @return  string
	 *
	 * @since   DEPLOY_VERSION
	 */
	protected function rendervideo($layoutData, $videoUrl)
	{
		$html = '';
		$autoPlay = '';

		if (isset($layoutData['field']->element->attributes()->autoplay))
		{
			$autoPlay = 'autoplay';
		}

		$html .= '<br>
				<video ' . $autoPlay . ' id="player_' . $layoutData['field']->id . '"
					height="' . $layoutData['field']->element->attributes()->height . '"
					width="' . $layoutData['field']->element->attributes()->width . '
					preload="auto"
					poster ="' . $layoutData['field']->element->attributes()->poster . '"
					controls playsinline webkit-playsinline
					src="' . $videoUrl . '">
				</video>';

		return $html;
	}

	/**
	 * Method to add media player to render video.
	 *
	 * @param   array  $layoutData  layoutData.
	 *
	 * @return  string
	 *
	 * @since   DEPLOY_VERSION
	 */
	protected function addMediaplayer($layoutData)
	{
		$html = '';

		$doc = Factory::getDocument();

		$doc->addScript(Uri::root() . 'media/com_tjfields/vendors/mediaelementplayer/mediaelement-and-player.min.js');
		$doc->addStyleSheet(Uri::root() . 'media/com_tjfields/vendors/mediaelementplayer/mediaelementplayer.min.css');

		if (strpos($layoutData['value'], "vimeo") !== false)
		{
			if (isset($layoutData['field']->element->attributes()->vimeo))
			{
				$doc->addScript(Uri::root() . 'media/com_tjfields/vendors/mediaelementplayer/renderers/vimeo.min.js');
			}
		}
		elseif (strpos($layoutData['value'], "facebook") !== false)
		{
			if (isset($layoutData['field']->element->attributes()->Facebook))
			{
				$doc->addScript(Uri::root() . 'media/com_tjfields/vendors/mediaelementplayer/renderers/facebook.min.js');
			}
		}
		elseif (strpos($layoutData['value'], "twitch") !== false)
		{
			if (isset($layoutData['field']->element->attributes()->twitch))
			{
				$doc->addScript(Uri::root() . 'media/com_tjfields/vendors/mediaelementplayer/renderers/twitch.min.js');
			}
		}
		elseif (strpos($layoutData['value'], "dailymotion") !== false)
		{
			if (isset($layoutData['field']->element->attributes()->dailymotion))
			{
				$doc->addScript(Uri::root() . 'media/com_tjfields/vendors/mediaelementplayer/renderers/dailymotion.min.js');
			}
		}
		elseif (strpos($layoutData['value'], "soundcloud") !== false)
		{
			if (isset($layoutData['field']->element->attributes()->soundcloud))
			{
				$doc->addScript(Uri::root() . 'media/com_tjfields/vendors/mediaelementplayer/renderers/soundcloud.min.js');
			}
		}

		$doc->addScriptDeclaration('
			jQuery(document).ready(function() {
				jQuery("#player_' . $layoutData['field']->id . '").mediaelementplayer({
					pluginPath: "/path/to/shims/",
					success: function(mediaElement, originalNode, instance) {
				}
				});
			});
		');

		return $html;
	}
}
