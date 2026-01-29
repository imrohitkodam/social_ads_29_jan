<?php
/**
 * @package     Techjoomla_Library
 * @subpackage  TjMedia
 * @author      Techjoomla <contact@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2018 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
defined('JPATH_BASE') or die();

use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\Router\Route;

JLoader::import("/techjoomla/media/storage/local", JPATH_LIBRARIES);
JLoader::import("/techjoomla/media/xref", JPATH_LIBRARIES);

HTMLHelper::_('jquery.framework');

$document = Factory::getDocument();
HTMLHelper::_('stylesheet', '/media/com_warehouse/vendors/css/magnific-popup.css');
HTMLHelper::_('script', '/media/com_warehouse/vendors/js/jquery.magnific-popup.min.js');
HTMLHelper::_('script', '/libraries/techjoomla/assets/js/tjfile.js');
$document->addScriptDeclaration("jQuery(document).ready(function() { tjFile.eventImgPopup('popup-media');});");

JText::script('LIB_TECHJOOMLA_ALLOWED_FILE_SIZE');
JText::script('LIB_TECHJOOMLA_ERR_MSG_FILE_ALLOW');

/**
 * Supports an HTML select list of categories
 *
 * @since  1.6
 */

class JFormFieldTjfile extends FormField
{
	/**
	 * The form field type.
	 *
	 * @var        string
	 * @since    1.6
	 */
	protected $type = 'file';

	private $title;

	private $uploadPath;

	private $saveData;

	private $storage;

	private $state;

	private $access;

	private $params;

	private $allowedExt;

	private $extension;

	private $acl;

	private $allowedType;

	private $task;

	private $preview;

	private $accept;

	/**
	 * Method to get the field input markup.
	 *
	 * @return    string    The field input markup.
	 *
	 * @since    1.6
	 */
	protected function getInput()
	{
		$default           = ! empty($this->default)    ? 'default="' . $this->default . '"' : '';
		$size              = ! empty($this->size)       ? 'size="' . $this->size . '"' : '';
		$class             = ! empty($this->class)      ? 'class="' . $this->class . '"' : '';
		$labelclass        = ! empty($this->labelclass) ? 'labelclass="' . $this->labelclass . '"' : '';
		$disabled          = ! empty($this->disabled)   ? 'disabled="' . $this->disabled . '"' : '';
		$required          = ! empty($this->required)   ? 'required="' . $this->required . '"' : '';
		$validate          = ! empty($this->validate)   ? 'validate="' . $this->validate . '"' : '';
		$showon            = ! empty($this->showon)     ? 'showon="' . $this->showon . '"' : '';
		$multiple          = ! empty($this->multiple)   ? 'multiple="' . $this->multiple . '"' : '';

		$this->accept      = $this->getAttribute('accept', '');
		$this->title       = $this->getAttribute('title', '');
		$this->uploadPath  = $this->getAttribute('uploadPath', '');
		$this->saveData    = $this->getAttribute('saveData', '1');
		$this->storage     = $this->getAttribute('storage', '');
		$this->state       = $this->getAttribute('state', '');
		$this->access      = $this->getAttribute('access', '');
		$this->params      = $this->getAttribute('params', '');
		$this->allowedExt  = $this->getAttribute('allowedExt', '');
		$this->extension   = $this->getAttribute('extension', '');
		$this->acl         = $this->getAttribute('acl', '');
		$this->allowedType = $this->getAttribute('allowedType', '');
		$this->task        = $this->getAttribute('task', '');
		$this->preview     = $this->getAttribute('preview', '0');
		$media_id          = '';
		$imgLink           = '#';
		$mediaLib          = '';

		// If the incoming value is string then convert the value to object.
		if (is_string($this->value))
		{
			$this->value = json_decode($this->value);
		}

		if (!empty($this->value->media_id))
		{
			$media_id = $this->value->media_id;
			$mediaLib = TJMediaStorageLocal::getInstance();
			$mediaLib->load($this->value->media_id);
			$imgLink = Route::_(Uri::root() . $mediaLib->path . '/' . $mediaLib->source, false);
		}

		// Initialize variables.
		$html = '<input type="file"
			id="' . $this->id . '"
			name="' . $this->name . '" ' .

			$default . ' ' .
			$size . ' ' .
			$class . ' ' .
			$labelclass . ' ' .
			$disabled .
			' onChange="tjFile.validateFile(this)" ' .
			' accept="' . $this->accept . '" ' .
			$required . ' ' .
			$validate . ' ' .
			$showon . ' ' .
			$multiple .
			'data-uploadPath="' . $this->uploadPath . '"
			 data-save-data="' . $this->saveData . '"
			 data-title="' . $this->title . '"
			 data-state="' . $this->state . '"
			 data-old-data=""
			 data-storage="' . $this->storage . '"
			 data-access="' . $this->access . '"
			 data-params="' . $this->params . '"
			 data-allowedExt="' . $this->allowedExt . '"
			 data-extension="' . $this->extension . '"
			 data-acl="' . $this->acl . '"
			 data-allowedType="' . $this->allowedType . '"
			 data-task="' . $this->task . '">

			<input type="hidden" name="' . $this->name . '[media_id]" value="' . $media_id . '">
		';

		if (!empty($this->allowedExt) || !empty($this->size))
		{
			$html .= '<div>
				<p class="text text-info small">';

					if (!empty($this->allowedExt))
					{
						$html .= Text::sprintf('LIB_TECHJOOMLA_ALLOWED_FILE_TYPES', $this->allowedExt) . '<br/>';
					}

					if (!empty($this->size))
					{
						$html .= Text::sprintf('LIB_TECHJOOMLA_ALLOWED_FILE_SIZE', $this->size);
					}

			$html .= '</p>
			</div>';
		}

		$html .= '<div class="tjfile-error-message" style="display:none;">
			<p class="text text-error small"></p>
		</div>';

		$html .= '<div class="tjfile-success-message" style="display:none;">
			<p class="text text-success small"></p>
		</div>';

		if (@is_array(getimagesize($imgLink)))
		{
			$isImage = true;
		}
		else
		{
			$isImage = false;
		}

		if ($isImage && !empty($this->preview))
		{
			if (!empty($this->value->media_id))
			{
				$html .= '<div style="height:150px;width:150px" class="tjfile-uploaded-media">
					<a href="' . $imgLink . '" title="" class="popup-media">
						<img src="' . $imgLink . '" height="auto" width="auto" />
					</a>
				</div>';
			}
		}
		else
		{
			if (!empty($this->value->media_id))
			{
				$html .= "<a name='" . $this->name . '[imgLink]' . "' href=" . $imgLink . " target='_blank' rel='noreferrer'>" .
						$mediaLib->original_filename . "</a>";
			}
			else
			{
				$html .= "<a class='hide' name='" . $this->name . '[imgLink]' . "' href='#' target='_blank' rel='noreferrer'></a>";
			}
		}

		return $html;
	}
}
