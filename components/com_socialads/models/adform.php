<?php
/**
 * @version    SVN:<SVN_ID>
 * @package    Com_Socialads
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2015 TechJoomla. All rights reserved
 * @license    GNU General Public License version 2, or later
 */

// No direct access.
defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\Utilities\ArrayHelper;
use Joomla\Registry\Registry;

/**
 * Adform model class
 *
 * @since  1.6
 */
class SocialadsModelAdForm extends AdminModel
{
	/**
	 * Constructor.
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 *
	 * @since   3.2
	 */
	public function __construct($config = array())
	{
		$config['event_after_delete'] = 'onAfterSocialAdDelete';
		parent::__construct($config);

		$TjGeoHelper = JPATH_ROOT . '/components/com_tjfields/helpers/geo.php';

		if (!class_exists('TjGeoHelper'))
		{
			JLoader::register('TjGeoHelper', $TjGeoHelper);
			JLoader::load('TjGeoHelper');
		}

		$this->TjGeoHelper = new TjGeoHelper;

		// @TODO - manoj - convert to static helper
		require_once JPATH_ROOT . '/components/com_socialads/helpers/createad.php';
	}

	/**
	 * Returns a Table object, always creating it.
	 *
	 * @param   string  $type    The table type to instantiate
	 * @param   string  $prefix  A prefix for the table class name. Optional.
	 * @param   array   $config  Configuration array for model. Optional.
	 *
	 * @return  JTable    A database object
	 */
	public function getTable($type = 'Ad', $prefix = 'SocialadsTable', $config = array())
	{
		return Table::getInstance($type, $prefix, $config);
	}

	/**
	 * Method to get the record form.
	 *
	 * @param   array    $data      An optional array of data for the form to interogate.
	 * @param   boolean  $loadData  True if the form is to load its own data (default case), false if not.
	 *
	 * @return  JForm   A JForm object on success, false on failure
	 *
	 * @since  1.6
	 */
	public function getForm($data = array(), $loadData = true)
	{
		// Initialise variables.
		$app = Factory::getApplication();

		// Get the form.
		$form = $this->loadForm('com_socialads.ad', 'ad', array(
			'control' => 'jform',
			'load_data' => $loadData
		)
		);

		if (empty($form))
		{
			return false;
		}

		return $form;
	}

	/**
	 * This fetching all inserted details from DB
	 *
	 * @param   integer  $ad_id  ad ID
	 *
	 * @return  integer
	 *
	 * @since  3.0
	 **/
	public function getData($ad_id)
	{
		require_once JPATH_SITE . '/components/com_socialads/helpers/createad.php';
		$db    = Factory::getDbo();
		$query	= $db->getQuery(true);
		$query->select('a.*');
		$query->from($db->quoteName('#__ad_data', 'a'));
		$query->where($db->quoteName('a.ad_id') . ' = ' . $ad_id);
		$db->setQuery($query);
		$addata         = $db->loadObject();
		$count          = 0;
		$createAdHelper = new createAdHelper;
		$adfields       = $createAdHelper->chkadfields();

		if ($adfields != '')
		{
			// Chk empty
			$query	= $db->getQuery(true);
			$query->select('COUNT(*)');
			$query->from($db->quoteName('#__ad_fields', 'f'));
			$query->where($db->quoteName('f.adfield_ad_id') . ' = ' . $ad_id);
		
			$db->setQuery($query);
			$count = $db->loadResult();

			if ($addata && $addata->ad_alternative == 0 && $count > 0)
			{
				$query	= $db->getQuery(true);
				$query->select(array('a.*', 'f.*'));
				$query->from($db->quoteName('#__ad_data', 'a'));
				$query->from($db->quoteName('#__ad_fields', 'f'));
				$query->where($db->quoteName('a.ad_id') . ' = ' . $ad_id);
				$query->where($db->quoteName('f.adfield_ad_id') . ' = ' . $ad_id);

				$db->setQuery($query);
				$addata = $db->loadObject();
			}
		}

		$addata_result[0] = $count;

		if (!empty($addata))
		{
			$addata_result[1] = $addata;
			$addata_result[1]->sa_accpt_terms = $this->getTnCData($ad_id, $addata->created_by);
		}

		return $addata_result;
	}

	/**
	 * Get selected user data
	 *
	 * @param   integer  $uid  user id
	 *
	 * @return  array
	 *
	 * @since  1.6
	 */
	public function getPromoterPlugins($uid)
	{
		$opt = array();
		PluginHelper::importPlugin('socialadspromote');
		$results    = Factory::getApplication()->triggerEvent('onPromoteList', array($uid));

		foreach ($results as $result)
		{
			if (!empty($result))
			{
				$plug_name    = $result[0]->value;
				$plug_name    = explode('|', $plug_name);
				$plugin       = PluginHelper::getPlugin('socialadspromote', $plug_name[0]);
				$pluginParams = json_decode($plugin->params);
				$opt[]        = HTMLHelper::_('select.option', '<OPTGROUP>', $pluginParams->plugin_name);

				foreach ($result as $res)
				{
					$opt[] = HTMLHelper::_('select.option', $res->value, $res->text);
				}

				$opt[] = HTMLHelper::_('select.option', '</OPTGROUP>');
			}
		}

		$sel[0]        = new stdClass;
		$sel[0]->value = '';
		$sel[0]->text  = Text::_('COM_SOCIALADS_SELECT_PROMOTE_PLG');
		$opt           = array_merge($sel, $opt);

		$htmlSelect = HTMLHelper::_('select.genericlist',
						$opt, 'addatapluginlist',
						'class="promotplglist chzn-done"
						onchange="sa.create.populatePromotePlgList()"',
						'value',
						'text',
						''
						);

		return $htmlSelect;
	}

	/**
	 * Function to get zone
	 *
	 * @param   string  $typ  zone type
	 *
	 * @return  array
	 *
	 * @since  1.6
	 */
	public function getZones($typ)
	{
		$db = Factory::getDbo();
		$affiliate    = 'affiliate';
		$query = $db->getQuery(true);
		$query->select('id, zone_name');
		$query->from($db->qn('#__ad_zone'));
		$query->where($db->qn('state') . ' = ' . 1);
		$query->where($db->qn('ad_type') . ' = ' . $db->quote('|' . $typ . '|'));
		$query->orWhere($db->qn('ad_type') . ' = ' . $db->quote('|' . $affiliate . '||' . $typ . '|'));
		$query->orWhere($db->qn('ad_type') . ' = ' . $db->quote('|' . $typ . '||' . $affiliate . '|'));

		if ($typ == 'affiliate')
		{
			$query->orWhere($db->qn('ad_type') . ' LIKE ' . $db->quote('%' . $typ . '%'));
		}

		$query->order($db->qn('ordering'));
		$db->setQuery($query);
		$ad_zones = $db->loadObjectList();

		$mod_socialads = $db->quote('%' . $db->escape('mod_socialads', true) . '%');
		$query         = $db->getQuery(true);
		$query->select('params');
		$query->from($db->qn('#__modules'));
		$query->where($db->qn('module') . ' LIKE ' . $mod_socialads);
		$query->where($db->qn('published') . ' = ' . 1);
		$db->setQuery($query);
		$params = $db->loadObjectList();
		$module = array();

		foreach ($params as $params)
		{
			$params1 = str_replace('"', '', $params->params);
			$single  = explode(",", $params1);

			foreach ($single as $single)
			{
				$name = explode(":", $single);

				if ($name[0] == 'zone')
				{
					$module[] = $name[1];
				}
			}
		}

		$z = array();

		foreach ($ad_zones as $zone)
		{
			if (in_array($zone->id, $module))
			{
				$z[] = array(
					"zone_id" => $zone->id,
					"zone_name" => $zone->zone_name
				);
			}
		}

		return json_encode($z);
	}

	/**
	 * Function to get zone
	 *
	 * @param   integer  $ad_id  ad ID
	 *
	 * @return  integer
	 *
	 * @since  3.0
	 **/
	public function getzone($ad_id)
	{
		$db = Factory::getDbo();

		$query	= $db->getQuery(true);
		$query->select(array('az.id', 'az.zone_name', 'az.state', 'az.orientation', 'az.ad_type', 'az.max_title',
			'az.max_des', 'az.img_width', 'az.img_height', 'az.per_click', 'az.per_imp', 'az.per_day', 
			'az.layout'));
		$query->from($db->quoteName('#__ad_data', 'ad'));
		$query->join('LEFT', $db->quoteName('#__ad_zone', 'az') . 'ON' . $db->quoteName('az.id') . '=' . $db->quoteName('ad.ad_zone'));
		$query->where($db->quoteName('ad.ad_id') . ' = ' . $ad_id);

		$db->setQuery($query);
		$zone = $db->loadObject();

		return $zone;
	}

	/**
	 * Fetching all inserted details from DB for geo targeting
	 *
	 * @param   integer  $ad_id  ad ID
	 *
	 * @return  array
	 *
	 * @since  3.0
	 **/
	public function getData_context_target($ad_id)
	{
		$db    = Factory::getDbo();
		$user  = Factory::getUser();
		$input = Factory::getApplication()->input;

		// $ad_id=$input->get('adid',0,'INT');
		$query	= $db->getQuery(true);
		$query->select($db->quoteName('a.keywords'));
		$query->from($db->quoteName('#__ad_contextual_target', 'a'));
		$query->where($db->quoteName('a.ad_id') . ' = ' . $ad_id);
		$db->setQuery($query);
		$addata = $db->loadColumn();

		if (!empty($addata))
		{
			return $addata[0];
		}
	}

	/**
	 * Fetching all inserted details from DB for geo targeting
	 *
	 * @param   integer  $ad_id  ad ID
	 *
	 * @return  integer
	 *
	 * @since  3.0
	 **/
	public function getData_geo_target($ad_id)
	{
		$db    = Factory::getDbo();
		$user  = Factory::getUser();
		$input = Factory::getApplication()->input;

		$query	= $db->getQuery(true);
		$query->select('a.*');
		$query->from($db->quoteName('#__ad_geo_target', 'a'));
		$query->where($db->quoteName('a.ad_id') . ' = ' . $ad_id);
		$db->setQuery($query);
		$addata = $db->loadAssocList();

		if (!empty($addata[0]))
		{
			return $addata[0];
		}
		else
		{
			return $addata;
		}
	}

	/**
	 * Functin to get pricing data
	 *
	 * @param   integer  $ad_id  ad ID
	 *
	 * @return  integer
	 *
	 * @since  3.0
	 **/
	public function getpricingData($ad_id)
	{
		$db    = Factory::getDbo();
		$query	= $db->getQuery(true);
		$query->select(array('pi.ad_credits_qty', 'ad.ad_payment_type', 'ad.ad_startdate', 'o.original_amount'));
		$query->from($db->quoteName('#__ad_data', 'ad'));
		$query->join('LEFT', $db->quoteName('#__ad_payment_info', 'pi') . 'ON' . $db->quoteName('pi.ad_id') . '=' . $db->quoteName('ad.ad_id'));
		$query->join('LEFT', $db->quoteName('#__ad_orders', 'o') . 'ON' . $db->quoteName('pi.order_id') . '=' . $db->quoteName('o.id'));
		$query->where($db->quoteName('ad.ad_id') . ' = ' . $ad_id);
		$query->where($db->quoteName('o.status') . ' = ' . $db->quote('P'));

		$db->setQuery($query);
		$result = $db->loadObject();

		return $result;
	}

	/**
	 * Function to get zone data
	 *
	 * @param   string  $typ  zone type
	 *
	 * @return  array
	 *
	 * @since  1.6
	 */
	public function getZonesData($typ)
	{
		require JPATH_SITE . "/components/com_socialads/defines.php";
		$db    = Factory::getDbo();
		$query	= $db->getQuery(true);
		$query->select('*');
		$query->from($db->quoteName('#__ad_zone'));
		$query->where($db->quoteName('id') . ' = ' . $typ);
		$query->where($db->quoteName('state') . ' = ' . 1);

		$db->setQuery($query);
		$zone_data = $db->loadObjectList();

		$z = array();

		foreach ($zone_data as $zone)
		{
			if ($zone->ad_type == '|media|' || $zone->ad_type == '|html5_zip|' || $zone->ad_type == '|media||affiliate|' || $zone->ad_type == '|html5_zip||affiliate|')
			{
				$zone->max_title = $title_char;
			}

			$layouts = explode('|', $zone->layout);
			$z[]     = array(
				"zone_id"   => $zone->id,
				"zone_name" => $zone->zone_name,
				"max_title" => $zone->max_title,
				"max_des"   => $zone->max_des,
				"img_width" => $zone->img_width,
				"img_height" => $zone->img_height,
				"per_click" => $zone->per_click,
				"per_imp"   => $zone->per_imp,
				"per_day"   => $zone->per_day,
				"layout"    => $layouts,
				"base"      => Uri::Root()
			);
		}

		return json_encode($z);
	}

