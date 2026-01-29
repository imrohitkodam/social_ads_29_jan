<?php
/**
 * @version    SVN:<SVN_ID>
 * @package    Plg_Esprofiletargeting
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2015 TechJoomla. All rights reserved
 * @license    GNU General Public License version 2, or later
 */

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;

?>

<div class="control-group span6">
	<label class="ad-fields-lable" for="plgdataesprofile,select"><?php echo Text::_("PROFILE_TYPE");?></label>
	<div class="controls">
		<?php
		if ($vars[0] != "")
		{
			foreach ($vars[0] as $result)
			{
				$options[] = HTMLHelper::_('select.option', $result->id, $result->title, 'value', 'text');
			}
		}

		echo HTMLHelper::_('select.genericlist', $options, 'plgdata[][esprofile,select]',
				'class="sa-fields-inputbox input-medium" onchange="sa.create.calculateReach()" size="3" multiple="true"',
				'value', 'text', $vars[1]
			);

		$options = array();
		?>
	</div>
</div>
