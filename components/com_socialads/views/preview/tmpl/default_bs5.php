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

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

$doc = Factory::getDocument();
$input = Factory::getApplication()->input;
$user = Factory::getUser();

if (!$user->id)
{
	?>
	<div class="techjoomla-bootstrap">
		<div class="alert alert-block">
			<?php echo Text::_('COM_SOCIALADS_PLEASE_LOGIN'); ?>
		</div>
	</div>
	<?php

	return false;
}

$adid = $input->get('id', 0, 'INT');
echo SaAdEngineHelper::getAdHtml($adid, 1);
