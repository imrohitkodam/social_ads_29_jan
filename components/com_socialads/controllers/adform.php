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
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Table\Table;

use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Layout\FileLayout;

JModelLegacy::addIncludePath(JPATH_SITE . '/components/com_tjprivacy/models');
JTable::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjprivacy/tables');

use Joomla\CMS\HTML\HTMLHelper;

/**
 * Ad form controller class.
 *
 * @package     SocialAds
 * @subpackage  com_socialads
 * @since       1.0
 */
class SocialadsControllerAdform extends FormController
{
	public $view_list, $bsVersion, $sa_params;
	/**
	 * Constructor.
	 *
	 * @param   array  $config  Default parameter
	 *
	 * @see  JController
	 *
	 * @since  1.6
	 */
	public function __construct($config = array())
	{
		parent::__construct($config);
		$this->view_list = 'ads';
	}

	/**
	 * Function to get promote plugin
	 *
	 * @return  void
	 *
	 * @since  1.6
	 */
	public function getPromoterPlugins()
	{
		// Get selected user id
		$input = Factory::getApplication()->input;
		$uid = $input->get('uid', '', 'INT');
		$model = $this->getModel('adform');
		$result = array();
		$result['html'] = $model->getPromoterPlugins($uid);

		echo json_encode($result);
		jexit();
	}

	/**
	 * Function to get zones
	 *
	 * @return  void
	 *
	 * @since  1.6
	 */
	public function getZones()
	{
		$input = Factory::getApplication()->input;
		$typ   = $input->get('a_type', '', 'STRING');
		$model = $this->getModel('adform');
		echo $model->getZones($typ);
		jexit();
	}

	/**
	 * Function to get zone data
	 *
	 * @return  void
	 *
	 * @since  1.6
	 */
	public function getZonesData()
	{
		$input = Factory::getApplication()->input;
		$typ   = $input->get('zone_id', 0, 'INT');
		$model = $this->getModel('adform');
		echo $model->getZonesData($typ);
		jexit();
	}