	/**
	 * Function to fetch data of a promote plugin
	 *
	 * @param   string  $promote_plg  promote plugn name
	 * @param   string  $promote_id   promote plugn id
	 *
	 * @return  array
	 *
	 * @since  1.6
	 */
	public function getPromotePluginPreviewData($promote_plg, $promote_id)
	{

		// Trigger the Promot Plg Methods to get the preview data
		PluginHelper::importPlugin('socialadspromote', $promote_plg);

		$previewdata = Factory::getApplication()->triggerEvent('onSocialAdPromoteData', array($promote_id));
		$previewdata = $previewdata[0];

		$filename = COM_SA_CONST_MEDIA_ROOTPATH . '/' . basename(JPATH_SITE . $previewdata[0]->image);

		$mystring  = $previewdata[0]->image;
		$findifurl = 'http';
		$ifurl     = strpos($mystring, $findifurl);

		if ($ifurl === false)
		{
			$source1 = JPATH_SITE . '/' . $previewdata[0]->image;
		}
		else
		{
			$source1 = $previewdata[0]->image;
			$content = file_get_contents($previewdata[0]->image);

			// Store in the filesystem.
			$fp = fopen($filename, "w");
			fwrite($fp, $content);
			fclose($fp);
		}

		if (!File::exists($filename))
		{
			File::copy($source1, $filename);
		}

		$previewdata[0]->imagesrc = COM_SA_CONST_MEDIA_ROOTURL . '/' . basename(JPATH_SITE . $previewdata[0]->image);

		$previewdata[0]->image = '<img width="100" src="' . COM_SA_CONST_MEDIA_ROOTURL . '/' . basename(JPATH_SITE . $previewdata[0]->image) . '" />';

		$url                  = explode("://", $previewdata[0]->url);
		$previewdata[0]->url1 = $url[0];
		$previewdata[0]->url2 = $url[1];

		return $previewdata;
	}

	/**
	 * Image upload
	 *
	 * @return  void
	 *
	 * @since  1.6
	 */
	public function mediaUpload()
	{
		require_once JPATH_SITE . '/components/com_socialads/helpers/media.php';
		$sa_params    = ComponentHelper::getParams('com_socialads');
		$data         = Factory::getApplication()->input->post;
		$upload_image = '';

		// Create object of media helper class
		$media = new sa_mediaHelper;

		// Get uploaded media details
		$file_field = strip_tags($_REQUEST['filename']);

		// Orginal file name
		$file_name = $_FILES[$file_field]['name'];

		// Convert name to lowercase
		$file_name = strtolower($_FILES[$file_field]['name']);

		// Replace "spaces" with "_" in filename
		$file_name     = preg_replace('/\s/', '_', $file_name);
		$file_name     = File::makeSafe($file_name);
		$file_type     = $_FILES[$file_field]['type'];
		$file_tmp_name = $_FILES[$file_field]['tmp_name'];
		$file_size     = $_FILES[$file_field]['size'];
		$file_error    = $_FILES[$file_field]['error'];

		// Set error flag, if any error occurs set this to 1
		$error_flag = 0;

		// Check for max media size allowed for upload
		$max_size_exceed = $media->check_max_size($file_size);

		if ($max_size_exceed)
		{
			$errorList[] = Text::_('COM_SOCIALADS_ERR_MSG_FILE_BIG') . " " . $sa_params->get('media_size') . "KB<br>";

			$error_flag = 1;
		}

		if (!$error_flag)
		{
			// Detect file type & detect media group type image/video/flash
			$media_type_group = $media->check_media_type_group($file_type);

			if (!$media_type_group['allowed'])
			{
				$errorList[] = Text::_('COM_SOCIALADS_ERR_MSG_FILE_ALLOW');
				$error_flag  = 1;
			}



			$adzone = '';

			if ($data->get('ad_zone_id', '', 'INT') != '')
			{
				$adzone = $data->get('ad_zone_id', '', 'INT');
			}
			else
			{
				$adzone = $data->get('adzone', '', 'STRING');
			}

			$adzone_media_dimnesions = $media->get_adzone_media_dimensions($adzone);

			// If($media_type_group['media_type_group']!="video" )// skip check size for video
			if ($media_type_group['media_type_group'] == "image")
			{
				$media_dimensions_allow_to_upload = $media->check_media_dimensions_allow_to_upload($adzone_media_dimnesions, $file_tmp_name);
				if (!$media_dimensions_allow_to_upload['allowToUpolad'])
				{
					$message = $media_dimensions_allow_to_upload['message'];
					$errorList[] = Text::_('COM_SOCIALADS_ERR_MSG_FILE_SIZE_NOT_MATCH') . " :- " . $message . '<br>';

					$error_flag = 1;
				}
			}





			if (!$error_flag)
			{
				$media_extension = $media->get_media_extension($file_name);

				// Determine if resizing is needed for images
				// Get max height and width for selected zone

				// @TODO get video frame height n width
				$max_zone_width  = $adzone_media_dimnesions->img_width;
				$max_zone_height = $adzone_media_dimnesions->img_height;

				// If($media_type_group['media_type_group']!="video" )// skip resizing for video
				if ($media_type_group['media_type_group'] == "image")
				{
					// Get uploaded image dimensions
					$media_size_info = $media->check_media_resizing_needed($adzone_media_dimnesions, $file_tmp_name);
					$resizing        = 0;

					if ($media_size_info['resize'])
					{
						$resizing = 1;
					}

					switch ($resizing)
					{
						case 0:
							$new_media_width  = $media_size_info['width_img'];
							$new_media_height = $media_size_info['height_img'];

							// @TODO not sure abt this
							$top_offset = 0;

							// @TODO not sure abt this
							$blank_height = $new_media_height;
							break;
						case 1:
							$new_dimensions   = $media->get_new_dimensions($max_zone_width, $max_zone_height, 'auto');
							$new_media_width  = $new_dimensions['new_calculated_width'];
							$new_media_height = $new_dimensions['new_calculated_height'];
							$top_offset       = $new_dimensions['top_offset'];
							$blank_height     = $new_dimensions['blank_height'];
							break;
					}
				}
				else
				{
					// As we skipped resizing for video , we will use zone dimensions
					$new_media_width  = $adzone_media_dimnesions->img_width;
					$new_media_height = $adzone_media_dimnesions->img_height;

					// @TODO not sure abt this
					$top_offset   = 0;
					$blank_height = $new_media_height;
				}

				// $fullPath = JUri::root() . 'images/socialads/';
				$fullPath = COM_SA_CONST_MEDIA_ROOTURL . '/';

				// $relPath = 'images/socialads/';
				$relPath = COM_SA_CONST_MEDIA_ROOTPATH_RELATIVE_NO_SLASH . '/';

				$colorR = 255;
				$colorG = 255;
				$colorB = 255;

				$file_name_without_extension = $media->get_media_file_name_without_extension($file_name);
				$needResize = isset($media_dimensions_allow_to_upload['needResize']) ? $media_dimensions_allow_to_upload['needResize'] : 1;

				$upload_image = $media->uploadImage(
									$file_field,
									$max_zone_width,
									$fullPath,
									$relPath,
									$colorR,
									$colorG,
									$colorB,
									$new_media_width,
									$new_media_height,
									$blank_height,
									$top_offset,
									$media_extension,
									$file_name_without_extension,
									$max_zone_height,
									$needResize
								);
			}
		}

		if ($error_flag)
		{
			echo '<img src="' . Uri::root(true) . '/media/com_sa/images/error.gif"
			width="16" height="16px" border="0" class="errorPresent" style="margin-bottom: -3px;" /> Error(s) Found: ';

			foreach ($errorList as $value)
			{
				echo $value . ', ';
			}

			jexit();
		}

		if ($data->get('upimgcopy') !== null)
		{
			$img = str_replace(Uri::base(), '', $data->get('upimgcopy'));
		}

		if (is_array($upload_image))
		{
			foreach ($upload_image as $key => $value)
			{
				if ($value == "-ERROR-")
				{
					unset($upload_image[$key]);
				}
			}

			$document = array_values($upload_image);

			for ($x = 0; $x < sizeof($document); $x++)
			{
				$errorList[] = $document[$x];
			}

			$imgUploaded = false;
		}
		else
		{
			$imgUploaded = true;
		}

		if ($imgUploaded)
		{
			$upload_image = (string) $upload_image;

			switch ($media->media_type_group)
			{
				case "flash":
					// FLOWPLAYER METHOD
					echo '<div><input type="hidden" name="upimg" value="' . htmlspecialchars($upload_image, ENT_COMPAT, 'UTF-8') . '"></div>';
					echo '<script src="' . Uri::root(true) . '/media/com_sa/vendors/flowplayer/flowplayer-3.2.13.min.js" type="text/javascript"></script>';
					echo '<div class="vid_ad_preview"
					href="' . htmlspecialchars($upload_image, ENT_COMPAT, 'UTF-8') . '"
					style="background:url(' . Uri::root(true) .
					'/media/com_sa/images/black.png);width:' . htmlspecialchars($new_media_width, ENT_COMPAT, 'UTF-8') . 'px;height:'
					. htmlspecialchars($new_media_height, ENT_COMPAT, 'UTF-8') . 'px;
					">
					</div>';

					// Configure flowplayer	//disable all controls //hide play button
					echo '
					<script type="text/javascript">
						flowplayer("div.vid_ad_preview",
						{
							src:"' . Uri::root(true) . '/media/com_sa/vendors/flowplayer/flowplayer-3.2.18.swf",
							wmode:"opaque"
						},
						{
							canvas:
							{
								backgroundColor:"#000000",
								width:' . htmlspecialchars($new_media_width, ENT_COMPAT, 'UTF-8') . ',
								height:' . htmlspecialchars($new_media_height, ENT_COMPAT, 'UTF-8') . '
							},

							//default settings for the play button
							play: {
								opacity: 0.0,
							 	label: null,
							 	replayLabel: null,
							 	fadeSpeed: 500,
							 	rotateSpeed: 50
							},

							plugins:{
								controls: null
							}
						});
					</script>';

					jexit();

					break;

				case "video":
					// FLOWPLAYER METHOD
					echo '<div><input type="hidden" name="upimg" value="' . htmlspecialchars($upload_image, ENT_COMPAT, 'UTF-8') . '"></div>';

					// Configure flowplayer	//disable all controls //hide play button
					echo '<video style="display:block;" controls controlsList="nofullscreen nodownload noremoteplayback noPlayButton noPlauseButton" width="' .$new_media_width .'px" height="'. $new_media_height .'px" autoplay muted loop="true">
						<source src="' . $upload_image .'" type="video/mp4">
						' . Text::_('COM_SOCIALADS_AD_BROWSER_NOT_SUPPORT_VIDEO_MSG') . '
					</video>';

					jexit();
					break;

				case "html5":
						// FLOWPLAYER METHOD
					echo '<div><input type="hidden" name="upimg" value="' . htmlspecialchars($upload_image, ENT_COMPAT, 'UTF-8') . '"></div>';

					echo '<iframe id="html_object" type="text/html" data-js-attr="sa-html5-iframe" src="' . $upload_image . '/index.html"
					frameborder="0" width="' .$new_media_width .'px" height="'. $new_media_height .'px"></iframe>';

					jexit();
				break;
			}

			if ($max_zone_width == $media_size_info['width_img'] && $max_zone_height == $media_size_info['height_img'])
			{
				echo '<img src="' . htmlspecialchars($upload_image, ENT_COMPAT, 'UTF-8') . '" border="0" />';
			}

			elseif ($max_zone_width != $media_size_info['width_img'] || $max_zone_height != $media_size_info['height_img'])
			{

				echo '<img src="' . htmlspecialchars($upload_image, ENT_COMPAT, 'UTF-8') . '" border="0" />';


				if ($media_type_group['media_type_group'] == "image" && $media_dimensions_allow_to_upload['needResize'])
				{
					if (!$adzone_media_dimnesions->use_image_ratio)
					{
						$msg = Text::sprintf('COM_SOCIALADS_IMAGE_RESIZE',
								$max_zone_width,
								$max_zone_height,
								$media_size_info['width_img'],
								$media_size_info['height_img']
							);
					
					}
					else 
					{
						$msg = Text::sprintf('COM_SOCIALADS_IMAGE_RESIZE_RATIO',
								$adzone_media_dimnesions->img_width_ratio,
								$adzone_media_dimnesions->img_height_ratio,
								$media_size_info['width_img'],
								$media_size_info['height_img']
							);
					}
					echo '<script>alert("' . $msg . '")</script>';
				}
			}

			echo '<div><input type="hidden" name="upimg" value="' . htmlspecialchars($upload_image, ENT_COMPAT, 'UTF-8') . '"></div>';
			jexit();
		}
		else
		{
			echo '<img src="' . Uri::root() . 'media/com_sa/images/error.gif" width="16" height="16px"
					border="0" style="margin-bottom: -3px;" /> Error(s) Found: ';

			foreach ($errorList as $value)
			{
				echo htmlspecialchars($value, ENT_COMPAT, 'UTF-8') . ', ';
			}

			jexit();
		}
	}

