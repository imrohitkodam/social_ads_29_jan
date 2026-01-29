<?php
/**
 * @version    SVN: <svn_id>
 * @package    TJPrivacy
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2017-2018 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\MVC\Controller\BaseController;

/**
 * Class TjprivacyController
 *
 * @since  1.0
 */
class TjprivacyController extends BaseController
{
	/**
	 * Method to display a view.
	 *
	 * @param   boolean  $cachable   If true, the view output will be cached
	 * @param   mixed    $urlparams  An array of safe url parameters and their variable types, for valid values see {@link JFilterInput::clean()}.
	 *
	 * @return   JController This object to support chaining.
	 *
	 * @since    1.5
	 */
	public function display($cachable = false, $urlparams = false)
	{
		parent::display($cachable, $urlparams);

		return $this;
	}
}