	/**
	 * Function to change layout
	 *
	 * @return  void
	 *
	 * @since  1.6
	 */
	public function changeLayout()
	{
		$adseen = 2;
		$input = Factory::getApplication()->input;
		$layout = $input->get('layout');
		$addata = new stdClass;
		$addata->ad_title = $input->get('title', '', 'STRING');

		if ($addata->ad_title == '')
		{
			$addata->ad_title = Text::_("COM_SOCIALADS_AD_SAMPLEAD_TITLE");
		}

		$addata->ad_body = $input->get('body', '', 'STRING');

		if ($addata->ad_body == '')
		{
			$addata->ad_body = Text::_('COM_SOCIALADS_AD_SAMPLEAD_BODY');
		}

		$addata->link = '#';
		$addata->ignore = "";
		$upload_area = 'id="upload_area"';
		$plugin = $layout;
		$addata->ad_adtype_selected = $input->get('a_type');
		$addata->adzone = $input->get('a_zone');
		$addata->ad_image = '';
		$adHtmlTyped = '';

		// If it's 'text ad' don't set image
		if ($addata->ad_adtype_selected == 'text')
		{
			$adHtmlTyped .= $addata->ad_body;
		}
		else
		{
			$addata->ad_image = $input->get('img', '', 'STRING');
			$addata->ad_image = str_replace(Uri::root(), '', $addata->ad_image);

			if ($addata->ad_image == '')
			{
				$addata->ad_image = 'media/com_sa/images/no_img_default.jpg';
			}
		}

		$adHtmlTyped = SaAdEngineHelper::getInstance()->getAdHtmlByMedia(
		$upload_area, $addata->ad_image, $addata->ad_body, $addata->link,
		$layout, $addata->adzone, $track = 0
		);

		$layout = JPATH_SITE . '/plugins/socialadslayout/' . $plugin . '/' . $plugin . '/layout.php';

		$versionObj = new SaVersion;
		$options = array("version" => $versionObj->getMediaVersion());
		HTMLHelper::stylesheet('plugins/socialadslayout/' . $plugin . '/' . $plugin . '/layout.min.css', $options);
		HTMLHelper::script('media/com_sa/js/render.min.js', $options);

		$css = Uri::root() . 'plugins/socialadslayout/' . $plugin . '/' . $plugin . '/layout.min.css';

		if (File::exists($layout))
		{
			ob_start();
			include $layout;
			$html = ob_get_contents();
			ob_end_clean();
		}
		else
		{
			$html = '<!--div for preview ad-image-->
			<div><a id="preview-title" class="preview-title-lnk" href="#">';

			if ($addata->ad_title != '')
			{
				$html .= '' . $addata->ad_title;
			}
			else
			{
				$html .= '' . Text::_("COM_SOCIALADS_AD_SAMPLEAD_TITLE");
			}

			$html .= '</a>
			</div>
			<!--div for preview ad-image-->
			<div id="upload_area" >';

			if ($addata->ad_image != '')
			{
				$html .= '<img  src="' . $addata->ad_image . '">';
			}
			else
			{
				$html .= '<img  src="' . Uri::root(true) . '/media/com_sa/images/no_img_default.jpg">';
			}

			$html .= '
			</div>
			<!--div for preview ad-bodytext-->
			<div id="preview-bodytext">';

			if ($addata->ad_body != '')
			{
				$html .= '' . $addata->ad_body;
			}
			else
			{
				$html .= '' . Text::_('COM_SOCIALADS_AD_SAMPLEAD_BODY');
			}

			$html .= '</div>';
		}

		$js = '';

		// If it's 'text ad' don't send js
		if ($addata->ad_adtype_selected != 'text')
		{
			// @TODO
			// $js should be sent out only for video ads and flash ads
			$js = '
				flowplayer("div.vid_ad_preview",
				{
					src:"' . Uri::root(true) . '/media/com_sa/vendors/flowplayer/flowplayer-3.2.18.swf",
					wmode:"opaque"
				},
				{
					canvas: {
						backgroundColor:"#000000",
						width:300,
						height:300
					},
					/*default settings for the play button*/
					play: {
						opacity: 0.0,
						label: null,
						replayLabel: null,
						fadeSpeed: 500,
						rotateSpeed: 50
					},
					plugins:{
						controls: {
							url:"' . Uri::root(true) . '/media/com_sa/vendors/flowplayer/flowplayer.controls-3.2.16.swf",
							height:25,
							timeColor: "#980118",
							all: false,
							play: true,
							scrubber: true,
							volume: true,
							time: false,
							mute: true,
							progressColor: "#FF0000",
							bufferColor: "#669900",
							volumeColor: "#FF0000"
						}
					}
				});
			';
		}

		$z = array(
		"html" => $html,
		"css" => $css,
		"js" => $js
		);
		echo json_encode($z);
		jexit();
	}

	/**
	 * This functon is used for the js promote pulgin which will get the data and pass it to the view
	 *
	 * @return  void
	 *
	 * @since  1.6
	 */
	public function getPromotePluginPreviewData()
	{
		$input = Factory::getApplication()->input;

		if ($input->get('caller', '', 'STRING') == 'raw')
		{
			$previewdata[0]->title    = $input->get('title', '', 'STRING');
			$previewdata[0]->bodytext = $input->get('body', '', 'STRING');
			$previewdata[0]->image    = $input->get('image', '', 'STRING');
			$url                      = $input->get('url', '', 'get', 'PATH', JREQUEST_ALLOWRAW);
			$previewdata[0]->url      = urldecode($url);
		}
		else
		{
			$previewdata = $this->fetchPromotePluginPreviewData();
		}

		// $filename  = JPATH_SITE . '/images/socialads/' . basename(JPATH_SITE . $previewdata[0]->image);
		$filename  = COM_SA_CONST_MEDIA_ROOTPATH . '/' . basename(JPATH_SITE . $previewdata[0]->image);
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
			$fp = fopen($filename, 'w');
			fwrite($fp, $content);
			fclose($fp);
		}

		if (!File::exists($filename))
		{
			File::copy($source1, $filename);
		}

		$previewdata[0]->imagesrc = COM_SA_CONST_MEDIA_ROOTURL . '/' . basename(JPATH_SITE . $previewdata[0]->image);
		$previewdata[0]->image = '<img width="100" src="' . COM_SA_CONST_MEDIA_ROOTURL . '/' . basename(JPATH_SITE . $previewdata[0]->image) . '" />';