	/**
	 * This function to save step1 -> design ad data
	 *
	 * @param   array    $formData       Post data
	 * @param   integer  $adminApproval  admin approval for ad
	 *
	 * @return  boolean
	 *
	 * @since  1.6
	 */
	public function saveDesignAd($formData, $adminApproval, $isCopyAd = false)
	{
		$db        = Factory::getDbo();
		$sa_params = ComponentHelper::getParams('com_socialads');
		$session   = Factory::getSession();
		$input     = Factory::getApplication()->input;
		$date      = Factory::getDate('now');
		$user = null;

		// Do Back End Stuff
		if (Factory::getApplication()->isClient("administrator") || $isCopyAd)
		{
			$ad_creator_id = $formData['ad_creator_id'];
			$user = Factory::getUser($ad_creator_id);
			$userid        = $user->id;
		}
		else
		{
			$user = Factory::getUser();
			$userid = $user->id;
		}

		if (!$userid)
		{
			$userid = 0;
			throw new Exception(Text::_('JGLOBAL_YOU_MUST_LOGIN_FIRST'));
		}

		$preSentApproveMailStatus = isset($formData['sa_sentApproveMail']) ? $formData['sa_sentApproveMail'] : 0;

		// To avoid repetative mail while editing confirm ads
		$return['sa_sentApproveMail'] = 0;
		$designAd_data                = new stdClass;
		$designAd_data->ad_id         = '';
		$ad_id                        = $session->get('ad_id');

		if ($ad_id)
		{
			$designAd_data->ad_id = $ad_id;
		}

		$designAd_data->created_by  = $userid;
		$designAd_data->ad_noexpiry = '';

		$designAd_data->ad_created_date  = $date->toSQL();
		$designAd_data->ad_modified_date = $date->toSQL();

		if(isset($formData['display_ad_on']) && $formData['display_ad_on'] && count($formData['display_ad_on']))
		{
			$designAd_data->display_ad_on = implode(',', $formData['display_ad_on']);
		}

		// Get ad data which in form of array in layout
		if (isset($formData['ad_zone_id'])  && $formData['ad_zone_id'])
		{
			$designAd_data->ad_zone = $formData['ad_zone_id'];
		}
		else
		{
			$designAd_data->adzone = $formData['adzone'];
		}

		$addData = array();

		// Code for affiliate ads
		$addType = $formData['adtype'];

		if ($addType == 'affiliate')
		{
			$affiliateAccess = $sa_params->get('user_groups_for_affiliate_ads');
			if ($affiliateAccess)
			{
				if (!count(array_intersect($user->groups, $affiliateAccess)))
				{
					return false;
				}
			}
			else if (!$user->authorise('core.admin'))
			{
				return false;
			}

			// @params $table_name, $where_field_name= where column name , $where_field_value = column value

			if ($ad_id)
			{
				// Delete __ad_contextual_target data
				$this->deleteData('ad_contextual_target', 'ad_id', $ad_id);

				// Delete __ad_geo_target data
				$this->deleteData('ad_geo_target', 'ad_id', $ad_id);

				// Delete __ad_fields data
				$this->deleteData('ad_fields', 'adfield_ad_id', $ad_id);

				$query = $db->getQuery(true);
				$query = "Delete pi.*, o.* from #__ad_payment_info AS pi
				INNER JOIN #__ad_orders AS o ON pi.order_id=o.id
				where pi.ad_id = " . $ad_id . " AND o.status = 'P' ";
				$db->setQuery($query);

				if (!$db->execute())
				{
					$this->setError($this->_db->getErrorMsg());

					return 0;
				}
			}

			$designAd_data->ad_affiliate = 1;
			$designAd_data->layout       = "layout6";
			$designAd_data->ad_title     = trim($formData['addata']['ad_title']);

			// $rawhtml                  = $input->get('addata', '', 'post', 'Array', 'RAW');
			$designAd_data->ad_body      = stripslashes(trim($formData['addata']['ad_body']));
		}
		else
		{
			$addData['ad_url2']   = str_replace('http://', '', $formData['addata']['ad_url2']);
			$addData['ad_url2']   = str_replace('https://', '', $formData['addata']['ad_url2']);

			$designAd_data->layout   = $formData['layout'];

			if ($isCopyAd)
			{
				if (isset($formData['old_image']) && !empty($formData['old_image']))
				{
					$newImageName            = $this->mediaCopy($formData['old_image'], $formData['old_ad_id']);
					$designAd_data->ad_image = $newImageName;
				}
			}
			else 
			{
				$designAd_data->ad_image = str_replace(Uri::root(), '', $formData['upimg']);
			}

			$designAd_data->ad_url1  = $formData['addata']['ad_url1'];
			$designAd_data->ad_url2  = trim($formData['addata']['ad_url2']);
			$designAd_data->ad_title = trim($formData['addata']['ad_title']);
			$designAd_data->ad_body  = trim($formData['addata']['ad_body']);
			$designAd_data->params   = isset($formData['params']) ? $formData['params'] : '';
		}

		$geo_target     = isset ($formData['geo_targett']) ? $formData['geo_targett'] : 0;
		$social_target  = isset ($formData['social_targett']) ? $formData['social_targett'] : 0;
		$context_target = isset ($formData['context_targett']) ? $formData['context_targett'] : 0;

		// IF any one targeting set then ad is not a guest ad
		$designAd_data->ad_guest = $formData['ad_guest'];

		$designAd_data->state = 1;

		// Do not update publish state on ad edit
		if (!$ad_id)
		{
			// Ad not will not be published by default published for campaign
			if ($sa_params->get('payment_mode') == 'wallet_mode')
			{
				$designAd_data->state = 0;
			}
		}

		$designAd_data->ad_noexpiry = $formData['unlimited_ad'];

		// Code for guest
		if (!empty($adminApproval))
		{
			$designAd_data->ad_approved = 0;
		}
		else
		{
			if ($sa_params->get('approval_status') == 0)
			{
				$designAd_data->ad_approved = 1;
			}
			else
			{
				$designAd_data->ad_approved = 0;
			}
		}

		$altadbutton = $formData['altadbutton'];

		if ($altadbutton == 'on')
		{
			if (!$user->authorise('core.admin'))
			{
				return false;
			}

			$designAd_data->ad_alternative = 1;
			$designAd_data->ad_approved    = 1;
			$designAd_data->state          = 1;
			$designAd_data->ad_guest       = 0;
			$designAd_data->camp_id        = 0;

			// IF aleternate ad then delete other data if exist
			if ($ad_id)
			{
				$this->deleteDataAlternateAd($ad_id);
			}
		}
		else
		{
			$designAd_data->ad_alternative = 0;
		}

		PluginHelper::importPlugin('socialads');

		if ($designAd_data->ad_id)
		{
			// Admin Approval Needed for Ad edits ?  // $adminApproval ==1 means admin creating the ad so dont send approve mail
			if (!Factory::getApplication()->isClient("administrator") && empty($adminApproval) && $sa_params->get('approval_status') == 1 && $preSentApproveMailStatus == 0)
			{
				// While updating (confirm ) ad sent ad approval email
				$createAdHelper               = new createAdHelper;
				$result                       = $createAdHelper->sendForApproval($designAd_data);
				$return['sa_sentApproveMail'] = $result['sa_sentApproveMail'];

				if (isset($result['ad_approved']))
				{
					$designAd_data->ad_approved = $result['ad_approved'];
				}
			}

			// Insert fields
			if (!$this->_db->updateObject('#__ad_data', $designAd_data, 'ad_id'))
			{
				echo $this->_db->stderr();

				return false;
			}

			Factory::getApplication()->triggerEvent('onAfterSocialAdSave', array($designAd_data, false));
		}
		else
		{
			// Insert fields
			if (!$db->insertObject('#__ad_data', $designAd_data, 'ad_id'))
			{
				echo $this->_db->stderr();

				return false;
			}
		}

		if (empty($ad_id))
		{
			$ad_id = $db->insertid();
			$session->set('ad_id', $ad_id);

			Factory::getApplication()->triggerEvent('onAfterSocialAdSave', array($designAd_data, true));
		}

		$hasUnlimitedAccess = false;
		$unlimitedAccess = $sa_params->get('user_groups_for_unlimited_ads');
		if ($unlimitedAccess)
		{
			if (!count(array_intersect($user->groups, $unlimitedAccess)))
			{
				$hasUnlimitedAccess = true;
			}
		}
		else if (!$user->authorise('core.admin'))
		{
			$hasUnlimitedAccess = true;
		}

		if ($hasUnlimitedAccess)
		{
			$payInitialFee = $this->payInitialFee($ad_id);
			if (!$payInitialFee)
			{
				$return['initialFeeWarning'] = Text::_('INITIAL_CHARGE_WARNING_MSG');
			}
		}

		return $return;
	}

