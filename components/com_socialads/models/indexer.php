<?php
/**
 * @version    SVN:<SVN_ID>
 * @package    Com_Socialads
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2015 TechJoomla. All rights reserved
 * @license    GNU General Public License version 2, or later
 */

// No direct access.
defined('_JEXEC') or die(';)');

use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;


require_once JPATH_COMPONENT . '/helper.php';

/**
 * Indexer Model
 *
 * @since  1.0
 */
class SocialadsModelIndexer extends BaseDatabaseModel
{
	/**
	 * Returns a reference to the a Table object, always creating it.
	 *
	 * @param   integer  $indexlimitstart  index limit start
	 * @param   integer  $indexlimit       index limit
	 * @param   integer  $pkey             Primary key
	 *
	 * @return  void
	 *
	 * @since  1.6
	 */
	public function makeIndexing($indexlimitstart, $indexlimit, $pkey)
	{
		$indexdate = date('Y-m-d H:i:s');
		$child_table1 = '#__finder_links_terms';
		$master_table = '#__finder_terms';
		$link_table = '#__finder_links';
		$db  = Factory::getDBO();

		if ($indexlimitstart == 0)
		{
			$query	= $db->getQuery(true);
			$query->delete($db->quoteName('#__ad_contextual_terms'));

			$db->setQuery($query);
			$db->execute();
		}

		$flag = 0;
		$query = "SELECT link_id FROM $link_table    ORDER BY  link_id 	LIMIT $indexlimitstart,$indexlimit ";

		$db->setQuery($query);
		$links = $db->loadobjectlist();

		if (empty($links))
		{
			$flag = 1;
		}

		foreach ($links as $link)
		{
			$linkid = $link->link_id;

			for ($i = 0;$i <= 9; $i++)
			{
				$child_table = $child_table1 . $i;

				$query = $db->getQuery(true);
				$query->select($db->qn(array('child.*', 'master.term')));
				$query->from($db->quoteName($child_table, 'child'));
				$query->join('INNER', $db->quoteName($master_table, 'master') . ' ON (' . $db->quoteName('master.term_id') . ' = ' . $db->quoteName('child.term_id') . ')');
				$query->where($db->quoteName('master.term') . ' != ' . $db->q(''));
				$query->where($db->quoteName('child.link_id') . ' = ' . $linkid);

				$db->setQuery($query);
				$terms = $db->loadobjectlist();

				foreach ($terms as $termarr)
				{
					$term = new stdClass;

					$term->indexdate = $indexdate;

					foreach ($termarr as $key => $value)
					{
						$term->$key = trim($value);

						if (!empty($term->term))
						{
							if ($db->insertObject('#__ad_contextual_terms', $term))
							{
							}
						}
					}
				}
			}
		}

		if ($flag == 1)
		{
			echo "Indexing Done successfully Data Stored in #__ad_contextual_terms table.";

			die;
		}
		else
		{
			global $mainframe;

			$newindexlimitstart = $indexlimit;
			$newindexlimit = $indexlimit + 20;
			$mainframe = Factory::getApplication();
			echo "Indexing From #__finder_links Links starts from  $indexlimitstart to $indexlimit in #__ad_contextual_terms table.";

				echo $url = Route::_('index.php?option=com_socialads&&task=indexer.makeIndexing&indexlimitstart='
					. $newindexlimitstart . '&indexlimit=' .
					$newindexlimit . '&pkey=' . $pkey, false
					);

				sleep(2);
				$mainframe->redirect($url);
		}
	}
}