		$url = explode("://", $previewdata[0]->url);
		$previewdata[0]->url1 = $url[0];
		$previewdata[0]->url2 = $url[1];

		if (!isset ($previewdata[0]->params))
		{
			$previewdata[0]->params = '';
		}

		// Data populate part
		if (!$input->get('caller'))
		{
			// Caller not set
			header('Content-type: application/json');

			// Pass array in json format
			echo json_encode(
					array(
					"url1"     => $previewdata[0]->url1,
					"url2"     => $previewdata[0]->url2,
					"title"    => (isset($previewdata[0]->title)? $previewdata[0]->title:''),
					"imagesrc" => $previewdata[0]->imagesrc,
					"image"    => $previewdata[0]->image,
					"bodytext" => (isset($previewdata[0]->bodytext) ? $previewdata[0]->bodytext : ''),
					"params"   => (isset($previewdata[0]->params) ? $previewdata[0]->params : '')
					)
			);
			jexit();
		}
		else
		{
			$buildadsession = Factory::getSession();
			$ad_data = array();
			$ad_data[0]['ad_url1']  = $previewdata[0]->url1;
			$ad_data[1]['ad_url2']  = $previewdata[0]->url2;
			$ad_data[2]['ad_title'] = $previewdata[0]->title;
			$ad_data[3]['ad_body']  = $previewdata[0]->bodytext;
			$ad_data[4]['ad_params'] = $previewdata[0]->params;

			$buildadsession->set('ad_data', $ad_data);
			$buildadsession->set('ad_image', $previewdata[0]->imagesrc);
			$link = Route::_('index.php?option=com_socialads&view=buildad&Itemid=' . $Itemid . '&frm=directad', false);
			$this->setRedirect($link);
		}