	/**
	 * This function to save trgeting data
	 *
	 * @param   array  $formData  Post data
	 *
	 * @return  boolean
	 *
	 * @since  1.6
	 */
	public function saveTargetingData($formData, $isCopyAd = false)
	{
		$sa_params = ComponentHelper::getParams('com_socialads');
		$session   = Factory::getSession();
		$app       = Factory::getApplication();
		$checkTable = Factory::getDbo()->getTableList();
		$prefix = Factory::getDbo()->getPrefix();

		// Do Back End Stuff
		if ($app->isClient("administrator") || $isCopyAd)
		{
			$ad_creator_id = $formData['ad_creator_id'];
			$userid        = Factory::getUser($ad_creator_id)->id;
		}
		// Do Front End stuff
		else
		{
			$userid = Factory::getUser()->id;
		}

		if (!$userid)
		{
			$userid = 0;

			return false;
		}

		$adData        = new stdClass;
		$adData->ad_id = '';

		$ad_id       = $session->get('ad_id');
		$geotargetId = $adfield_id = $context_targetId = '';

		// Get primary key of table
		if ($ad_id)
		{
			$adData->ad_id = $ad_id;

			// Get order id
			// @params $value,$field_name,$tableName
			$targetAdId = $this->getColumnFromAnyFieldValue($adData->ad_id, 'ad_id', '#__ad_geo_target');

			if ($targetAdId)
			{
				$geotargetId = $targetAdId;
			}
		}
		else
		{
			$app = Factory::getApplication();
			$app->enqueueMessage('Session Expire', 'error');

			return false;
		}

		if ($isCopyAd)
		{
			$adData->ad_guest = 1;
			Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_socialads/tables'); //Multiple

			if (in_array($prefix .'ad_geo_target', $checkTable))
			{
				JLoader::import('components.com_socialads.tables.geotarget', JPATH_ADMINISTRATOR); // singe
				$tableDb = Factory::getDbo();
				$adTable = Table::getInstance('GeoTarget', 'SocialadsTable', array('dbo', $tableDb));
				$adTable->load(array('ad_id' => $formData['old_ad_id']));

				if ($adTable->ad_id)
				{
					$adTable->id    = '';
					$adTable->ad_id = $ad_id;
					$adTable->store();

					$adData->ad_guest = 0;
				}
			}
		}
		else
		{
			// Added for geo targeting
			$geo_type       = isset ($formData['geo_type']) ? $formData['geo_type'] : '';

			$geo_fields     = isset ($formData['geo']) ? $formData['geo'] : '';

			$geo_target     = isset ($formData['geo_targett']) ? $formData['geo_targett'] : 0;

			$social_target  = isset ($formData['social_targett']) ? $formData['social_targett'] : 0;

			$context_target = isset ($formData['context_targett']) ? $formData['context_targett'] : 0;

			// Set geoflag
			$geoflag = 0;

			if (isset($geo_fields) && !empty($geo_fields))
			{
				foreach ($geo_fields as $geo)
				{
					if (!empty($geo))
					{
						$geoflag = 1;

						break;
					}
				}
			}

			// IF any one targeting set then ad is not a guest ad
			$adData->ad_guest = ($geo_target || $social_target || $context_target) ? 0 : 1;

			// Start of geo
			if ($sa_params->get('geo_targeting') && $geo_target == 1)
			{
				// Form field name="geo[country]" name="geo[region]" name="geo[city]"
				$geo_adfields = $geo_fields;

				if ($geoflag)
				{
					// Name="geo_type"  everywhere || city || region
					$type               = str_replace("by", "", $geo_type);
					$fielddata          = new stdClass;
					$fielddata->id      = '';
					$fielddata->ad_id   = $ad_id;
					$fielddata->country = $geo_fields['country'];
					$fielddata->region  = '';
					$fielddata->city    = '';

					// Get tagert table id
					if ($geotargetId)
					{
						$fielddata->id = $geotargetId;
					}

					if ($type === "region")
					{
						$fielddata->region = $geo_fields['region'];
						$fielddata->city   = '';
					}
					elseif ($type === "city")
					{
						$fielddata->region = '';
						$fielddata->city   = $geo_fields['city'];
					}

					if ($fielddata->id)
					{
						if (!$this->_db->updateObject('#__ad_geo_target', $fielddata, 'id'))
						{
							echo $this->_db->stderr();

							return false;
						}
					}
					elseif (!$this->_db->insertObject('#__ad_geo_target', $fielddata, 'id'))
					{
						echo $this->_db->stderr();

						return false;
					}
				}
			}
			else
			{
				$query = "DELETE FROM #__ad_geo_target WHERE ad_id=" . $ad_id;
				$this->_db->setQuery($query);

				if (!$this->_db->execute())
				{
					echo $this->_db->stderr();

					return false;
				}
			}
		}
		/* End of geo*/

		/*Start of context*/

		// Get primary key of table
		if (!empty($ad_id))
		{
			// Get order id
			// @params $value,$field_name,$tableName
			$id = $this->getColumnFromAnyFieldValue($ad_id, 'ad_id', '#__ad_contextual_target');

			if ($id)
			{
				$context_targetId = $id;
			}
		}
		else
		{
			$app = Factory::getApplication();
			$app->enqueueMessage('Session Expire', 'error');

			return false;
		}

		if ($isCopyAd)
		{
			if (in_array($prefix . 'ad_contextual_target', $checkTable))
			{
				JLoader::import('components.com_socialads.tables.contexttarget', JPATH_ADMINISTRATOR); // singe
				$tableDb = Factory::getDbo();
				$adTable = Table::getInstance('ContextTarget', 'SocialadsTable', array('dbo', $tableDb));
				$adTable->load(array('ad_id' => $formData['old_ad_id']));

				if ($adTable->ad_id)
				{
					$adTable->id    = '';
					$adTable->ad_id = $ad_id;
					$adTable->store();

					$adData->ad_guest = 0;
				}
			}
		}
		else
		{
		    if ($sa_params->get('contextual_targeting') && $context_target == "1")
		    {
		        $context_target_data_keywordtargeting = $formData['context_target_data']['keywordtargeting'];

		        if ($context_target_data_keywordtargeting)
		        {
		            $context_target        = new stdClass;
		            $context_target->id    = '';
		            $context_target->ad_id = $ad_id;

		            if ($context_targetId)
		            {
		                $context_target->id = $context_targetId;
		            }

		            $context_target->keywords = trim(strtolower($context_target_data_keywordtargeting));

		            if ($context_target->id)
		            {
		                if (!$this->_db->updateObject('#__ad_contextual_target', $context_target, 'id'))
		                {
		                    echo $this->_db->stderr();

		                    return false;
		                }
		            }
		            elseif (!$this->_db->insertObject('#__ad_contextual_target', $context_target, 'id'))
		            {
		                echo $this->_db->stderr();

		                return false;
		            }
		        }
		    }
		    elseif ($context_targetId)
		    {
				$query	= $this->_db->getQuery(true);
				$query->delete($this->_db->quoteName('#__ad_contextual_target'));
				$query->where($this->_db->quoteName('id') . ' = ' . $context_targetId);
		        $this->_db->setQuery($query);

		        if (!$this->_db->execute())
		        {
		            echo $this->_db->stderr();
		        }
		    }
		}

		/*START Save --Social Targeting data--
		$buildadsession->set('ad_fields',$data['mapdata']);
		$session_adfields = $buildadsession->get('ad_fields');
		$profile=$buildadsession->get('plg_fields');
		*/

		$ad_fields = isset ($formData['mapdata']) ? $formData['mapdata'] : '';

		$profile   = isset ($formData['plgdata']) ? $formData['plgdata'] : '';

		// Get social target ID
		// @params $value,$field_name,$tableName

		if ($isCopyAd)
		{
			if (in_array($prefix . 'ad_fields', $checkTable))
			{
				JLoader::import('components.com_socialads.tables.adfields', JPATH_ADMINISTRATOR); // singe
				$tableDb = Factory::getDbo();
				$adTable = Table::getInstance('AdFields', 'SocialadsTable', array('dbo', $tableDb));
				$adTable->load(array('adfield_ad_id' => $formData['old_ad_id']));

				if ($adTable->adfield_ad_id)
				{
					$adTable->adfield_id    = '';
					$adTable->adfield_ad_id = $ad_id;
					$adTable->store();

					$adData->ad_guest = 0;
				}
			}
		}
		else
		{
		    if ($ad_id)
		    {
		        $db = Factory::getDbo();

		        $app      = Factory::getApplication();
		        $dbprefix = $app->getCfg('dbprefix');

		        $tbexist_query = "SHOW TABLES LIKE '" . $dbprefix . "ad_fields'";
		        $db->setQuery($tbexist_query);
		        $isTableExist = $db->loadResult();

		        if ($isTableExist)
		        {
					$query	= $db->getQuery(true);
					$query->select($db->quoteName('adfield_id'));
					$query->from($db->quoteName('#__ad_fields'));
					$query->where($db->quoteName('adfield_ad_id') . ' = ' . $ad_id);

		            $db->setQuery($query);
		            $adfield_id = $db->loadResult();
		        }
		    }

		    if ((!empty($ad_fields) || !empty($profile)) && $social_target == "1")
		    {
		        // For saving demographic details
		        $fielddata             = new stdClass;
		        $fielddata->adfield_id = '';

		        if ($adfield_id)
		        {
		            $fielddata->adfield_id = $adfield_id;
		        }

		        $fielddata->adfield_ad_id = $ad_id;

		        $date_low  = date('Y-m-d 00:00:00', mktime(0, 0, 0, 01, 1, 1910));
		        $date_high = date('Y-m-d 00:00:00', mktime(0, 0, 0, 01, 1, 2030));
		        $grad_low  = 0;
		        $grad_high = 2030;

		        if (!empty($ad_fields))
		        {
		            foreach ($ad_fields as $mapdata)
		            {
		                foreach ($mapdata as $m => $map)
		                {
		                    if ($m)
		                    {
		                        if (strstr($m, ','))
		                        {
		                            $selcheck = explode(',', $m);
		                            $var      = isset($fielddata->{$selcheck[0]});
		                        }
		                        else
		                        {
		                            $var = isset($fielddata->$m);
		                        }

		                        if (!$var)
		                        {
		                            if (strstr($m, '|'))
		                            {
		                                $rangecheck = explode('|', $m);

		                                if ($rangecheck[2] == 0)
		                                {
		                                    if ($map)
		                                    {
		                                        if ($rangecheck[1] == 'daterange')
		                                        {
		                                            $date_low = $map;
		                                        }
		                                        elseif ($rangecheck[1] == 'numericrange')
		                                        {
		                                            $grad_low = $map;
		                                        }
		                                    }

		                                    if ($rangecheck[1] == 'daterange')
		                                    {
		                                        // 1900
		                                        $fielddata->{$rangecheck[0]} = $date_low;
		                                    }
		                                    elseif ($rangecheck[1] == 'numericrange')
		                                    {
		                                        // 0
		                                        $fielddata->{$rangecheck[0]} = $grad_low;
		                                    }
		                                }
		                                elseif ($rangecheck[2] == 1)
		                                {
		                                    if ($map)
		                                    {
		                                        if ($rangecheck[1] == 'daterange')
		                                        {
		                                            $date_high = $map;
		                                        }
		                                        elseif ($rangecheck[1] == 'numericrange')
		                                        {
		                                            $grad_high = $map;
		                                        }
		                                    }

		                                    if ($rangecheck[1] == 'daterange')
		                                    {
		                                        // 2030
		                                        $fielddata->{$rangecheck[0]} = $date_high;
		                                    }
		                                    elseif ($rangecheck[1] == 'numericrange')
		                                    {
		                                        // 2030
		                                        $fielddata->{$rangecheck[0]} = $grad_high;
		                                    }
		                                }
		                            }
		                            elseif (strstr($m, ','))
		                            {
		                                $selcheck = explode(',', $m);

		                                if ($selcheck[1] == 'select')
		                                {
		                                    if ($map)
		                                    {
		                                        $fielddata->{$selcheck[0]} = '|' . $map . '|';
		                                    }
		                                    else
		                                    {
		                                        $fielddata->{$selcheck[0]} = $map;
		                                    }
		                                }
		                            }
		                            else
		                            {
		                                $fielddata->$m = $map;
		                            }
		                        }
		                        else
		                        {
		                            if (strstr($m, ','))
		                            {
		                                $selcheck = explode(',', $m);

		                                if ($selcheck[1] == 'select')
		                                {
		                                    $fielddata->{$selcheck[0]} .= '|' . $map . '|';
		                                }
		                            }
		                            else
		                            {
		                                $fielddata->$m .= '|' . $map . '|';
		                            }
		                        }
		                    }
		                }
		            }
		        }

		        $tableColumns = SaCommonHelper::getTableColumns('ad_fields');
		        PluginHelper::importPlugin('socialadstargeting');
		        $results    = Factory::getApplication()->triggerEvent('onFrontendTargetingSave', array($profile, $tableColumns));

		        for ($i = 0; $i < count($results); $i++)
		        {
		            if ($results[$i] != "")
		            {
		                foreach ($results[$i] as $key => $value)
		                {
		                    $fielddata->$key = $value;
		                }
		            }
		        }

		        // Insert fields
		        $db       = Factory::getDbo();
		        $app      = Factory::getApplication();
		        $dbprefix = $app->getCfg('dbprefix');

		        $tbexist_query = "SHOW TABLES LIKE '" . $dbprefix . "ad_fields'";
		        $db->setQuery($tbexist_query);
		        $isTableExist = $db->loadResult();

		        if ($isTableExist)
		        {
		            if ($fielddata->adfield_id)
		            {
		                if (!$this->_db->updateObject('#__ad_fields', $fielddata, 'adfield_id'))
		                {
		                    echo $this->_db->stderr();

		                    return false;
		                }
		            }
		            else
		            {
		                if (!$this->_db->insertObject('#__ad_fields', $fielddata, 'adfield_id'))
		                {
		                    echo $this->_db->stderr();

		                    return false;
		                }
		            }
		        }
		    }
		    elseif ($adfield_id)
		    {
		        $this->deleteData('ad_fields', 'adfield_id', $adfield_id);
		    }
		}

		// Update ad data table
		if (!$this->_db->updateObject('#__ad_data', $adData, 'ad_id'))
		{
			echo $this->_db->stderr();

			return false;
		}

		PluginHelper::importPlugin('socialads');
		Factory::getApplication()->triggerEvent('onAfterSocialAdSave', array($adData, false));

		// Empty condition checkin ends

		// END Save --Social Targeting data--

		return true;
	}

