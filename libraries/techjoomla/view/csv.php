<?php
/**
 * @package     Techjoomla.Libraries
 * @subpackage  Csv
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Factory;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

jimport('techjoomla.tjcsv.csv');

/**
 * TjCsv
 *
 * @package     Techjoomla.Libraries
 * @subpackage  TjCsv
 * @since       1.0
 */
class TjExportCsv extends HtmlView
{
	/**
	 *  seperator specifies the field separator, default value is comma(,) .
	 *
	 * @var  STRING
	 */
	protected $seperator = ',';

	/**
	 *  enclosure specifies the field enclosure character, default value is " .
	 *
	 * @var  STRING
	 */
	protected $enclosure = '"';

	/**
	 *  Limit start for getData for CSV
	 *
	 * @var  INT
	 */
	protected $limitStart = 0;

	/**
	 *  Total count of data.
	 *
	 * @var  INT
	 */
	protected $recordCnt = 0;

	/**
	 * The filename of the downloaded CSV file.
	 *
	 * @var  STRING
	 */
	protected $fileName = '';

	/**
	 * The data for CSV file.
	 *
	 * @var  MIXED
	 */
	protected $data = null;

	/**
	 * The headers for CSV file.
	 *
	 * @var  MIXED
	 */
	protected $headers = null;

	/**
	 * Function get the limit start and total records count for CSV export
	 *
	 * @param   STRING  $tpl  file name if empty then default set component name view name date and rand number
	 *
	 * @return  jexit
	 *
	 * @since   1.0.0
	 */
	public function display($tpl = null)
	{
		$app = Factory::getApplication();
		$input      = $app->input;
		$returnFileName = $input->get('file_name');

		$this->fileName = $this->fileName ? File::stripExt($this->fileName) : substr($input->get('option'), 4) . "_" .
		$input->get('view') . "_" . date("Y-m-d_H-i-s", time());
		$this->fileName .= '_' . rand() . '.' . 'csv';

		// Get and set the common values.
		$this->limitStart = $input->get('limitstart', 0, 'INT');
		$model = BaseDatabaseModel::getInstance($input->get('view'), substr($input->get('option'), 4) . 'Model');
		$app->setUserState($input->get('option') . '.' . $input->get('view') . '.limitstart', $this->limitStart);
		$model->setState("list.limit", $model->getState('list.limit'));

		if (!$this->recordCnt)
		{
			$this->recordCnt = $model->getTotal();
		}

		if (!$this->data)
		{
			$this->data = $model->getItems();
		}

		$TjCsv = new TjCsv;
		$TjCsv->limitStart  = $this->limitStart;
		$TjCsv->recordCnt   = $this->recordCnt;
		$TjCsv->seperator   = $this->seperator;
		$TjCsv->enclosure   = $this->enclosure;
		$TjCsv->headers     = $this->headers;
		$TjCsv->csvFilename = $returnFileName ? $returnFileName : $this->fileName;
		$returnData = $TjCsv->CsvExport($this->data);

		echo json_encode($returnData);
		jexit();
	}

	/**
	 * Common function to download the csv file.
	 *
	 * @param   string  $file  File path
	 *
	 * @return  void|boolean On success void on failure false
	 *
	 * @since   1.1.0
	 */
	public function download($file)
	{
		$config = Factory::getConfig();

		if (empty($file))
		{
			return false;
		}

		$file = $config->get('tmp_path') . '/' . $file;

		if (fopen($file, "r"))
		{
			$fsize = filesize($file);
			$path_parts = pathinfo($file);

			header("Cache-Control: public, must-revalidate");
			header('Cache-Control: pre-check=0, post-check=0, max-age=0');
			header("Expires: 0");
			header("Content-Description: File Transfer");
			header("Content-Type: text/csv");
			header("Content-Length: " . (string) $fsize);
			header("Content-Disposition: filename=\"" . $path_parts["basename"] . "\"");
			$fd = fopen($file, "r");

			if (empty($fd))
			{
				return false;
			}

			while (!feof($fd))
			{
				$buffer = fread($fd, 2048);
				echo $buffer;
			}

			fclose($fd);
		}

		ignore_user_abort(true);
		unlink($file);
	}
}