		// Data populate part
	}

	/**
	 * Function to fetch promote data via the plugin trigger
	 *
	 * @return  Array
	 *
	 * @since  1.6
	 */
	public function fetchPromotePluginPreviewData()
	{
		// Data fetch part
		$input = Factory::getApplication()->input;
		$plgnameidstr = $input->get('id', '', 'STRING');
		$plgnameid = explode('|', $plgnameidstr);

		// Trigger the Promot Plg Methods to get the preview data
		PluginHelper::importPlugin('socialadspromote', $plgnameid[0]);
		$previewdata = Factory::getApplication()->triggerEvent('onSocialAdPromoteData', array($plgnameid[1]));
		$previewdata = $previewdata[0];

		// Data fetch part
		return $previewdata;
	}

	/**
	 * Save an ad details
	 *
	 * @return  Array
	 *
	 * @since  1.6
	 */
	public function saveMedia()
	{
		$model = $this->getModel('adform');

		if ($_REQUEST['filename'] != null)
		{
			$model->mediaUpload();
		}
	}

	/**
	 * Function to save ad data
	 *
	 * @return  Boolean
	 *
	 * @since  1.6
	 */
	public function autoSave()
	{
		$mainframe     = Factory::getApplication();
		$isAdmin       = 0;
		$adminApproval = 0;

		if ($mainframe->isClient("administrator"))
		{
			$isAdmin = 1;
		}

		$tmplData = new stdClass;
		$tmplData->sa_params = $this->sa_params = ComponentHelper::getParams('com_socialads');

		// Throw new Exception("Error message");
		$mainframe = Factory::getApplication();
		$input     = Factory::getApplication()->input;
		$session   = Factory::getSession();
		$formData  = $input->getArray(
			array (
			'addata'  => array (
				'ad_url1'  => 'string',
				'ad_url2'  => 'url',
				'ad_title' => 'string',
			),
			'ad_creator_id'    => 'integer',
			'adtype'           => 'string',
			'adzone'           => 'integer',
			'ad_zone_id'       => 'integer',
			'addatapluginlist' => 'string',
			'max_tit'          => 'integer',
			'max_body'         => 'integer',
			'char_text'        => 'integer',
			'pric_imp'         => 'float',
			'pric_click'       => 'float',
			'pric_day'         => 'float',
			'params'           => 'string',
			'upimg'            => 'url',
			'upimgcopy'        => 'url',
			'add_more_credit'  => 'integer',
			'layout'           => 'cmd',
			'ad_layout_nm'     => 'cmd',
			'geo'              => '',
			'geo_type'         => 'string',
			'geo_targett'      => 'integer',
			'social_targett'   => 'integer',
			'context_targett'  => 'integer',
			'context_target_data' => array (
				'keywordtargeting' => 'string',
			),
			'ad_campaign'    => 'string',
			'camp_name'      => 'string',
			'camp_amount'    => 'float',
			'pricing_opt'    => 'integer',
			'unlimited_ad'   => 'integer',
			'chargeoption'   => 'integer',
			'datefrom'       => 'date',
			'totaldays'      => 'integer',
			'ad_totaldays'   => 'integer',
			'totaldisplay'   => 'integer',
			'totalamount'    => 'float',
			'jpoints'        => 'integer',
			'h_currency'     => 'integer',
			'gateway'        => 'string',
			'h_rate'         => 'integer',
			'sa_accpt_terms' => 'integer',
			'bill'  => array (
				'fnam'    => 'string',
				'lnam'    => 'string',
				'email1'  => 'string',
				'phon'    => 'integer',
				'addr'    => 'string',
				'zip'     => 'string',
				'country' => 'string',
				'state'   => 'string',
				'city'    => 'string',
			),
			'altadbutton' => 'string',
			'datefrom'    => 'date',
			'option'      => 'string',
			'task'        => 'cmd',
			'sa_cop'      => 'string'
			)
		);

		$formData['mapdata'] = $input->get('mapdata', '', 'array');
		$formData['plgdata'] = $input->get('plgdata', '', 'array');
		$formData['display_ad_on'] = $input->get('display_ad_on', '', 'array');

		if ($formData['adtype'] === 'affiliate')
		{
			$formDatafilter = $input->getArray(
				array (
					'addata' => array (
						'ad_body' => 'raw',
					)
				)
			);
		}
		else
		{
			$formDatafilter = $input->getArray(
				array (
					'addata' => array (
						'ad_body' => 'string',
					)
				)
			);
		}

		$formData                       = array_merge_recursive($formData, $formDatafilter);
		$sa_params                      = ComponentHelper::getParams('com_socialads');
		$model                          = $this->getModel('adform');
		$stepId                         = $input->get('stepId', '', 'STRING');
		$returndata                     = array();
		$returndata['stepId']           = $stepId;
		$returndata['payAndReviewHtml'] = '';
		$returndata['adPreviewHtml']    = '';
		$returndata['billingDetail']    = '';
		$Itemid                         = SaCommonHelper::getSocialadsItemid('managead');
		$returndata['Itemid']           = $Itemid;

		if ($isAdmin == 1)
		{
			$adminApproval = 1;
		}

		$currentBSViews = $this->sa_params->get('bootstrap_version', "bs3");
		$this->bsVersion = $currentBSViews;

		// Save step-1 : design ad data
		if ($stepId == 'ad-design')
		{
			$formData['sa_sentApproveMail'] = $input->get('sa_sentApproveMail', 0);
			$geo_target     = isset ($formData['geo_targett']) ? $formData['geo_targett'] : 0;
			$social_target  = isset ($formData['social_targett']) ? $formData['social_targett'] : 0;
			$context_target = isset ($formData['context_targett']) ? $formData['context_targett'] : 0;

			// IF any one targeting set then ad is not a guest ad
			if ($geo_target || $social_target || $context_target)
			{
				$formData['ad_guest'] = 0;
			}
			else
			{
				$formData['ad_guest'] = 1;
			}

			$response = $model->saveDesignAd($formData, $adminApproval);

			if ($response === false)
			{
				return false;
			}

			if (isset($response['initialFeeWarning']))
			{
				$returndata['initialFeeWarning'] = $response['initialFeeWarning'];
			}

			// TODO @MAYANK
			$tncCheck = isset ($formData['sa_accpt_terms']) ? $formData['sa_accpt_terms'] : 0;

			if ($tncCheck)
			{
				$userPrivacyData              = array();
				$ad_id                        = $session->get('ad_id');
				$ad_creator_id                = $formData['ad_creator_id'];
				$checkTnCExistence            = $model->getTnCData($ad_id, $ad_creator_id);

				if (!$checkTnCExistence)
				{
					$userPrivacyData['client']    = 'com_socialads.ad';
					$userPrivacyData['client_id'] = $ad_id;
					$userPrivacyData['user_id']   = $formData['ad_creator_id'];
					$userPrivacyData['purpose']   = Text::_('COM_JGIVE_USER_PRIVACY_TERMS_PURPOSE_FOR_CAMPAIGN');
					$userPrivacyData['accepted']  = $tncCheck;
					$userPrivacyData['date']      = Factory::getDate('now')->toSQL();
				}

				BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_tjprivacy/models', 'tjprivacy');
				$tjprivacyModelObj  = BaseDatabaseModel::getInstance('tjprivacy', 'TjprivacyModel');
				$tjprivacyModelObj->save($userPrivacyData);
			}
		}

		// Save step-2 : targeting ad data
		if ($stepId == 'ad-targeting')
		{
			$response = $model->saveTargetingData($formData);

			if ($response === false)
			{
				return false;
			}
		}

		// Save ad pricing data
		// Pay per ad mode
		if ($stepId == 'ad-pricing')
		{
			$response = $model->savePricingData($formData);

			if (isset($response['camp_id']))
			{
				$returndata['camp_id'] = $response['camp_id'];
			}

			if (isset($response['initialFeeWarning']))
			{
				$returndata['initialFeeWarning'] = $response['initialFeeWarning'];
			}

			if (!empty($response['status']) && $response['status'] === false)
			{
				return false;
			}

			require_once JPATH_SITE . '/components/com_tjfields/helpers/geo.php';
			$tjGeoHelper = TjGeoHelper::getInstance('TjGeoHelper');
			$tmplData->country       = (array) $tjGeoHelper->getCountryList('com_socialads');
			$tmplData->ad_creator_id = $ad_creator_id   = $formData['ad_creator_id'];
			$tmplData->userbill      = $model->getbillDetails($ad_creator_id);

			if ($ad_creator_id)
			{
				// To call compulsory bootstrap 2 layout in the backed
				if ($isAdmin == 1)
				{
					$saLayout = new FileLayout((JVERSION < '4.0.0' ? 'bs2' : 'bs5') . '.ad.ad_billing');
				}
				else
				{
					$saLayout = new FileLayout('ad.' . $this->bsVersion . '.ad_billing');
				}

				$billingDetailHtml = $saLayout->render($tmplData);

				$returndata['billingDetail'] = $billingDetailHtml;
			}
		}

		// If 0 means billing details are not saved
		if (($sa_params->get('payment_mode') == 'pay_per_ad_mode' && $stepId == 'ad-pricing') || $stepId == 'ad-billing')
		{
			// Set data for JLayouts in tmplData
			$ad_id             = $tmplData->ad_id         = $session->get('ad_id');
			$order_id          = $tmplData->order_id      = $model->getOrderId($ad_id);
			$billdata          = $tmplData->billdata      = $formData['bill'];
			$billdata['ad_id'] = $ad_id;
			$billdata['user_id'] = $tmplData->ad_creator_id = $input->get('ad_creator_id', 0);
			$tmplData->displayAdsInfo = $this->getAdsInfoOnPayment($formData);

			// Save billing detail
			if (!empty($billdata))
			{
				$model->billingaddr($billdata);
			}

			// To call compulsory bootstrap 2 layout in the backed
			if ($isAdmin == 1)
			{
				$saLayout = new FileLayout((JVERSION < '4.0.0' ? 'bs2' : 'bs5') . '.ad.ad_adsummary');
			}
			else
			{
				$saLayout = new FileLayout('ad.' . $this->bsVersion . '.ad_adsummary');
			}

			$payAndReviewHtml               = $saLayout->render($tmplData);
			$returndata['payAndReviewHtml'] = $payAndReviewHtml;
		}

		// If campaign mode is selected then get ad preview html
		if ($stepId == 'ad-pricing' && $sa_params->get('payment_mode') == 'wallet_mode')
		{
			$ad_id         = $session->get('ad_id');
			$AdPreviewData = $model->getAdPreviewData($ad_id);
			$ad_id         = $tmplData->ad_id         = $session->get('ad_id');
			$AdPreviewData = $tmplData->AdPreviewData = $model->getAdPreviewData($ad_id);
			$tmplData->displayAdsInfo = $this->getAdsInfoOnPayment($formData, $AdPreviewData);

			// To call compulsory bootstrap 2 layout in the backed
			if ($isAdmin == 1)
			{
				$saLayout = new FileLayout((JVERSION < '4.0.0' ? 'bs2' : 'bs5') . '.ad.ad_showadscamp');
			}
			else
			{
				$saLayout = new FileLayout('ad.' . $this->bsVersion . '.ad_showadscamp');
			}

			$html          = $saLayout->render($tmplData);
			$returndata['adPreviewHtml'] = $html;
		}

		echo json_encode($returndata);
		jexit();
	}

	/**
	 * find the geo locations according the geo db
	 *
	 * @return  void
	 *
	 * @since  1.6
	 */
	public function findGeolocations()
	{
		$input = Factory::getApplication()->input;

		// $post=$input->post;
		// $input->get

		$geodata     = $_POST['geo'];
		$element     = $input->get('element');
		$element_val = $input->get('request_term');
		$model       = $this->getModel('adform');
		$response    = $model->getGeolocations($geodata, $element, $element_val);
		$data = array();

		if ($response)
		{
			foreach ($response as $row)
			{
				$json = array();

				// Id of the location
				// $json['value'] = $row['1'];

				// Name of the location
				$data[] = $row['0'];

				// $data[] = $json;
			}
		}

		echo json_encode($data);
		jexit();
	}

	/**
	 * Function to clean up the records
	 *
	 * @return  void
	 *
	 * @since  1.6
	 */
	public function cleanup()
	{
		// Clear ad ID session
		$session = Factory::getSession();
		$session->clear('ad_id');

		$msg = Text::_('COM_SOCIALADS_UPDATED_BILL_INFO');
		$mainframe = Factory::getApplication();
		$isAdmin = 0;
		$adminApproval = 0;

		if ($mainframe->isClient("administrator"))
		{
			$isAdmin = 1;
		}

		if ($isAdmin == 1)
		{
			$this->setRedirect(Uri::base() . 'index.php?option=com_socialads&view=forms', $msg);
		}
		else
		{
			$Itemid      = SaCommonHelper::getSocialadsItemid('ads');
			$link = Route::_('index.php?option=com_socialads&view=ads&Itemid=' . $Itemid, false);
			$this->setRedirect($link, $msg);
		}
	}

	/**
	 * Calculate Estimated No of Reach for each ad
	 *
	 * @return  void
	 *
	 * @since  1.6
	 */
	public function calculatereach()
	{
		$plgdata = array();
		$aa = array();
		$aa = json_encode($_POST['mapdata']);

		if (isset($_POST['plgdata']))
		{
			$plgdata = json_encode($_POST['plgdata']);
		}

		$plg_target_field = array();
		$target_field = array();
		$plgmapdata_array = array();

		if (empty($aa) and empty($plgdata))
		{
			jexit();
		}

		if (!empty($aa))
		{
			$mapdata_array = json_decode($aa);

			if (empty($mapdata_array))
			{
				jexit();
			}

			$target_field = $this->calculatereach_parseArray($mapdata_array);
		}

		if (!empty($plgdata))
		{
			$plgmapdata_array = json_decode($plgdata);
			$plg_target_field = $this->calculatereach_parseArray($plgmapdata_array);
		}

		$reach = 0;

		// $adRetriever=new adRetriever();
		// $reach = $adRetriever->getEstimatedReach($target_field,$plg_target_field);

		$model = $this->getModel('adform');
		$reach = $model->getEstimatedReach($target_field, $plg_target_field);

		header('Content-type: application/json');
		echo json_encode(array("reach" => $reach));
		jexit();
	}

	/**
	 * Calculate reach parse array
	 *
	 * @param   array  $mapdata_array  Map data array
	 *
	 * @return  void
	 *
	 * @since  1.6
	 */
	public function calculatereach_parseArray($mapdata_array)
	{
		$target_field = array();

		foreach ($mapdata_array as $mapdata_obj)
		{
			foreach ($mapdata_obj as $mapdata)
			{
				if ($mapdata != '')
				{
					$mapdata_arr = $this->parseObjectToArray($mapdata_obj);

					foreach ($mapdata_arr as $key => $value)
					{
						$target_key_arr = explode(',', $key);
						$target_key_arr1 = explode('|', $target_key_arr[0]);
						$target_key = $target_key_arr1[0];

						if (array_key_exists($target_key, $target_field))
						{
							$target_field[$target_key] = $target_field[$target_key] . "','" . $value;
						}
						else
						{
							$target_field[$target_key] = $value;
						}
					}
				}
			}
		}

		return $target_field;
	}

	/**
	 * Parse object array
	 *
	 * @param   array  $object  object of array
	 *
	 * @return  void
	 *
	 * @since  1.6
	 */
	public function parseObjectToArray($object)
	{
		$array = array();

		if (is_object($object))
		{
			$array = get_object_vars($object);
		}

		return $array;
	}

	/**
	 * Method to get user campaigns
	 *
	 * @return  void
	 *
	 * @since   3.1
	 */
	public function getUserCampaigns()
	{
		$input     = Factory::getApplication()->input;
		$userId    = $input->get('userid', 0, 'INT');
		$model     = $this->getModel('adform');
		$campaigns = $model->getUserCampaigns($userId);

		header('Content-type: application/json');
		echo json_encode($campaigns);
		jexit();
	}

	/**
	 * Method to save ad
	 *
	 * @return  void
	 *
	 * @since   3.1
	 */
	public function draftAd()
	{
		$app = Factory::getApplication();

		if ($app->isClient("administrator"))
		{
			$model = $this->getModel('form');

			$redirectUrl = 'index.php?option=com_socialads&view=forms';
		}
		else
		{
			$model = $this->getModel('adform');

			$Itemid      = SaCommonHelper::getSocialadsItemid('ads');
			$redirectUrl = Route::_('index.php?option=com_socialads&view=ads&Itemid=' . $Itemid, false);
		}

		$model->draftAd();

		$msg = Text::_('COM_SOCIALADS_AD_SAVED_DRAFT');
		$app->enqueueMessage($msg, 'success');
		$app->redirect($redirectUrl);
	}

	/**
	 * Method to activate ad
	 *
	 * @return  void
	 *
	 * @since   3.1
	 */
	public function activateAd()
	{
		$app = Factory::getApplication();
		$sa_params = ComponentHelper::getParams('com_socialads');
		$user = Factory::getUser();
		$adminApproval = 0;

		require_once JPATH_ADMINISTRATOR . '/components/com_socialads/helpers/socialads.php';
		$canDo = SocialadsHelper::getActions();

		/**
		 *  if (isset($user->groups['8']) || isset($user->groups['7']) || isset($user->groups['Super Users']) ||
		 * isset($user->groups['Administrator']) || $user->usertype == "Super Users" || isset($user->groups['Super Users']) ||
		 * isset($user->groups['Administrator']) || $user->usertype == "Super Administrator" || $user->usertype == "Administrator" )
		 */
		if ($canDo->get('core.edit'))
		{
			$adminApproval = 1;
		}

		if ($sa_params->get('approval_status') && $adminApproval == 0)
		{
			$msg = Text::_('COM_SOCIALADS_AD_CREATED') . ' ' . Text::_('COM_SOCIALADS_ADMIN_APPROVAL_NOTICE');
		}
		else
		{
			$msg = Text::_('COM_SOCIALADS_AD_CREATED');
		}

		if ($app->isClient("administrator"))
		{
			$model = $this->getModel('form');

			$redirectUrl = 'index.php?option=com_socialads&view=forms';
		}
		else
		{
			$model = $this->getModel('adform');

			// $socialadshelper = new socialadshelper();
			// $Itemid = $socialadshelper->getSocialadsItemid('managead');
			$Itemid = SaCommonHelper::getSocialadsItemid('ads');

			// $this->setRedirect( 'index.php?option=com_socialads&view=managead&Itemid='.$Itemid, $msg);

			$redirectUrl = Route::_('index.php?option=com_socialads&view=ads&Itemid=' . $Itemid, false);
		}

		$model->activateAd();
		$app->enqueueMessage($msg, 'success');
		$app->redirect($redirectUrl);
	}

	/**
	 * get the ads information on payment tab
	 *
	 * @return  void
	 *
	 * @since  1.6
	 */
	private function getAdsInfoOnPayment($formData, $adPreviewData = null)
	{
		$displayAdsInfo = [];
		$geo         = [];
		$cost         = 0;
		$context_targett  = $formData['context_targett'];
		$social_targett  = $formData['social_targett'];
		$displayAdsInfo['context_targett'] = $context_targett;
		$displayAdsInfo['social_targett'] = $social_targett;
		$displayAdsInfo['ad_type'] = $formData['adtype'];
		$displayAdsInfo['ad_title'] = $formData['addata']['ad_title'];
		$displayAdsInfo['ad_url'] = $formData['addata']['ad_url2'];

		if ($formData['geo_targett'] == 1)
		{
			$country = str_replace("||", ", ", $formData['geo']['country']);
			$country = str_replace("|", "", $country);

			if ($formData['geo_type'] == 'byregion')
			{
				$region = str_replace("||", ", ", $formData['geo']['region']);
				$region = str_replace("|", "", $region);
			}
			else
			{
				$region = '';
			}

			if ($formData['geo_type'] == 'bycity')
			{
				$city = str_replace("||", ", ", $formData['geo']['city']);
				$city = str_replace("|", "", $city);
			}
			else
			{
				$city = '';
			}

			$geo = [
				'country' => $country,
				'region' => $region,
				'city' => $city
			];
		}

		if ($context_targett)
		{
			$keywords = $formData['context_target_data']['keywordtargeting'];
			$displayAdsInfo['keywords'] = $keywords;
		}

		if ($social_targett)
		{
			$displayAdsInfo['mapdata'] = $formData['mapdata'];
		}

		if ($adPreviewData && $adPreviewData->ad_payment_type == 0)
		{
			$cost       = $formData['pric_imp'];
		}
		elseif($adPreviewData && $adPreviewData->ad_payment_type == 1)
		{
			$cost       = $formData['pric_click'];
		}
		elseif($adPreviewData && $adPreviewData->ad_payment_type == 3)
		{
			$cost = 0;
		}

		$cost = SaCommonHelper::getFormattedPrice($cost);
		$displayAdsInfo['cost'] = $cost;
		$displayAdsInfo['geo'] = $geo;

		return $displayAdsInfo;
	}

	/**
	 * Function to get selected user group has permission to create unlimited Ad
	 *
	 * @return  Boolean
	 *
	 * @since  1.6
	 */
	public function getUserType()
	{
		$mainframe     = Factory::getApplication();
		$input     = Factory::getApplication()->input;
		$userid = $input->get('user');
		$user = Factory::getUser($userid);
		$returndata['isSuperAdmin'] = 0;
		$sa_params = ComponentHelper::getParams('com_socialads');

		if ($user->authorise('core.admin'))
		{
			$returndata['isSuperAdmin'] = 1;
		}

		$returndata['hasUnlimitedAdPermssion'] = 0;
		$unlimitedAccess = $sa_params->get('user_groups_for_unlimited_ads');
		if ($unlimitedAccess)
		{
			if (count(array_intersect($user->groups, $unlimitedAccess)))
			{
				$returndata['hasUnlimitedAdPermssion'] = 1;
			}
		}
		else if ($user->authorise('core.admin'))
		{
			$returndata['hasUnlimitedAdPermssion'] = 1;
		}

		$returndata['hasAffiliateAdPermssion'] = 0;
		$hasAffiliateAdPermssion = $sa_params->get('user_groups_for_affiliate_ads');
		if ($hasAffiliateAdPermssion)
		{
			if (count(array_intersect($user->groups, $hasAffiliateAdPermssion)))
			{
				$returndata['hasAffiliateAdPermssion'] = 1;
			}
		}
		else if ($user->authorise('core.admin'))
		{
			$returndata['hasAffiliateAdPermssion'] = 1;
		}

		echo json_encode($returndata);
		jexit();
	}
}