	/**
	 * This function to save pricing data
	 *
	 * @param   array  $formData  Post data
	 *
	 * @return  boolean
	 *
	 * @since  1.6
	 */
	public function savePricingData($formData, $isCopyAd = false)
	{
		$response  = array();

		// INT(11) max stores 1073741824
		if (!empty($formData['totaldisplay']) && $formData['totaldisplay'] > 1073741824)
		{
			$response['status'] = false;

			return $response;
		}

		$sa_params = ComponentHelper::getParams('com_socialads');
		$db        = Factory::getDbo();
		$session   = Factory::getSession();
		$date      = Factory::getDate('now');

		$app = Factory::getApplication();

		// Do Back End Stuff
		if ($app->isClient("administrator") || $isCopyAd)
		{
			$ad_creator_id = $formData['ad_creator_id'];
			$user          = Factory::getUser($ad_creator_id);
			$userid        = $user->id;
		}
		else
		{
			$user   = Factory::getUser();
			$userid = $user->id;
		}

		if (!$userid)
		{
			$userid = 0;

			return false;
		}

		$ad_id = $session->get('ad_id');

		$ad_data                  = new stdClass;
		$ad_data->ad_id           = $ad_id;
		$ad_data->ad_startdate    = $formData['datefrom'] ? $formData['datefrom'] : null;
		$ad_data->ad_payment_type = $formData['chargeoption'];
		$ad_data->ad_noexpiry     = $formData['unlimited_ad'];

		if ($ad_data->ad_noexpiry == 1)
		{
			$unlimitedAccess = $sa_params->get('user_groups_for_unlimited_ads');
			if ($unlimitedAccess)
			{
				if (!count(array_intersect($user->groups, $unlimitedAccess)))
				{
					return false;
				}
			}
			else if (!$user->authorise('core.admin'))
			{
				return false;
			}

			$ad_data->ad_approved = 1;
			$ad_data->state       = 1;
		}

		$camp_id = '';

		if ($sa_params->get('payment_mode') == 'wallet_mode')
		{
			$camp_id     = $formData['ad_campaign'];
			$camp_name   = $formData['camp_name'];
			$camp_amount = $formData['camp_amount'];

			if (!$camp_id && !empty($camp_name) && !empty($camp_amount))
			{
				$db                = Factory::getDbo();
				$obj               = new stdClass;
				$obj->id           = '';
				$obj->created_by   = $userid;
				$obj->campaign     = $formData['camp_name'];
				$obj->daily_budget = $formData['camp_amount'];
				$obj->state        = 1;

				if ($obj->id)
				{
					if (!$db->updateObject('#__ad_campaign', $obj, 'id'))
					{
						echo $db->stderr();

						return false;
					}

					PluginHelper::importPlugin('socialads');
					Factory::getApplication()->triggerEvent('onAfterSocialAdCampaignSave', array($obj, false));
				}
				else
				{
					if (!$db->insertObject('#__ad_campaign', $obj, 'id'))
					{
						echo $db->stderr();

						return false;
					}

					$response['camp_id'] = $camp_id = $db->insertid();
					$obj->id    = $response['camp_id'];
					PluginHelper::importPlugin('socialads');
					Factory::getApplication()->triggerEvent('onAfterSocialAdCampaignSave', array($obj, true));
				}
			}

			$ad_data->camp_id         = $camp_id;
			$ad_data->ad_payment_type = $formData['pricing_opt'];

			if ($sa_params->get('bidding', 0) == 1)
			{
				$ad_data->bid_value = $formData['bid_value'];
			}

			if (!$formData['unlimited_ad'])
			{
				$payInitialFee = $this->payInitialFee($ad_id);
				if (!$payInitialFee)
				{
					$return['initialFeeWarning'] = Text::_('INITIAL_CHARGE_WARNING_MSG');
				}
			}
		}

		if (!$db->updateObject('#__ad_data', $ad_data, 'ad_id'))
		{
			echo $db->stderr();

			return false;
		}

		// If campaign is selected then there is no need to place order So need to skip oreder code
		// If unlimited ad then there is no need to save payment info so return from here itself
		if ($ad_data->ad_noexpiry == 1)
		{
			// Delete Price data if already exist
			$query = $db->getQuery(true);

			$query = "delete pi.*, o.* from #__ad_payment_info AS pi
			INNER JOIN #__ad_orders AS o ON pi.order_id=o.id
			where pi.ad_id = " . $ad_id . " AND o.status = 'P' ";
			$db->setQuery($query);

			/*
			Above query used as delete is not working with joins
			$query->delete('pi, o');
			$query->from('#__ad_payment_info AS pi');
			$query->join('INNER','#__ad_orders AS o ON pi.order_id=o.id');
			$query->where('pi.ad_id = ' . $ad_id);
			$query->where('o.status = "P"');
			$db->setQuery($query);
			*/

			if (!$db->execute())
			{
				$this->setError($this->_db->getErrorMsg());

				return 0;
			}

			return true;
		}

		// NO campaign option is not selected then only place order
		if ($sa_params->get('payment_mode') == 'pay_per_ad_mode')
		{
			$paymentdata     = new stdClass;
			$paymentdata->id = '';

			// Get ad id
			if ($ad_id)
			{
				// Get order id
				$query = $db->getQuery(true);
				$query->select($db->quoteName(array('pi.order_id', 'pi.id')));
				$query->from($db->quoteName('#__ad_payment_info', 'pi'));
				$query->join('LEFT', $db->quoteName('#__ad_orders', 'o') . 'ON' . $db->quoteName('o.id') . '=' . $db->quoteName('pi.order_id'));
				$query->where($db->quoteName('pi.ad_id') . ' = ' . $ad_id);
				$query->where($db->quoteName('o.status') .' = ' . $db->quote('P'));
				$db->setQuery($query);
				$order = $db->loadObject();

				if (isset($order->order_id))
				{
					$paymentdata->id = $order->order_id;
				}
			}
			else
			{
				$app = Factory::getApplication();
				$app->enqueueMessage('Session Expire', 'error');

				return false;
			}

			$paymentdata->cdate     = $date->toSQL();
			$paymentdata->processor = '';

			// Need
			$paymentdata->amount          = $formData['totalamount'];
			$paymentdata->original_amount = $formData['totalamount'];
			$paymentdata->status          = 'P';

			// Need
			$paymentdata->coupon     = '';
			$paymentdata->payee_id   = $userid;

			// Get user's IP Address
			JLoader::import('components.com_socialads.helpers.tjgeoloc', JPATH_SITE);
			$paymentdata->ip_address = TJGeoLocationHelper::getUserIP();

			// CHECK FOR COUPON
			$coupon                 = isset ($formData['sa_cop']) ? $formData['sa_cop'] : 0;
			$SocialadsPaymentHelper = new SocialadsPaymentHelper;
			$adcop                  = $SocialadsPaymentHelper->getcoupon($coupon);

			if (!empty($adcop))
			{
				if ($adcop[0]->val_type == 1)
				{
					// Discount rate
					$val = ($adcop[0]->value / 100) * $paymentdata->original_amount;
				}
				else
				{
					$val = $adcop[0]->value;
				}

				if (!empty($val))
				{
					$paymentdata->coupon = $coupon;
				}
			}
			else
			{
				$val = 0;
			}

			$discountedPrice = $paymentdata->original_amount - $val;

			if ($discountedPrice <= 0)
			{
				$discountedPrice = 0;
			}

			// TAX CALCULATION
			// @TODO - manoj - needs to check this once
			PluginHelper::importPlugin('adstax');
			$taxresults = Factory::getApplication()->triggerEvent('onAfterSocialAdAddTax', array($discountedPrice));
			$appliedTax = 0;

			if (!empty($taxresults))
			{
				foreach ($taxresults as $tax)
				{
					if (!empty($tax))
					{
						$appliedTax += $tax[1];
					}
				}
			}

			$amountAfterTax               = $discountedPrice + $appliedTax;
			$paymentdata->amount          = $amountAfterTax;
			$paymentdata->payment_info_id = isset($order->id) ? $order->id : 0;

			// If order amount is 0 due to coupon
			if ($paymentdata->amount == 0 && !empty($paymentdata->coupon))
			{
				// $paymentdata->status = 'C';
			}
			// CHECK FOR COUPON

			if(!isset($paymentdata->prefix_oid))
			{
				$paymentdata->prefix_oid = '';
			}

			if (!$paymentdata->id)
			{
				if (!$this->_db->insertObject('#__ad_orders', $paymentdata, 'id'))
				{
					echo $this->_db->stderr();

					return false;
				}

				if (empty($orderid))
				{
					$orderid = $this->_db->insertid();
				}

				$sa_params = ComponentHelper::getParams('com_socialads');
				$order_prefix = (string) $sa_params->get('order_prefix');

				// String length should not be more than 5
				$order_prefix = substr($order_prefix, 0, 5);

				// Take separator set by admin
				$separator = (string) $sa_params->get('separator');

				$res = new stdclass;

				$res->prefix_oid = $order_prefix . $separator;

				// Check if we have to add random number to order id
				$use_random_orderid = (int) $sa_params->get('random_orderid');
				$socialadPaymentHelper = new SocialadsPaymentHelper;

				if ($use_random_orderid)
				{
					$random_numer = $socialadPaymentHelper->_random(5);
					$res->prefix_oid .= $random_numer . $separator;

					// This length shud be such that it matches the column lenth of primary key
					// It is used to add pading
					$len = (23 - 5 - 2 - 5);

					// Order_id_column_field_length - prefix_length - no_of_underscores - length_of_random number
				}
				else
				{
					// This length shud be such that it matches the column lenth of primary key
					// It is used to add pading
					$len = (23 - 5 - 2);
				}

				$maxlen = 23 - strlen($res->prefix_oid) - strlen($orderid);

				$padding_count = (int) $sa_params->get('padding_count');

				// Use padding length set by admin only if it is les than allowed(calculate) length

				if ($padding_count > $maxlen)
				{
					$padding_count = $maxlen;
				}

				if (strlen((string) $orderid) <= $len)
				{
					$append = '';

					for ($z = 0;$z < $padding_count;$z++)
					{
						$append .= '0';
					}

					$append = $append . $orderid;
				}

				$res->id = $orderid;
				$res->prefix_oid = $res->prefix_oid . $append;

				if (!$db->updateObject('#__ad_orders', $res, 'id'))
				{
					// Return false;
				}
			}
			else
			{
				if (!$this->_db->updateObject('#__ad_orders', $paymentdata, 'id'))
				{
					echo $this->_db->stderr();

					return false;
				}

				$orderid = $paymentdata->id;
			}

			// Get payment info ID from order id
			$query = $db->getQuery(true);
			$query->select($db->quoteName('pi.id'));
			$query->from($db->quoteName('#__ad_payment_info', 'pi'));
			$query->where($db->quoteName('pi.order_id') . ' = ' . $orderid);
			$db->setQuery($query);
			$payment_info_id = $db->loadResult();

			$ad_chargeoption = $formData['chargeoption'];

			if ($ad_chargeoption >= 2)
			{
				$credits = $formData['totaldays'];
			}
			else
			{
				$credits = $formData['totaldisplay'];
			}

			$payment_info_data     = new stdClass;
			$payment_info_data->id = '';

			// @TODO NEED TO MOVE TO PAYMNT INFO ?
			$payment_info_data->ad_credits_qty = $credits;

			if (!$payment_info_id)
			{
				$payment_info_data->order_id = $orderid;

				// @TODO NEED TO MOVE TO PAYMNT INFO ?
				$payment_info_data->ad_id = $ad_id;
				$payment_info_data->cdate = $date->toSQL();

				if (!$this->_db->insertObject('#__ad_payment_info', $payment_info_data, 'id'))
				{
					echo $this->_db->stderr();

					return false;
				}
			}
			else
			{
				$payment_info_data->id = $payment_info_id;

				if (!$this->_db->updateObject('#__ad_payment_info', $payment_info_data, 'id'))
				{
					echo $this->_db->stderr();

					return false;
				}
			}
		}

		PluginHelper::importPlugin('socialads');
		Factory::getApplication()->triggerEvent('onAfterSocialAdSave', array($ad_data, false));
		$response['status'] = true;

		return $response;
	}

	/**
	 * Function to Get Column from any field name & value
	 *
	 * @param   integer  $value       column value
	 * @param   integer  $field_name  column name
	 * @param   string   $tableName   table name
	 * @param   string   $column      default =id
	 *
	 * @return  integer
	 *
	 * @since  3.0
	 **/
	public function getColumnFromAnyFieldValue($value, $field_name, $tableName, $column = "id")
	{
		$db    = Factory::getDbo();
		$query = "SELECT " . $column . " FROM `" . $tableName . "`
		 WHERE  `" . $field_name . "` =  " . $value;
		$db->setQuery($query);
		$id = $db->loadResult();

		return $id;
	}

	/**
	 * This function fetches billing details
	 *
	 * @param   integer  $userId  User ID
	 *
	 * @return  integer
	 *
	 * @since  3.0
	 **/
	public function getbillDetails($userId)
	{
		$db    = Factory::getDbo();
		$query	= $db->getQuery(true);
		$query->select('*');
		$query->from($db->quoteName('#__ad_users'));
		$query->where($db->quoteName('user_id') . ' = ' . $userId);
		$query->order($db->quoteName('id') . ' DESC');

		$db->setQuery($query);

		return $billDetails = $db->loadObject();
	}

	/**
	 * This function fetches order id for the Ad
	 *
	 * @param   integer  $ad_id  id of the Ad
	 *
	 * @return  integer
	 *
	 * @since  3.0
	 **/
	public function getOrderId($ad_id)
	{
		$db    = Factory::getDbo();
		$query = $db->getQuery(true);
		$query->select($db->quoteName('pi.order_id'));
		$query->from($db->quoteName('#__ad_payment_info', 'pi'));
		$query->join('LEFT', $db->quoteName('#__ad_orders', 'o') . 'ON' . $db->quoteName('pi.order_id') . '=' . $db->quoteName('o.id'));
		$query->where($db->quoteName('pi.ad_id') . ' = ' . $ad_id);
		$query->where($db->quoteName('o.status') . ' = '. $db->quote('P'));
		$query->where($db->quoteName('o.comment') . ' != '. $db->quote('AUTO_GENERATED'));
		$db->setQuery((string) $query);

		return $db->loadResult();
	}

	/**
	 * This function fetches billing address
	 *
	 * @param   array  $data  data for address
	 *
	 * @return  integer
	 *
	 * @since  3.0
	 **/
	public function billingaddr($data)
	{
		// $data = $data1->get('bill',array(),'ARRAY');
		$row             = new stdClass;
		$row->user_id    = $data['user_id'];
		$row->user_email = $data['email1'];
		$row->firstname  = $data['fnam'];
		$row->ad_id      = $data['ad_id'];

		if (isset($data['mnam']))
		{
			$row->middlename = $data['mnam'];
		}

		$row->lastname = $data['lnam'];

		if (!empty($data['vat_num']))
		{
			$row->vat_number = $data['vat_num'];
		}

		$row->country_code = $data['country'];
		$row->address      = trim($data['addr']);

		// In smoe country city,state,zip code is not present - eg HONG CONG
		$row->city       = (!empty($data['city'])) ? $data['city'] : '';
		$row->state_code = (!empty($data['state'])) ? $data['state'] : '';

		$row->zipcode  = (!empty($data['zip'])) ? $data['zip'] : '';
		$row->phone    = $data['phon'];
		$row->approved = '1';
		
		$query	= $this->_db->getQuery(true);
		$query->select($this->_db->quoteName('id'));
		$query->from($this->_db->quoteName('#__ad_users'));
		$query->where($this->_db->quoteName('ad_id') . ' = ' . $row->ad_id);
		$query->order($this->_db->quoteName('id') . ' DESC');

		$this->_db->setQuery($query);
		$bill = $this->_db->loadResult();

		if ($bill)
		{
			$row->id = $bill;

			if (!$this->_db->updateObject('#__ad_users', $row, 'id'))
			{
				echo $this->_db->stderr();

				return 0;
			}
		}
		else
		{
			if (!$this->_db->insertObject('#__ad_users', $row, 'id'))
			{
				echo $this->_db->stderr();

				return 0;
			}
		}

		return 1;
	}

