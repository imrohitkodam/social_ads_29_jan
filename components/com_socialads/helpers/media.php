<?php
/**
 * @version    SVN:<SVN_ID>
 * @package    SocialAds
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2015 TechJoomla. All rights reserved
 * @license    GNU General Public License version 2, or later
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\Filesystem\File;


// 2.7.5b1 manoj
require_once JPATH_SITE . '/components/com_socialads/classes/gifresizer.php';

/**
 * Helper class for media helper
 *
 * @since  1.6
 */
class Sa_MediaHelper
{
	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->sa_params = ComponentHelper::getParams('com_socialads');
	}

	/**
	 * Check for max media size allowed for upload
	 *
	 * @param   integer  $file_size  Max allowed file upload size in KB
	 *
	 * @return  integer  0 or 1
	 */
	public function check_max_size($file_size)
	{
		// @TODO needed?
		$this->media_size = $file_size;
		$max_media_size   = $this->sa_params->get('media_size') * 1024;

		if ($file_size > $max_media_size)
		{
			return 1;
		}

		return 0;
	}

	/**
	 * Detect file type, and,
	 * detect media group type image/video/flash
	 *
	 * @param   string  $file_type  MIME type
	 *
	 * @return  array
	 */
	public function check_media_type_group($file_type)
	{
		$allowed_media_types = array(
			'image' => array(
				// Images
				'image/gif',
				'image/png',
				'image/jpeg',
				'image/pjpeg',
				'image/jpeg',
				'image/pjpeg',
				'image/jpeg',
				'image/pjpeg'
			)
		);
		$flashUpload = $this->sa_params->get('flash_uploads');

		if ($this->sa_params->get('video_uploads'))
		{
			$allowed_media_types['video'] = array(
				// Video
				'video/mp4',
				'video/x-flv'
			);
		}

		if ($flashUpload == "1")
		{
			$allowed_media_types['flash'] = array(
				// Flash
				'application/x-shockwave-flash',
				'application/octet-stream',
				'application/vnd.adobe.flash.movie'
				// Swf
			);
		}

		if (in_array('html5_zip', $this->sa_params->get('ad_type_allowed')))
		{
			$allowed_media_types['html5'] = array(
				'application/zip',
				'application/x-zip-compressed'
			);
		}

		$media_type_group = '';
		$flag             = 0;

		foreach ($allowed_media_types as $key => $value)
		{
			if (in_array($file_type, $value))
			{
				$media_type_group = $key;
				$flag             = 1;
				break;
			}
		}

		$this->media_type       = $file_type;
		$this->media_type_group = $media_type_group;

		$return['media_type']       = $file_type;
		$return['media_type_group'] = $media_type_group;

		if (!$flag)
		{
			// File type not allowed
			$return['allowed'] = 0;

			return $return;
		}

		// Allowed file type
		$return['allowed'] = 1;

		return $return;
	}

	/**
	 * Method to get ad type
	 *
	 * @param   String  $fextension  file link
	 *
	 * @return  ad type
	 *
	 * @since   1.6
	 *
	 */
	public function get_ad_type($fextension)
	{
		// Images
		$allowed_media_types = array('image' => array('gif', 'png', 'jpeg', 'pjpeg', 'jpg'));
		$flashUpload = $this->sa_params->get('flash_uploads');

		if ($this->sa_params->get('video_uploads'))
		{
			// Video
			$allowed_media_types['video'] = array('flv', 'mp4');
		}

		if (in_array('html5_zip', $this->sa_params->get('ad_type_allowed')))
		{
			// Video
			$allowed_media_types['zip'] = array('html');
		}

		if ($flashUpload == "1")
		{
			// Flash
			$allowed_media_types['flash'] = array('swf');
		}

		$ad_type = '';
		$flag = 0;

		foreach ($allowed_media_types as $key => $value)
		{
			if (in_array($fextension, $value))
			{
				$ad_type = $key;
				$flag    = 1;
				break;
			}
		}

		// Return allowed file types
		return $ad_type;
	}

	/**
	 * Get adzone media dimensions
	 *
	 * @param   integer  $adzone  Zone id
	 *
	 * @return  object
	 */
	public function get_adzone_media_dimensions($adzone)
	{
		$db = Factory::getDbo();
		$query = "SELECT *
		 FROM #__ad_zone
		 WHERE id =" . $adzone;
		$db->setQuery($query);
		$adzone_media_dimensions = $db->loadObject();

		return $adzone_media_dimensions;
	}

	/**
	 * Get media extension
	 *
	 * @param   string  $file_name  Name of file
	 *
	 * @return  string
	 */
	public function get_media_extension($file_name)
	{
		$media_extension       = pathinfo($file_name);
		$this->media_extension = $media_extension['extension'];

		return $media_extension['extension'];
	}

	/**
	 * Check if media resizing is needed
	 *
	 * @param   aray    $adzone_media_dimnesions  Zone dimensions array
	 * @param   string  $file_tmp_name            File name
	 *
	 * @return  array
	 */
	public function check_media_resizing_needed($adzone_media_dimnesions, $file_tmp_name)
	{
		// Get uploaded image height and width
		// This will work for all images, an also for swf files
		list($width_img, $height_img) = getimagesize($file_tmp_name);

		$return['width_img']  = $width_img;
		$return['height_img'] = $height_img;

		$this->width  = $width_img;
		$this->height = $height_img;

		if ($width_img == $adzone_media_dimnesions->img_width && $height_img == $adzone_media_dimnesions->img_height)
		{
			// No resizing needed
			$return['resize'] = 0;

			return $return;
		}

		// Resizing needed
		$return['resize'] = 1;

		return $return;
	}

	/**
	 * Get media file name without extension
	 *
	 * @param   string  $file_name  Name of file
	 *
	 * @return  string
	 */
	public function get_media_file_name_without_extension($file_name)
	{
		$media_extension = pathinfo($file_name);

		return $media_extension['filename'];
	}

	/**
	 * Get new dimensions
	 *
	 * @param   integer  $max_zone_width   Zone width
	 * @param   integer  $max_zone_height  Zone height
	 * @param   string   $option           Resize option
	 *
	 * @return  array
	 */
	public function get_new_dimensions($max_zone_width, $max_zone_height, $option)
	{
		switch ($option)
		{
			case 'exact':
				$new_calculated_width  = $max_zone_width;
				$new_calculated_height = $max_zone_height;
				break;
			case 'auto':
				$new_dimensions = $this->get_optimal_dimensions($max_zone_width, $max_zone_height);
				$new_calculated_width  = $new_dimensions['new_calculated_width'];
				$new_calculated_height = $new_dimensions['new_calculated_height'];
				break;
		}

		$new_dimensions['new_calculated_width']  = $new_calculated_width;
		$new_dimensions['new_calculated_height'] = $new_calculated_height;

		return $new_dimensions;
	}

	/**
	 * Get optimal dimensions
	 *
	 * @param   integer  $max_zone_width   Zone width
	 * @param   integer  $max_zone_height  Zone height
	 *
	 * @return  array
	 */
	public function get_optimal_dimensions($max_zone_width, $max_zone_height)
	{
		// @TODO not sure abt line below
		$top_offset = 0;

		if ($max_zone_height == null)
		{
			if ($this->width < $max_zone_width)
			{
				$new_calculated_width = $this->width;
			}
			else
			{
				$new_calculated_width = $max_zone_width;
			}

			$ratio_orig            = $this->width / $this->height;
			$new_calculated_height = $new_calculated_width / $ratio_orig;

			$blank_height = $new_calculated_height;
			$top_offset   = 0;
		}
		else
		{
			if ($this->width <= $max_zone_width && $this->height <= $max_zone_height)
			{
				$new_calculated_height = $this->height;
				$new_calculated_width = $this->width;
			}
			else
			{
				if ($this->width > $max_zone_width)
				{
					$ratio = ($this->width / $max_zone_width);
					$new_calculated_width  = $max_zone_width;
					$new_calculated_height = ($this->height / $ratio);

					if ($new_calculated_height > $max_zone_height)
					{
						$ratio = ($new_calculated_height / $max_zone_height);
						$new_calculated_height = $max_zone_height;
						$new_calculated_width  = ($new_calculated_width / $ratio);
					}
				}

				if ($this->height > $max_zone_height)
				{
					$ratio = ($this->height / $max_zone_height);
					$new_calculated_height = $max_zone_height;
					$new_calculated_width  = ($this->width / $ratio);

					if ($new_calculated_width > $max_zone_width)
					{
						$ratio = ($new_calculated_width / $max_zone_width);
						$new_calculated_width  = $max_zone_width;
						$new_calculated_height = ($new_calculated_height / $ratio);
					}
				}
			}

			if ($new_calculated_height == 0 || $new_calculated_width == 0 || $this->height == 0 || $this->width == 0)
			{
				die(Text::_('COM_SOCIALADS_ERR_MSG_FILE_VALID'));
			}

			if ($new_calculated_height < 45)
			{
				$blank_height = 45;
				$top_offset   = round(($blank_height - $new_calculated_height) / 2);
			}
			else
			{
				$blank_height = $new_calculated_height;
			}
		}

		$new_dimensions['new_calculated_width']  = $new_calculated_width;
		$new_dimensions['new_calculated_height'] = $new_calculated_height;
		$new_dimensions['top_offset']            = $top_offset;
		$new_dimensions['blank_height']          = $blank_height;

		return $new_dimensions;
	}

	/*public function uploadImage($file_field, $maxSize, $max_zone_width, $fullPath, $relPath, $colorR, $colorG, $colorB, $max_zone_height = null){*/
	/**
	 * [uploadImage description]
	 *
	 * @param   [type]  $file_field                   [description]
	 * @param   [type]  $max_zone_width               [description]
	 * @param   [type]  $fullPath                     [description]
	 * @param   [type]  $relPath                      [description]
	 * @param   [type]  $colorR                       [description]
	 * @param   [type]  $colorG                       [description]
	 * @param   [type]  $colorB                       [description]
	 * @param   [type]  $new_media_width              [description]
	 * @param   [type]  $new_media_height             [description]
	 * @param   [type]  $blank_height                 [description]
	 * @param   [type]  $top_offset                   [description]
	 * @param   [type]  $media_extension              [description]
	 * @param   [type]  $file_name_without_extension  [description]
	 * @param   [type]  $max_zone_height              [description]
	 *
	 * @return  [type]                                [description]
	 */
	public function uploadImage($file_field, $max_zone_width, $fullPath, $relPath, $colorR, $colorG, $colorB,
		$new_media_width, $new_media_height,
		$blank_height, $top_offset, $media_extension, $file_name_without_extension, $max_zone_height = null, $needResize = 1)
	{
		// If socialads images folder, as per config, is not present - create it
		if (!Folder::exists(COM_SA_CONST_MEDIA_ROOTPATH))
		{
			@mkdir(COM_SA_CONST_MEDIA_ROOTPATH);
		}

		switch ($this->media_type_group)
		{
			case "flash":
				// Retrieve file details from uploaded file, sent from upload form
				$file = $_FILES[$file_field];

				// Clean up filename to get rid of strange characters like spaces etc
				$filename = File::makeSafe($file['name']);

				// Set up the source and destination of the file
				$src = $file['tmp_name'];

				$filename  = strtolower($filename);
				$filename  = preg_replace('/\s/', '_', $filename);
				$timestamp = time();

				$file_name_without_extension = $this->get_media_file_name_without_extension($filename);
				$filename                    = $file_name_without_extension . "_" . $timestamp . "." . $this->media_extension;

				// $dest = JPATH_SITE . '/images/socialads/swf/' . $filename;
				$dest = COM_SA_CONST_MEDIA_ROOTPATH . '/swf/' . $filename;

				// First check if the file has the right extension, we need swf only
				if (File::upload($src, $dest))
				{
					// $dest = JUri::root() . 'images/socialads/swf/' . $filename;
					$dest = COM_SA_CONST_MEDIA_ROOTURL . '/swf/' . $filename;

					return $dest;
				}

			break;

			case "video":
				// Retrieve file details from uploaded file, sent from upload form
				$file = $_FILES[$file_field];

				// Clean up filename to get rid of strange characters like spaces etc
				$filename = File::makeSafe($file['name']);

				// Set up the source and destination of the file
				$src = $file['tmp_name'];

				$filename  = strtolower($filename);
				$filename  = preg_replace('/\s/', '_', $filename);
				$timestamp = time();

				$file_name_without_extension = $this->get_media_file_name_without_extension($filename);
				$filename                    = $file_name_without_extension . "_" . $timestamp . "." . $this->media_extension;

				// $dest = JPATH_SITE . '/images/socialads/vids/' . $filename;
				$dest = COM_SA_CONST_MEDIA_ROOTPATH . '/vids/' . $filename;

				if (File::upload($src, $dest))
				{
					// $dest = JUri::root() . 'images/socialads/vids/' . $filename;
					$dest = COM_SA_CONST_MEDIA_ROOTURL . '/vids/' . $filename;

					return $dest;
				}

			break;

			case "html5":
				// Retrieve file details from uploaded file, sent from upload form
				$file = $_FILES[$file_field];

				// Clean up filename to get rid of strange characters like spaces etc
				$filename = File::makeSafe($file['name']);

				// Set up the source and destination of the file
				$src = $file['tmp_name'];

				$filename  = strtolower($filename);
				$filename  = preg_replace('/\s/', '_', $filename);
				$timestamp = time();

				$file_name_without_extension = $this->get_media_file_name_without_extension($filename);
				$filename                    = $file_name_without_extension . "_" . $timestamp . "." . $this->media_extension;
				$newFileName                    = $file_name_without_extension . "_" . $timestamp;

				$dest = COM_SA_CONST_MEDIA_ROOTPATH . '/html5_zip/' . $newFileName;

				// jimport('techjoomla.common');
				// jimport( 'joomla.filesystem.archive.bzip2' );
				// $SocialLibraryObject = new JArchiveBzip2;

				// $dest = COM_SA_CONST_MEDIA_ROOTPATH . '/html5_zip/' . $filename;
				// if (!$SocialLibraryObject->extract($dest, $src))
				// {
				// 	return false;
				// }

				$oldFile = COM_SA_CONST_MEDIA_ROOTPATH . '/html5_zip/' . 'old_file' . $filename;

				if (File::upload($src, $oldFile))
				{
					jimport('techjoomla.common');
					$SocialLibraryObject = new TechjoomlaCommon;

					if (!$SocialLibraryObject->extractCourse($dest, $oldFile))
					{
						return false;
					}

					if (JFile::exists($oldFile))
					{
						JFile::delete($oldFile);
					}

					// Create htaccess file in each lessons folder to allow direct files access of the folder
					$htaccessFile	= $dest . '/.htaccess';

					if (!JFile::exists($htaccessFile))
					{
						$content = "allow from all";
						$result = JFile::write($htaccessFile, $content);

						if (!$result)
						{
							return false;
						}

						$dest = COM_SA_CONST_MEDIA_ROOTURL . '/html5_zip/' . $newFileName;

						return $dest;
					}

					return $dest;
				}

			break;
		}
		

		$errorList = array();
		$folder    = $relPath;
		$match     = "";
		$filesize  = $_FILES[$file_field]['size'];

		if ($filesize > 0)
		{
			$filename = strtolower($_FILES[$file_field]['name']);
			$filename = preg_replace('/\s/', '_', $filename);

			if ($filesize < 1)
			{
				$errorList[] = Text::_('COM_SOCIALADS_ERR_MSG_FILE_EMPTY');
			}

			if (count($errorList) < 1)
			{
				// File is allowed
				$match = '1';
				$NUM = time();
				$front_name  = $file_name_without_extension;
				$newfilename = $front_name . "_" . $NUM . "." . $media_extension;
				$save = JPATH_SITE . '/' . $folder . $newfilename;

				if (!file_exists($save))
				{
					if (!$needResize)
					{
						if (!File::upload($_FILES[$file_field]['tmp_name'], $save))
						{
							$errorList[] = Text::_('COM_SOCIALADS_ERR_SOMETHING_WENT_WRONG');
						}
					}
					else
					{
						list($this->width, $this->height) = getimagesize($_FILES[$file_field]['tmp_name']);
						$image_p = imagecreatetruecolor($new_media_width, $blank_height);
						$white = imagecolorallocate($image_p, $colorR, $colorG, $colorB);

						// START added to preserve transparency
						imagealphablending($image_p, false);
						imagesavealpha($image_p, true);
						$transparent = imagecolorallocatealpha($image_p, 255, 255, 255, 127);
						imagefill($image_p, 0, 0, $transparent);

						// END added to preserve transparency

						switch ($media_extension)
						{
							case "gif":
								// New Instance Of GIFResizer
								$gr = new gifresizer;

								// Directory Used for extracting GIF Animation Frames
								$gr->temp_dir = JPATH_SITE . '/' . $folder . 'frames';

								// If folder is not present create it
								if (!Folder::exists($gr->temp_dir))
								{
									@mkdir($gr->temp_dir);
								}

								// Resizing the animation into a new file.
								// $gr->resize("gifs/1.gif","resized/1_resized.gif",50,50);

								// Resizing the animation into a new file.
								if ($needResize)
								{
									$gr->resize($_FILES[$file_field]['tmp_name'], $save, $new_media_width, $new_media_height);
								}
							break;

							case "jpg":
								$image = @imagecreatefromjpeg($_FILES[$file_field]['tmp_name']);
								@imagecopyresampled($image_p, $image, 0, $top_offset, 0, 0, $new_media_width, $new_media_height, $this->width, $this->height);
							break;

							case "jpeg":
								$image = @imagecreatefromjpeg($_FILES[$file_field]['tmp_name']);
								@imagecopyresampled($image_p, $image, 0, $top_offset, 0, 0, $new_media_width, $new_media_height, $this->width, $this->height);
							break;

							case "png":
								$image = @imagecreatefrompng($_FILES[$file_field]['tmp_name']);
								@imagecopyresampled($image_p, $image, 0, $top_offset, 0, 0, $new_media_width, $new_media_height, $this->width, $this->height);
							break;
						}

						switch ($media_extension)
						{
							/*
							case "gif":
								if(!@imagegif($image_p, $save)){
									$errorList[]= Text::_('COM_SOCIALADS_ERR_MSG_FILE_GIF');
								}

							break;
							*/

							case "jpg":
								if (!@imagejpeg($image_p, $save, 100))
								{
									$errorList[] = Text::_('COM_SOCIALADS_ERR_MSG_FILE_JPG');
								}
							break;

							case "jpeg":
								if (!@imagejpeg($image_p, $save, 100))
								{
									$errorList[] = Text::_('COM_SOCIALADS_ERR_MSG_FILE_JPEG');
								}
							break;

							case "png":
								if (!@imagepng($image_p, $save, 0))
								{
									$errorList[] = Text::_('COM_SOCIALADS_ERR_MSG_FILE_PNG');
								}
							break;
						}

						if (!empty($image))
						{
							@imagedestroy($image);
						}
					}
				}
				else
				{
					$errorList[] = Text::_('COM_SOCIALADS_ERR_MSG_FILE_EXIST');
				}
			}
		}
		else
		{
			$errorList[] = Text::_('COM_SOCIALADS_ERR_MSG_FILE_NO');
		}

		if (!$match)
		{
			$errorList[] = Text::_('COM_SOCIALADS_ERR_MSG_FILE_ALLOW') . ":" . $filename;
		}

		if (sizeof($errorList) == 0)
		{
			return $fullPath . $newfilename;
		}
		else
		{
			$eMessage = array();

			for ($x = 0; $x < sizeof($errorList); $x++)
			{
				$eMessage[] = $errorList[$x];
			}

			return $eMessage;
		}
	}

	/**
	 * Check media dimensions allow to upload
	 *
	 * @param   aray    $adzone_media_dimnesions  Zone dimensions array
	 * @param   string  $file_tmp_name            File name
	 *
	 * @return  array
	 */
	public function check_media_dimensions_allow_to_upload($adzone_media_dimnesions, $file_tmp_name)
	{
		$return = array();
		$return['allowToUpolad'] = 0;
		$return['needResize'] = 1;
		$return['message'] = '';
		if ($this->sa_params->get('upload_different_size'))
		{
			$return['allowToUpolad'] = 1;
		}
		// Get uploaded image height and width
		// This will work for all images, an also for swf files
		list($width_img, $height_img) = getimagesize($file_tmp_name);

		$return['width_img']  = $width_img;
		$return['height_img'] = $height_img;

		$this->width  = $width_img;
		$this->height = $height_img;

		

		if ($adzone_media_dimnesions->use_image_ratio)
		{
			$return['message'] = $adzone_media_dimnesions->img_width_ratio . ' : ' . $adzone_media_dimnesions->img_height_ratio;
			// ratio = width / height
			$ratio = round($width_img / $height_img, 1);
			$zoneImageRatio = round($adzone_media_dimnesions->img_width_ratio / $adzone_media_dimnesions->img_height_ratio, 1);
			if ($ratio == $zoneImageRatio)
			{
				// No resizing needed
				$return['allowToUpolad'] = 1;
				$return['needResize'] = 0;
			}
		}
		else
		{
			$return['message'] = $adzone_media_dimnesions->img_height . ' * ' . $adzone_media_dimnesions->img_width;
			if ($width_img == $adzone_media_dimnesions->img_width && $height_img == $adzone_media_dimnesions->img_height)
			{
				// No resizing needed
				// No resizing needed
				$return['allowToUpolad'] = 1;
				$return['needResize'] = 0;
			}
		}

		return $return;
	}
}