	/**
	 * Function to get geo location
	 *
	 * @param   array   $geodata      Table data
	 * @param   string  $element      country or city
	 * @param   string  $element_val  value
	 *
	 * @return  array
	 *
	 * @since  3.0
	 **/
	public function getGeolocations($geodata, $element, $element_val)
	{
		$query_condi    = array();
		$query_table    = array();
		$first          = 1;
		$first_key      = key($geodata);
		$previous_field = '';
		$loca_list      = array();

		foreach ($geodata as $key => $value)
		{
			$value = trim($value);

			if ($first)
			{
				$query_table[] = '#__tj_' . $key . ' as ' . $key;
			}
			elseif ($element == $key)
			{
				$query_table[] = '#__tj_' . $key . ' as ' . $key . ' ON ' . $key . '.' . $previous_field . '_id = ' . $previous_field . '.id';
			}

			$value = str_replace("||", "','", $value);
			$value = str_replace('|', '', $value);

			if ($element == $key)
			{
				$element_table_name = $key;
				$query_condi[]      = $key . "." . $key . " LIKE '%" . trim($element_val) . "%'";

				if (trim($value))
				{
					$query_condi[] = $key . "." . $key . " NOT IN ('" . trim($value) . "')";
				}

				break;

				$previous_field = $key;
			}
			elseif (trim($value) && $first)
			{
				$query_condi[]  = $key . "." . $key . " IN ('" . trim($value) . "')";
				$previous_field = $key;
			}

			$first = 0;
		}

		$tables = (count($query_table) ? ' FROM ' . implode("\n LEFT JOIN ", $query_table) : '');

		if ($tables)
		{
			$where = (count($query_condi) ? ' WHERE ' . implode("\n AND ", $query_condi) : '');

			if ($where)
			{
				$db    = Factory::getDbo();
				$query = "SELECT distinct(" . $element_table_name . "." . $element . ") \n " . $tables . " \n " . $where;
				$db->setQuery($query);
				$loca_list = $db->loadRowList();
			}
		}

		return $loca_list;
	}

	/**
	 * Estimated reach
	 *
	 * @param   string  $target_field     Target field
	 * @param   string  $plg_targetfiels  Plugin target fields
	 *
	 * @return  boolean
	 *
	 * @since  1.6
	 */
	public function getEstimatedReach($target_field, $plg_targetfiels)
	{
		$params      = ComponentHelper::getParams('com_socialads');
		$integration = $params->get('social_integration');
		$reach       = '';

		if ($integration == 'JomSocial')
		{
			$reach = $this->getEstimatedReach_JS($target_field, $plg_targetfiels);
		}

		if ($integration == 'Community Builder')
		{
			$reach = $this->getEstimatedReach_CB($target_field, $plg_targetfiels);
		}

		return $reach;
	}

	/**
	 * Estimated reach
	 *
	 * @param   string  $target_field     Target field
	 * @param   string  $plg_targetfiels  Plugin target fields
	 *
	 * @return  boolean
	 *
	 * @since  1.6
	 */
	public function getEstimatedReach_JS($target_field, $plg_targetfiels)
	{
		$db               = Factory::getDbo();
		$exact_field      = array();
		$query_fuz        = '';
		$fuzzy_fields     = '';
		$fuzzy_data       = array();
		$fuzzy_values     = array();
		$fuz_value        = '';
		$mapping_fieldids = '';
		$exact_values     = array();
		$condition        = '';
		$est_reach        = '';

		foreach ($target_field as $key => $currentvalue)
		{
			$original_key = $key;
			$pos          = -1;
			$range_flag   = 0;

			if (strpos($key, "_low") > 0)
			{
				$range_flag = 1;
				$pos        = strpos($key, "_low");
				$key        = substr($original_key, 0, $pos);
			}

			if (strpos($key, "_high") > 0)
			{
				$range_flag = 2;
				$pos        = strpos($key, "_high");
				$key        = substr($original_key, 0, $pos);
			}

			$query_field	= $db->getQuery(true);
			$query_field->select(array('mapping_match', 'mapping_fieldtype', 'mapping_fieldid', 'mapping_fieldname'));
			$query_field->from($db->quoteName('#__ad_fields_mapping'));
			$query_field->where($db->quoteName('mapping_fieldname') . ' = ' . $db->quote($key));

			$db->setQuery($query_field);
			$mapping_values     = $db->loadObject();
			$mapping_fieldids[] = $mapping_values->mapping_fieldid;

			if ($mapping_values->mapping_match == 0)
			{
				$fuzzy_fields[] = $original_key;
				$fuzzy_data[]   = $currentvalue;
			}
			else
			{
				switch ($mapping_values->mapping_fieldtype)
				{
					case 'singleselect':
						$exact_field[] = "+" . $currentvalue . "";
						break;
					case 'multiselect':
						$currentvalue_str_multi = "'" . $currentvalue . "'";
						$currentvalue_arr_multi = explode("','", $currentvalue_str_multi);

						foreach ($currentvalue_arr_multi as $currentvalue_arr_key => $currentvalue_arr_val)
						{
							$currentvalue_arr_val = str_replace("'", "", $currentvalue_arr_val);
							$exact_field[]        = $currentvalue_arr_val;
						}

						break;
					case 'textbox':
						$exact_field[] = "" . $currentvalue . "";
						break;
					case 'date':
						$where[] = "(value = " . $db->quote($currentvalue) . ")";
						break;
					case 'daterange':
					case 'numericrange':
						if ($range_flag == 1)
						{
							$where[] = "(value >= {$db->quote($currentvalue)})";
						}

						if ($range_flag == 2)
						{
							$where[] = "(value<= {$db->quote($currentvalue)})";
						}

						break;
				}
			}
		}

		// If there is any fuzzy targeted field
		if (count($fuzzy_fields) != 0 and count($exact_field) == 0)
		{
			foreach ($fuzzy_data as $fuz_value)
			{
				$fuzzy_values[] = $fuz_value;
			}

			$fuzzy_values = implode(" ", $fuzzy_values);

			if ($fuzzy_values)
			{
				$where[] = " MATCH (value) AGAINST ( '" . $fuzzy_values . "' IN BOOLEAN MODE ) ";
			}
		}
		elseif (count($fuzzy_fields) == 0 && count($exact_field) != 0)
		{
			foreach ($exact_field as $exact_value)
			{
				$exact_values[] = $exact_value;
			}

			$exact_values = implode(" ", $exact_values);

			if ($exact_values)
			{
				$where[] = " MATCH (value) AGAINST ( '" . $exact_values . "' IN BOOLEAN MODE ) ";
			}
		}
		elseif (count($fuzzy_fields) != 0 and count($exact_field) != 0)
		{
			foreach ($fuzzy_data as $fuz_value)
			{
				$fuzzy_values[] = $fuz_value;
			}

			$fuzzy_values = implode(" ", $fuzzy_values);

			// Exact Fields
			foreach ($exact_field as $exact_value)
			{
				$exact_values[] = $exact_value;
			}

			$exact_values = implode(" ", $exact_values);

			if ($exact_values or $fuzzy_values)
			{
				$where[] = " MATCH (value) AGAINST ( '" . $exact_values . " " . $fuzzy_values . "' IN BOOLEAN MODE ) ";
			}
		}

		$plgugindata         = 0;
		$plugindata_mapadata = 0;

		PluginHelper::importPlugin('socialadstargeting');
		$plg_results  = Factory::getApplication()->triggerEvent('onAfterSocialAdGetEstimate', array( $plg_targetfiels));

		$userlist_str = '';
		$userlist_arr = array();

		foreach ($plg_results as $plgvalue)
		{
			if ($plgvalue)
			{
				$userlist_arr[] = implode("','", $plgvalue);
				$plgugindata    = 1;
			}
		}

		$userlist_str = implode("','", $userlist_arr);

		if (!$exact_values and !$fuzzy_values and !$plgugindata)
		{
			// This is made 	if No selection Of AQ=ny Fields
			$where = array();
			$query = "SELECT  COUNT(distinct(`userid`)) as reach FROM #__community_users";
		}
		elseif ((!$exact_values and !$fuzzy_values) and $plgugindata)
		{
			$query = "SELECT  COUNT(distinct(`userid`)) as reach FROM #__community_users";

			if ($userlist_str)
			{
				$where[] = " userid IN('" . $userlist_str . "')";
			}
		}
		else
		{
			$query = "SELECT  count(distinct(`user_id`)) as raech";
			$query .= " FROM #__community_fields_values " . "\n";

			if ($mapping_fieldids)
			{
				$mapping_fieldidlist = implode(",", $mapping_fieldids);

				if ($where)
				{
					$condition = "AND (field_id IN($mapping_fieldidlist))";
				}
				else
				{
					$condition = "WHERE (field_id IN($mapping_fieldidlist))";
				}
			}

			$plugindata_mapadata = 1;
		}

		$where = (count($where) ? ' WHERE ' . implode("\n AND ", $where) : '');
		$query .= "\n " . $where . $condition . "\n";

		if ($plugindata_mapadata)
		{
			$final_userlist = array();
			$query          = str_replace("count(distinct(`user_id`)) as raech", "distinct(`user_id`) as user_id", $query);
			$db->setQuery($query);
			$users_list_mapdata = $db->loadColumn();

			$query	= $db->getQuery(true);
			$query->select('distinct(userid) as reach');
			$query->from($db->quoteName('#__community_users'));
			$query->where($this->_db->quoteName('userid') . ' IN (' . implode(',', $this->_db->quote($userlist_arr)) . ')');
		
			$db->setQuery($query);
			$users_list_plgdata = $db->loadColumn();

			if ($users_list_plgdata and $users_list_mapdata)
			{
				$final_userlist = (array_intersect($users_list_mapdata, $users_list_plgdata));
				$est_reach      = count($final_userlist);
			}
			elseif ($users_list_plgdata)
			{
				$est_reach = count($users_list_plgdata);
			}
			elseif ($users_list_mapdata)
			{
				$est_reach = count($users_list_mapdata);
			}
		}
		else
		{
			$db->setQuery($query);
			$est_reach = $db->loadResult();
		}

		if ($est_reach)
		{
			return $est_reach;
		}
		else
		{
			return 0;
		}
	}

	/**
	 * Estimated reach
	 *
	 * @param   string  $target_field     Target field
	 * @param   string  $plg_targetfiels  Plugin target fields
	 *
	 * @return  boolean
	 *
	 * @since  1.6
	 */
	public function getEstimatedReach_CB($target_field, $plg_targetfiels)
	{
		$db       = Factory::getDbo();
		$query_cb = "SHOW COLUMNS FROM #__comprofiler ";
		$db->setQuery($query_cb);
		$cb_fields = $db->loadObjectlist();

		foreach ($cb_fields as $key => $currentvalue_cb)
		{
			$cbfields_arr[] = $currentvalue_cb->Field;
		}

		foreach ($target_field as $key => $currentvalue)
		{
			$key          = str_replace('field_', '', $key);
			$original_key = $key;

			if ($original_key == 'mobile')
			{
				$original_key = 'phone';
			}

			if (in_array($original_key, $cbfields_arr))
			{
				$pos        = -1;
				$range_flag = 0;

				if (strpos($key, "_low") > 0)
				{
					$range_flag = 1;
					$pos        = strpos($key, "_low");
					$key        = substr($original_key, 0, $pos);
				}

				if (strpos($key, "_high") > 0)
				{
					$range_flag = 2;
					$pos        = strpos($key, "_high");
					$key        = substr($original_key, 0, $pos);
				}

				$query_field	= $db->getQuery(true);
				$query_field->select(array('mapping_match', 'mapping_fieldtype', 'mapping_fieldid', 'mapping_fieldname'));
				$query_field->from($db->quoteName('#__ad_fields_mapping'));
				$query_field->where($db->quoteName('mapping_fieldname') . ' = ' . $db->quote($key));

				$db->setQuery($query_field);
				$mapping_values     = $db->loadObject();
				$mapping_fieldids[] = $mapping_values->mapping_fieldid;

				if ($mapping_values->mapping_match == 0)
				{
					$fuzzy_fields[] = $key;
					$fuzzy_data[]   = $currentvalue;
				}
				else
				{
					// Switch to add where conditions for field types
					switch ($mapping_values->mapping_fieldtype)
					{
						case 'singleselect':
						case 'multiselect':
							if ($mapping_values->mapping_fieldtype == 'multiselect')
							{
								$where[] = " $original_key IN('$currentvalue')";
							}
							else
							{
								$where[] = " $original_key LIKE '" . $currentvalue . "'";
							}
							break;

						case 'textbox':
							$where[] = " $original_key LIKE '" . $currentvalue . "'";
							break;

						case 'date':
							$where[] = "($original_key = " . $db->quote($currentvalue) . ")";
							break;

						case 'daterange':
						case 'numericrange':
							if ($range_flag == 1)
							{
								$where[] = "($original_key >= {$db->quote($currentvalue)})";
							}

							if ($range_flag == 2)
							{
								$where[] = "($original_key<= {$db->quote($currentvalue)})";
							}
							break;
					}
				}
			}
		}

		if (count($fuzzy_fields))
		{
			$field_names             = implode(',', $fuzzy_fields);
			$valueswithqoutesinarray = array();

			foreach ($fuzzy_data as $fuz_value)
			{
				// TODO: Find an alternative for htmlspecialchars
				$fuzzy_values[] = "'" . htmlspecialchars($fuz_value) . "'";
			}

			$fuzzy_values = implode(' ', $fuzzy_values);
			$where[]      = "MATCH ($field_names) AGAINST ( $fuzzy_values IN BOOLEAN MODE ) ";
		}

		$query = "SELECT distinct(count(`user_id`)) as reach";
		$query .= " FROM #__comprofiler " . "\n";
		$condition = '';
		$where     = (count($where) ? ' WHERE ' . implode("\n AND ", $where) : '');
		$query .= "\n " . $where;
		$db->setQuery($query);
		$est_reach = $db->loadObject();

		if ($est_reach->reach)
		{
			return $est_reach->reach;
		}
		else
		{
			return 0;
		}
	}

	/**
	 * IF admin has selected alternate ad then delete other data
	 *
	 * @param   integer  $ad_id  Ad id
	 *
	 * @return  boolean
	 *
	 * @since  1.6
	 */
	public function deleteDataAlternateAd($ad_id)
	{
		$db = Factory::getDbo();

		// Delete __ad_contextual_target data
		$this->deleteData('ad_contextual_target', 'ad_id', $ad_id);

		// Delete __ad_geo_target data
		$this->deleteData('ad_geo_target', 'ad_id', $ad_id);

		// Delete __ad_fields data
		$this->deleteData('ad_fields', 'adfield_ad_id', $ad_id);

		/*
		Delete Price data if already exist
		$query = $db->getQuery(true);
		$query->delete('pi.*, o.*  ');
		$query->from('#__ad_payment_info AS pi');
		$query->join('INNER','#__ad_orders AS o ON pi.order_id=o.id');
		$query->where('pi.ad_id = ' . $ad_id);
		$query->where('o.status = "P"');
		*/

		$query = "Delete pi.*, o.* from #__ad_payment_info AS pi
		INNER JOIN #__ad_orders AS o ON pi.order_id=o.id where pi.ad_id = " . $ad_id . " AND o.status = 'P' ";
		$db->setQuery($query);

		if (!$db->execute())
		{
			$this->setError($this->_db->getErrorMsg());

			return 0;
		}
	}

	/**
	 * Function to delete record
	 *
	 * @param   string  $table_name         zone type
	 * @param   string  $where_field_name   where column name
	 * @param   string  $where_field_value  column value
	 *
	 * @return  void
	 *
	 * @since  1.6
	 */
	public function deleteData($table_name, $where_field_name, $where_field_value)
	{
		$db = Factory::getDbo();

		$app           = Factory::getApplication();
		$dbprefix      = $app->getCfg('dbprefix');
		$tbexist_query = "SHOW TABLES LIKE '" . $dbprefix . $table_name . "'";
		$db->setQuery($tbexist_query);
		$isTableExist = $db->loadResult();
		$paramlist    = array();

		if ($isTableExist)
		{
			$query = "DELETE FROM #__" . $table_name . "
					 WHERE " . $where_field_name . " = " . $where_field_value;
			$db->setQuery($query);
			$db->execute();
		}
	}

	/**
	 * To get user campaigns
	 *
	 * @param   INT  $userId  Ad Id
	 *
	 * @return  object
	 *
	 * @since  1.6
	 */
	public function getUserCampaigns($userId)
	{
		if (!$userId)
		{
			return false;
		}

		$db = Factory::getDbo();
		$query = $db->getQuery(true);
		$query->select('*')
			->from($db->quoteName('#__ad_campaign'))
			->where($db->quoteName('created_by') . ' = ' . (int) $userId)
			->where($db->quoteName('state') . ' = 1')
			->where('(' . $db->quoteName('end_date') . ' IS NULL OR ' . $db->quoteName('end_date') . ' >= NOW())');

		$db->setQuery($query);

		return $db->loadObjectList();
	}

	/**
	 * Get Campaign name
	 *
	 * @param   INT  $ad_id  Ad Id
	 *
	 * @since   1.0
	 *
	 * @return result
	 */
	public function getAdPreviewData($ad_id)
	{
		$db = Factory::getDbo();
		$query	= $db->getQuery(true);
		$query->select(array('ad.camp_id', 'ad.ad_payment_type', 'camp.campaign', 'ad.pay_initial_fee'));
		$query->from($db->quoteName('#__ad_data', 'ad'));
		$query->join('LEFT', $db->quoteName('#__ad_campaign', 'camp') . 'ON' . $db->quoteName('camp.id') . '=' . $db->quoteName('ad.camp_id'));
		$query->where($db->quoteName('ad.ad_id') . ' = ' . $ad_id);

		$db->setQuery($query);
		$result = $db->loadObject();

		return $result;
	}

	/**
	 * Get Campaign name
	 *
	 * @param   INT  $cid  Campaign Id
	 *
	 * @since   1.0
	 *
	 * @return result
	 */
	public function getCampaignName($cid)
	{
		$db    = Factory::getDbo();
		$query	= $db->getQuery(true);
		$query->select($db->qn('campaign'));
		$query->from($db->quoteName('#__ad_campaign'));
		$query->where($db->quoteName('id') . ' = ' . $cid);

		$db->setQuery($query);

		return $db->loadResult();
	}

	/**
	 * Retrieve details for a country
	 *
	 * @return  object  $country  Details
	 *
	 * @since   1.0
	 */
	public function getCountry()
	{
		return $this->TjGeoHelper->getCountryList();
	}

	/**
	 * Draft Ad
	 *
	 * @since  1.6
	 *
	 * @return 1.0
	 */
	public function draftAd()
	{
		$session = Factory::getSession();
		$db      = Factory::getDbo();
		$ad_id   = $session->get('ad_id');

		$obj        = new stdClass;
		$obj->ad_id = $ad_id;
		$obj->state = 0;

		if (!$db->updateObject('#__ad_data', $obj, 'ad_id'))
		{
			echo $this->_db->stderr();

			return 0;
		}

		return true;
	}

	/**
	 * Activate Ad
	 *
	 * @since  1.6
	 *
	 * @return 1.0
	 */
	public function activateAd()
	{
		$session   = Factory::getSession();
		$db        = Factory::getDbo();
		$sa_params = ComponentHelper::getParams('com_socialads');
		$ad_id     = $session->get('ad_id');

		$obj        = new stdclass;
		$obj->ad_id = $ad_id;
		$adminApproval = 0;

		require_once JPATH_ADMINISTRATOR . '/components/com_socialads/helpers/socialads.php';
		$canDo = SocialadsHelper::getActions();

		if ($canDo->get('core.edit'))
		{
			$adminApproval = 1;
		}

		$obj->state = $adminApproval;

		if (!$db->updateObject('#__ad_data', $obj, 'ad_id'))
		{
			echo $this->_db->stderr();

			return 0;
		}

		// Send admin approve mail for new ad
		if ($sa_params->get('approval_status') == 1)
		{
			$createAdHelper = new createAdHelper;
			$createAdHelper->adminAdApprovalEmail($ad_id);
		}

		return true;
	}

	/**
	 * Set flag to allow or not full Ad edit
	 *
	 * @param   int  $ad_id  Ad id
	 *
	 * @return  1/0
	 *
	 * @since 3.1
	 */
	public function allowWholeAdEdit($ad_id)
	{
		if (!$ad_id)
		{
			return;
		}

		$db        = Factory::getDbo();
		$sa_params = ComponentHelper::getParams('com_socialads');

		// If wallet mode then allow full Ad edit by default
		if ($sa_params->get('payment_mode') == "wallet_mode")
		{
			return 1;
		}

		// Create a new query object.
		$query = $db->getQuery(true);

		/* Get the Ad id if
		 * credits balance is > 0
		 * or it's unlimited ad
		 * or it's an alternative ad
		 * or if payment type is day And Ad is ongoing
		 *
		 * If this query satisfy then don't allow to edit full Ad
		 */
		$query->select($db->qn('a.ad_id'))
			->from($db->quoteName('#__ad_data', 'a'))
			->where($db->qn('a.ad_id') . ' = ' . $ad_id);

		$query->where(
				$db->quoteName('a.ad_credits_balance') . '>0
				OR a.ad_noexpiry =1
				OR a.ad_alternative = 1
				OR (a.ad_payment_type=2 AND ((a.ad_enddate <> "0000-00-00" || a.ad_enddate IS NOT NULL)
				AND a.ad_startdate <= CURDATE()
				AND a.ad_enddate > CURDATE()))'
		);

		$db->setQuery($query);
		$result = $db->loadResult();

		// If Ad payment is already done then only allow editing Ad basic details
		if (!$result)
		{
			return 1;
		}

		return 0;
	}

	/**
	 * Function to verify that Allow Ad more credit to Ad
	 *
	 * @param   integer  $ad_id  ad ID
	 *
	 * @return  integer
	 *
	 * @since  3.0
	 **/
	public function getMoreCredit($ad_id)
	{
		if (!$ad_id)
		{
			return;
		}

		$db        = Factory::getDbo();
		$sa_params = ComponentHelper::getParams('com_socialads');

		// If wallet mode then allow full Ad edit by default
		if ($sa_params->get('payment_mode') == "wallet_mode")
		{
			return 0;
		}

		// Create a new query object.
		$query = $db->getQuery(true);

		/* Get the Ad id if
		 * credits balance is > 0
		 * or it's unlimited ad
		 * or it's an alternative ad
		 * or if payment type is day And Ad is ongoing
		 * (payment type 0 - impression and 1 is clicks so add credits if the add is impression or clicks type only)
		 *
		 * If this query satisfy then don't allow to edit full Ad
		 */
		$query
			->select($db->qn('a.ad_id'))
			->from($db->quoteName('#__ad_data', 'a'))
			->where($db->qn('a.ad_id') . ' = ' . $ad_id);

		$query->where($db->quoteName('a.ad_credits_balance') . " > " . 0);
		$query->where($db->quoteName('a.ad_alternative') . " != " . 1);
		$query->where($db->quoteName('a.ad_affiliate') . " != " . 1);
		$query->where($db->quoteName('a.ad_noexpiry') . ' != ' . 1 . ' OR 
						('. $db->quoteName('a.ad_payment_type') . ' IN (2,3,4) AND 
						(('. $db->quoteName('a.ad_enddate') . ' IS NULL OR ' . $db->quoteName('a.ad_enddate') . ' <> "0000-00-00") AND
						'. $db->quoteName('a.ad_startdate') . ' <= CURDATE()))');

		$db->setQuery($query);
		$result = $db->loadResult();

		// If Ad payment is already done then only allow editing Ad basic details
		if ($result)
		{
			return 1;
		}

		return 0;
	}

	/**
	 * Function to restrict multiple entries in the consent table for an ad
	 *
	 * @param   integer  $adId         ad ID/client_id
	 *
	 * @param   integer  $adCreatorId  created by user_id
	 *
	 * @return  integer
	 *
	 * @since  3.0
	 */
	public function getTnCData($adId, $adCreatorId)
	{
		Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjprivacy/tables');
		$userPrivacyTable = Table::getInstance('tj_consent', 'TjprivacyTable', array());
		$userPrivacyData  = $userPrivacyTable->load(
			array(
					'client'    => 'com_socialads.ad',
					'client_id' => $adId,
					'user_id'   => $adCreatorId
				)
		);

		return (int) $userPrivacyData;
	}

	/**
	 * Method to publish/unpublish ad
	 *
	 * @param   Integer  &$pks   Ad ids.
	 * @param   Integer  $value  Publish/Unpublish state
	 *
	 * @return  boolean true or false
	 *
	 * @since   3.1.14
	 */
	public function publish(&$pks, $value = 1)
	{
		$app     = Factory::getApplication();
		$context = $app->input->get('option');
		$pks     = (array) $pks;
		$user    = Factory::getUser();
		$table   = $this->getTable();

		// Access checks.
		foreach ($pks as $i => $pk)
		{
			$table->reset();

			if ($table->load($pk))
			{
				$editStateAuthorise = $user->authorise('core.edit.state', 'com_socialads') == 1 ? true : false;
				$editOwnAuthorise   = $user->authorise('core.edit.own', 'com_socialads') == 1 ? true : false;

				if ($editStateAuthorise === false || ($editOwnAuthorise === false && ($table->created_by !== $user->get('id') && $app->isClient("site"))))
				{
					// Prune items that you can't change.
					unset($pks[$i]);

					return false;
				}
			}
		}

		// Check if there are items to change
		if (!count($pks))
		{
			return true;
		}

		// Attempt to change the state of the records.
		if (!$table->publish($pks, $value, $user->get('id')))
		{
			$this->setError($table->getError());

			return false;
		}

		PluginHelper::importPlugin('socialads');
		Factory::getApplication()->triggerEvent('onAfterSocialAdChangeState', array($context, $pks, $value));

		return true;
	}

	/**
	 * Method to delete ads
	 *
	 * @param   array  &$adid  The array of ad ids.
	 *
	 * @return  boolean true or false
	 *
	 * @since   2.2
	 */
	public function delete(&$adid)
	{
		$app     = Factory::getApplication();
		$context = $app->input->get('option');
		$user    = Factory::getUser();
		$config   = Factory::getConfig();
		$dbprefix = $config->get('dbprefix');

		if (!empty($adid))
		{
			foreach ($adid as $i => $adId)
			{
				$data  = $this->getData((int) $adId);
				$table = $this->getTable();
				$table->load($adId);

				// Checking here is log-in user has access to delete
				$deleteOwnAuthorise = $user->authorise('core.delete', 'com_socialads') == 1 ? true : false;

				if ($table->created_by !== $user->get('id') && $app->isClient("site"))
				{
					// Prune items that you can't change.
					unset($adid[$i]);

					return false;
				}
				elseif ($deleteOwnAuthorise === false)
				{
					// Prune items that you can't change.
					unset($adid[$i]);

					return false;
				}

				$query = $this->_db->getQuery(true);
				$query->delete($this->_db->qn('#__ad_data'));
				$query->where($this->_db->qn('ad_id'). ' = ' . $adId);
				$this->_db->setQuery($query);

				if ($this->_db->execute())
				{
					$query = $this->_db->getQuery(true);
					$query = "SHOW TABLES LIKE '" . $dbprefix . "ad_fields'";
					$this->_db->setQuery($query);
					$fields = $this->_db->loadResult();

					if ($fields)
					{
						// Delete social targeting of ad
						$query = $this->_db->getQuery(true);
						$query->delete($this->_db->qn('#__ad_fields'));
						$query->where($this->_db->qn('adfield_ad_id') . ' = ' . $adId);
						$this->_db->setQuery($query);
						
						if (!$this->_db->execute())
						{
							$this->setError($this->_db->getErrorMsg());

							return false;
						}
					}

					// Delete contextual targeting of ad
					$query = $this->_db->getQuery(true);
					$query = "SHOW TABLES LIKE '" . $dbprefix . "ad_contextual_target'";
					$this->_db->setQuery($query);
					$fields = $this->_db->loadResult();

					if ($fields)
					{
						$query = $this->_db->getQuery(true);
						$query->delete($this->_db->qn('#__ad_contextual_target'));
						$query->where($this->_db->qn('ad_id') . ' = ' . $adId);
						$this->_db->setQuery($query);
						
						if (!$this->_db->execute())
						{
							$this->setError($this->_db->getErrorMsg());

							return false;
						}
					}

					// Delete geo targeting of ad
					$query = $this->_db->getQuery(true);
					$query = "SHOW TABLES LIKE '" . $dbprefix . "ad_geo_target'";
					$this->_db->setQuery($query);
					$fields = $this->_db->loadResult();

					if ($fields)
					{
						$query = $this->_db->getQuery(true);
						$query->delete($this->_db->qn('#__ad_geo_target'));
						$query->where($this->_db->qn('ad_id') . ' = ' . $adId);
						$this->_db->setQuery($query);
						
						if (!$this->_db->execute())
						{
							$this->setError($this->_db->getErrorMsg());

							return false;
						}
					}

					// Delete stats of ad
					$query = $this->_db->getQuery(true);
					$query = "SHOW TABLES LIKE '" . $dbprefix . "ad_stats'";
					$this->_db->setQuery($query);
					$fields = $this->_db->loadResult();

					if ($fields)
					{
						$query = $this->_db->getQuery(true);
						$query->delete($this->_db->qn('#__ad_stats'));
						$query->where($this->_db->qn('ad_id') . ' = ' . $adId);
						$this->_db->setQuery($query);
						
						if (!$this->_db->execute())
						{
							$this->setError($this->_db->getErrorMsg());

							return false;
						}
					}

					// Delete ignores of ads
					$query = $this->_db->getQuery(true);
					$query = "SHOW TABLES LIKE '" . $dbprefix . "ad_ignore'";
					$this->_db->setQuery($query);
					$fields = $this->_db->loadResult();

					if ($fields)
					{
						$query = $this->_db->getQuery(true);
						$query->delete($this->_db->qn('#__ad_ignore'));
						$query->where($this->_db->qn('adid') . ' = ' . $adId);
						$this->_db->setQuery($query);
						
						if (!$this->_db->execute())
						{
							$this->setError($this->_db->getErrorMsg());

							return false;
						}
					}

					// Delete payments of ad
					$query = $this->_db->getQuery(true);
					$query = "SHOW TABLES LIKE '" . $dbprefix . "ad_payment_info'";
					$this->_db->setQuery($query);
					$fields = $this->_db->loadResult();

					if ($fields)
					{
						$query = $this->_db->getQuery(true);
						$query->select($this->_db->qn('order_id'));
						$query->from($this->_db->qn('#__ad_payment_info'));
						$query->where($this->_db->qn('ad_id') . ' = ' . $adId);
						$this->_db->setQuery($query);
						$adOrders = $this->_db->loadColumn();

						// Delete orders of ad
						$query = $this->_db->getQuery(true);
						$query = "SHOW TABLES LIKE '" . $dbprefix . "ad_orders'";
						$this->_db->setQuery($query);
						$fieldOrders = $this->_db->loadResult();

						if ($fieldOrders && count($adOrders))
						{
							$query = $this->_db->getQuery(true);
							$query->delete($this->_db->qn('#__ad_orders'));
							$query->where($this->_db->quoteName('id') . ' IN (' . implode(',', $this->_db->quote($adOrders)) . ')');
							$this->_db->setQuery($query);
							
							if (!$this->_db->execute())
							{
								$this->setError($this->_db->getErrorMsg());

								return false;
							}
						}

						$query = $this->_db->getQuery(true);
						$query->delete($this->_db->qn('#__ad_payment_info'));
						$query->where($this->_db->qn('ad_id') . ' = ' . $adId);
						$this->_db->setQuery($query);
						
						if (!$this->_db->execute())
						{
							$this->setError($this->_db->getErrorMsg());

							return false;
						}
					}

					// Delete archive stats of ad
					$query = $this->_db->getQuery(true);
					$query = "SHOW TABLES LIKE '" . $dbprefix . "ad_archive_stats'";
					$this->_db->setQuery($query);
					$fields = $this->_db->loadResult();

					if ($fields)
					{
						$query = $this->_db->getQuery(true);
						$query->delete($this->_db->qn('#__ad_archive_stats'));
						$query->where($this->_db->qn('ad_id') . ' = ' . $adId);
						$this->_db->setQuery($query);
					
						if (!$this->_db->execute())
						{
							$this->setError($this->_db->getErrorMsg());

							return false;
						}
					}

					// Delete users data against an ad
					$query = $this->_db->getQuery(true);
					$query = "SHOW TABLES LIKE '" . $dbprefix . "ad_users'";
					$this->_db->setQuery($query);
					$fields = $this->_db->loadResult();

					if ($fields)
					{
						$query = $this->_db->getQuery(true);
						$query->delete($this->_db->qn('#__ad_users'));
						$query->where($this->_db->qn('ad_id') . ' = ' . $adId);
						$this->_db->setQuery($query);
					
						if (!$this->_db->execute())
						{
							$this->setError($this->_db->getErrorMsg());

							return false;
						}
					}
				}

				PluginHelper::importPlugin('socialads');
				Factory::getApplication()->triggerEvent('onAfterSocialAdDelete', array($context, $data[1]));
			}

			return true;
		}
	}

	/**
	 * Method save as copy
	 *
	 * @return  void
	 *
	 * @since   3.0
	 */
	public function mediaCopy($oldImageName, $adId)
	{
		$oldImage     = $oldImageName;
		$destination  = explode('.', $oldImageName);
		$destination[0] = $destination[0] . rand(10, 100) . '(2)' . '_copy_' . $adId;
		$destinationFile  = implode('.', $destination);
		File::copy(JPATH_SITE . '/' . $oldImage, JPATH_SITE . '/' . $destinationFile);

		return $destinationFile;
	}

	/**
	 * Pay initial fee for placing the Ad
	 *
	 * @return  void
	 *
	 * @since   3.0
	 */
	public function payInitialFee($id)
	{
	    $sa_params           = ComponentHelper::getParams('com_socialads');
	    $db                  = Factory::getDbo();
	    $initialFee          = $sa_params->get('initial_fee_for_ad_placement');
	    $needToPayInitialFee = $sa_params->get('need_to_pay_initial_fee');
		$statusUpdated = true;

	    $query = $db->getQuery(true);
	    $query->select($db->quoteName('a.created_by'))
	        ->select($db->quoteName('a.ad_title'))
	        ->select($db->quoteName('a.ad_noexpiry'))
	        ->select($db->quoteName('a.ad_alternative'))
	        ->select($db->quoteName('a.ad_affiliate'))
	        ->select($db->quoteName('a.pay_initial_fee'))
	        ->select($db->quoteName('u.name'))
	        ->select($db->quoteName('u.email'))
	        ->from($db->quoteName('#__ad_data', 'a'))
	        ->join('INNER', $db->quoteName('#__users', 'u') . ' ON ' . $db->quoteName('a.created_by') . ' = ' . $db->quoteName('u.id'))
	        ->where($db->quoteName('a.ad_id') . ' = ' . $id);

	    $db->setQuery($query);
	    $result	 = $db->loadObject();

	    if ($sa_params->get('payment_mode') == 'wallet_mode' && $needToPayInitialFee && $initialFee
			&& $result->ad_noexpiry == 0 && $result->ad_alternative == 0 && $result->ad_affiliate == 0 && $result->pay_initial_fee == 0)
	    {
	        $query = $db->getQuery(true);
	        $query->select($db->quoteName('balance'))
	            ->select($db->quoteName('user_id'))
	            ->from($db->quoteName('#__ad_wallet_transc'))
	            ->where($db->quoteName('user_id') . ' = ' . $result->created_by)
	            ->order($db->quoteName('time') . ' DESC');

	        $db->setQuery($query);
	        $walletTrasaction	 = $db->loadObject();

	        if (is_null($walletTrasaction) || $initialFee > $walletTrasaction->balance)
	        {
				$query = $db->getQuery(true);
				$query->update($db->quoteName('#__ad_data'))
					->set($db->quoteName('ad_approved') . ' = ' . 0)
					->where($db->quoteName('ad_id') . ' = ' . $id);
				$db->setQuery($query);

				$db->execute();

	            $statusUpdated = false;
	        }
			else
			{
				$date1       = microtime(true);
				$createdDate = $date1;
				$date2       = date('Y-m-d');
				$todayDate   = $date2;
				$query1      = $db->getQuery(true);
				// $query1->select($db->quoteName('id'))
				// 	->from($db->quoteName('#__ad_wallet_transc'))
				// 	->where("DATE(FROM_UNIXTIME(time)) = '" . $db->quote($todayDate))
				// 	->where($db->quoteName('type') . ' = ' . $db->quote('C'))
				// 	->where($db->quoteName('type_id') . " IS NULL");

				$query1 = "SELECT id FROM #__ad_wallet_transc WHERE DATE(FROM_UNIXTIME(time)) = '" . $todayDate . "' AND type_id IS NULL"
					. " AND type = 'C' AND user_id = " . $result->created_by . " ";
				$db->setQuery($query1);
				$check = $db->loadresult();

				if ($check)
				{
					$query3 = $db->getQuery(true);
					$query3 = "UPDATE #__ad_wallet_transc SET time ='" . $createdDate . "', spent = spent +"
							. $initialFee . ",balance = " . $walletTrasaction->balance . " - " . $initialFee . " where id=" . $check;
					$db->setQuery($query3);
					$db->execute();
				}
				else
				{
					$query4 = $db->getQuery(true);
					$query4 = "INSERT INTO #__ad_wallet_transc
							(time, user_id, spent, earn, balance, type, comment)
							VALUES ('" . $createdDate . "'," . $result->created_by . "," . $initialFee . ",'0'," . $walletTrasaction->balance . " - " .
							$initialFee . ", 'C' ,'COM_SOCIALADS_INITIAL_FEE_MESSAGE')";
					$db->setQuery($query4);
					$db->execute();
				}

				$query = $db->getQuery(true);
				$query->update($db->quoteName('#__ad_data'))
					->set($db->quoteName('pay_initial_fee') . ' = ' . 1)
					->set($db->quoteName('ad_approved') . ' = ' . 1)
					->set($db->quoteName('pay_initial_fee_amout') . ' = ' . $initialFee)
					->where($db->quoteName('ad_id') . ' = ' . $id);
				$db->setQuery($query);

				$db->execute();
			}
		}
		
		return $statusUpdated;
	}
}
