<?php
/**
 * @package     Techjoomla.Libraries
 * @subpackage  Tjmoney
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

defined('_JEXEC') or die('Restricted access');

/**
 * It contains multiple functions for currencies
 *
 * @since  1.0
 */
class TjMoney
{
	private $code;

	private $subunit;

	private $decimal;

	private $symbol;

	private $numericCode;

	private $alphabeticCode;

	private $currency;

	private $currencyName;

	private $decimalCurrencyName;

	public static $allCurrencies;

	/**
	 * The constructor
	 *
	 * @param   STRING  $code  code
	 *
	 * @since  1.0
	 */
	public function __construct($code)
	{
		try
		{
			// Initialize object
			$this->setAllCurrencies();
			$this->setCode($code);
			$this->setSubunit($this);
			$this->setSymbol($this);
			$this->setAlphabeticCode($this);
			$this->setNumericCode($this);
			$this->setCurrency($this);
			$this->setDecimal($this);
			$this->setCurrencyName($this);
			$this->setDecimalCurrencyName($this);
		}
		catch (Exception $e)
		{
			echo 'Caught exception: ' . $e->getMessage(), "\n";
		}
	}

	/**
	 * Return code
	 *
	 * @return STRING
	 */
	public function getCode()
	{
		return $this->code;
	}

	/**
	 * Return subunit
	 *
	 * @return Int
	 */
	public function getSubunit()
	{
		return $this->subunit;
	}

	/**
	 * Return symbol
	 *
	 * @return STRING
	 */
	public function getSymbol()
	{
		return $this->symbol;
	}

	/**
	 * Return Numeeric code
	 *
	 * @return Int
	 */
	public function getNumericCode()
	{
		return $this->numericCode;
	}

	/**
	 * Return Aplhabetic code
	 *
	 * @return STRING
	 */
	public function getAlphabeticCode()
	{
		return $this->alphabeticCode;
	}

	/**
	 * Return currency name
	 *
	 * @return STRING
	 */
	public function getCurrency()
	{
		return $this->currency;
	}

	/**
	 * Return currency name
	 *
	 * @return STRING
	 */
	public function getCurrencyName()
	{
		return $this->currencyName;
	}

	/**
	 * Return decimal currency name
	 *
	 * @return STRING
	 */
	public function getDecimalCurrencyName()
	{
		return $this->decimalCurrencyName;
	}

	/**
	 * Return currency name
	 *
	 * @return STRING
	 */
	public function getDecimal()
	{
		return $this->decimal;
	}

	/**
	 * The function to Check is this currency exist
	 *
	 * @return  BOOLEAN
	 *
	 * @since   1.0
	 */
	public function isContains()
	{
		$isContains = isset($this->getCurrencies()[$this->getCode()]);

		if (!$isContains)
		{
			throw new Exception('Cannot find ISO currency ' . $this->getCode());
		}

		return $isContains;
	}

	/**
	 * The function to set currency code
	 *
	 * @param   string  $code  code.
	 *
	 * @return  OBJECT
	 *
	 * @since   1.0
	 */
	public function setCode($code)
	{
		if (!is_string($code))
		{
			throw new Exception('Currency code should be string');
		}

		$this->code = $code;

		return $this;
	}

	/**
	 * The function to set decimal
	 *
	 * @return  OBJECT
	 *
	 * @since   1.0
	 */
	public function setDecimal()
	{
		if ($this->isContains())
		{
			$this->decimal = $this->getCurrencies()[$this->getCode()]['decimal'];
		}

		return $this;
	}

	/**
	 * The function to set Subunit
	 *
	 * @return  OBJECT
	 *
	 * @since   1.0
	 */
	public function setSubunit()
	{
		if ($this->isContains())
		{
			$this->subunit = $this->getCurrencies()[$this->getCode()]['minorUnit'];
		}

		return $this;
	}

	/**
	 * The function to set currency name with country
	 *
	 * @return  OBJECT
	 *
	 * @since   1.0
	 */
	public function setCurrency()
	{
		if ($this->isContains())
		{
			if (empty($this->getCurrencies()[$this->getCode()]['currency']))
			{
				throw new Exception('Cannot find ISO currency Name for ' . $this->getCode());
			}

			$this->currency = $this->getCurrencies()[$this->getCode()]['currency'];

			return $this;
		}
	}

	/**
	 * The function to set only currency name in word
	 *
	 * @return  OBJECT
	 *
	 * @since   _DEPLOY_VERSION_
	 */
	public function setCurrencyName()
	{
		if ($this->isContains())
		{
			if (empty($this->getCurrencies()[$this->getCode()]['currencyName']))
			{
				throw new Exception('Cannot find ISO currency Name for ' . $this->getCode());
			}

			$this->currencyName = $this->getCurrencies()[$this->getCode()]['currencyName'];

			return $this;
		}
	}

	/**
	 * The function to set decimal currency name in word
	 *
	 * @return  OBJECT
	 *
	 * @since   _DEPLOY_VERSION_
	 */
	public function setDecimalCurrencyName()
	{
		if ($this->isContains())
		{
			if (empty($this->getCurrencies()[$this->getCode()]['decimalCurrencyName']))
			{
				throw new Exception('Cannot find ISO currency Name for ' . $this->getCode());
			}

			$this->decimalCurrencyName = $this->getCurrencies()[$this->getCode()]['decimalCurrencyName'];

			return $this;
		}
	}

	/**
	 * The function to set Symbol
	 *
	 * @return  OBJECT
	 *
	 * @since   1.0
	 */
	public function setSymbol()
	{
		if (!$this->isContains())
		{
			throw new UnknownCurrencyException('Cannot find ISO currency ' . $this->getCode());
		}

		// Curency Mvdol doesn't have symbol then will show alphabetic code BOV
		if (empty($this->getCurrencies()[$this->getCode()]['symbol']))
		{
			$this->symbol = $this->getCurrencies()[$this->getCode()]['alphabeticCode'];
		}
		else
		{
			$this->symbol = $this->getCurrencies()[$this->getCode()]['symbol'];
		}

		return $this;
	}

	/**
	 * The function to set Numeric Code
	 *
	 * @return  OBJECT
	 *
	 * @since   1.0
	 */
	public function setNumericCode()
	{
		if ($this->isContains())
		{
			if (empty($this->getCurrencies()[$this->getCode()]['numericCode']))
			{
				throw new Exception('Cannot find ISO currency Numeric Code for ' . $this->getCode());
			}

			$this->numericCode = $this->getCurrencies()[$this->getCode()]['numericCode'];

			return $this;
		}
	}

	/**
	 * The function to set Alphabetic Code
	 *
	 * @return  OBJECT
	 *
	 * @since   1.0
	 */
	public function setAlphabeticCode()
	{
		if ($this->isContains())
		{
			if (empty($this->getCurrencies()[$this->getCode()]['alphabeticCode']))
			{
				throw new Exception('Cannot find ISO currency Alphabetic Code for ' . $this->getCode());
			}

			$this->alphabeticCode = $this->getCurrencies()[$this->getCode()]['alphabeticCode'];

			return $this;
		}
	}

	/**
	 * The function to iterator
	 *
	 * @return  ARRAY
	 *
	 * @since   1.0
	 */
	public function getIterator()
	{
		return new \ArrayIterator(
			array_map(
				function ($code) {
					return new Currency($code);
				},
				array_keys($this->getCurrencies())
			)
		);
	}

	/**
	 * The function to get rounded currency
	 *
	 * @param   FLOAT  $amount  Ammount
	 *
	 * @return  FLOAT|STRING
	 *
	 * @since   1.0
	 */
	public function getRoundedValue($amount)
	{
		try
		{
			$isContain = $this->iscontains();

			if ($isContain)
			{
				$amt = round((float) $amount, $this->getSubunit());

				// Check if number is decimal then only apply
				if (strpos($amt, ".") !== false)
				{
					// In case of last digit is 0, Round display wrong format. For Example - $2.50 displays as $2.5
					$amt = number_format($amt, $this->getSubunit());
				}
			}

			return $amt;
		}
		catch (Exception $e)
		{
			echo 'Caught exception: ' . $e->getMessage(), "\n";
		}
	}

	/**
	 * The function to return formatted amount
	 *
	 * @param   FLOAT   $amount  Amount
	 * @param   STRING  $config  CurrencyDisplayFormat
	 *
	 * @return  STRING
	 *
	 * @since   1.0
	 */
	public function displayFormattedValue($amount, $config)
	{
		$formattedAmount = "";

		// Get rounded amount amount as per the currecy before formatting
		$roundedAmt = $this->getRoundedValue($amount);

		// Do decimal formatting

		if ($this->getDecimal())
		{
			$roundedAmt = str_replace(".", $this->getDecimal(), $roundedAmt);
		}

		// Decide to display currency code or symbol

		$codeOrSymbol = $this->getSymbol();

		if (strtolower($config->CurrencyCodeOrSymbol) === 'code')
		{
			$codeOrSymbol = $this->getCode();
		}

		// Check Display symbol/ Code before or after amount
		if (str_replace(' ', '', $config->CurrencyDisplayFormat) == '{AMOUNT}{CURRENCY_SYMBOL}')
		{
			$formattedAmount = $roundedAmt . " " . $codeOrSymbol;
		}
		else
		{
			$formattedAmount = $codeOrSymbol . " " . $roundedAmt;
		}

		return $formattedAmount;
	}

	/**
	 * The function to compare currencies
	 *
	 * @param   FLOAT   $currencyOne  currencyOne
	 * @param   STRING  $currencyTwo  currencyTwo
	 *
	 * @return  ARRAY
	 *
	 * @since   1.0
	 */
	private static function  compare($currencyOne, $currencyTwo)
	{
		return strcasecmp($currencyOne["currency"], $currencyTwo["currency"]);
	}

	/**
	 * The function to return currencies
	 *
	 * @return  ARRAY
	 *
	 * @since   1.0
	 */
	public static function getCurrencies()
	{
		try
		{
			$rawCurrencies = self::loadCurrencies();

			// Sort Currency Ascending order
			usort($rawCurrencies, array('TjMoney', 'compare'));

			foreach ($rawCurrencies as $key => $value)
			{
				$currencies[$value['alphabeticCode']] = $value;
			}
		}
		catch (Exception $e)
		{
			echo 'Caught exception: ' . $e->getMessage(), "\n";
		}

		return $currencies;
	}

	/**
	 * The function to set decimal currency name in word
	 *
	 * @return  OBJECT
	 *
	 * @since   _DEPLOY_VERSION_
	 */
	public function setAllCurrencies()
	{
		$file = __DIR__ . '/resources/currency.php';

		if (file_exists($file))
		{
			self::$allCurrencies = require $file;

			return;
		}

		throw new Exception('Failed to load currency ISO codes.');
	}

	/**
	 * The function to load currencies
	 *
	 * @return  ARRAY
	 *
	 * @since   1.0
	 */
	private static function loadCurrencies()
	{
		if (self::$allCurrencies)
		{
			return self::$allCurrencies;
		}
		else 
		{
			$file = __DIR__ . '/resources/currency.php';

			if (file_exists($file))
			{
				return self::$allCurrencies = require $file;
			}
		}
	}

	/**
	 * The function to convert number into word
	 *
	 * @param   Integer  $num       numeric value which will convert into the word
	 * @param   String   $currency  Currency Name
	 *
	 * @return  String
	 *
	 * @since   _DEPLOY_VERSION_
	 *
	 * Source Link : https://www.studentstutorial.com/php/number-to-words
	 */
	public function numberToWord($num, $currency)
	{
		$currencyName        = $this->getCurrencyName($currency);
		$decimalCurrencyName = $this->getDecimalCurrencyName($currency) ? $this->getDecimalCurrencyName($currency) : '';

		$ones = array(
			0 => "Zero", 1 => "One", 2 => "Two", 3 => "Three", 4 => "Four", 5 => "Five", 6 => "Six",
			7 => "Seven", 8 => "Eight", 9 => "Nine", 10 => "Ten", 11 => "Eleven", 12 => "Twelve", 13 => "Thirteen",
			14 => "Fourteen", 15 => "Fifteen", 16 => "Sixteen", 17 => "Seventeen", 18 => "Eighteen", 19 => "Nineteen"
			);

			$tens = array(
			0 => "Zero", 1 => "Ten", 2 => "Twenty", 3 => "Thirty", 4 => "Forty", 5 => "Fifty",
			6 => "Sixty", 7 => "Seventy", 8 => "Eighty", 9 => "Ninety"
			);

			/*limit t quadrillion */
			$hundreds = array(
			"Hundred", "Thousand", "Million", "Billion", "Trillion", "Quardrillion"
			);

			$num       = number_format($num, 2, ".", ",");
			$num_arr   = explode(".", $num);
			$wholenum  = $num_arr[0];
			$decnum    = $num_arr[1];
			$whole_arr = array_reverse(explode(",", $wholenum));

			krsort($whole_arr, 1);
			$rettxt = "";

		foreach ($whole_arr as $key => $i)
		{
			while (substr($i, 0, 1) == "0")
				$i = substr($i, 1, 5);

				if ($i < 20 && $i != '')
				{
					/* echo "getting:".$i; */
					$rettxt .= $ones[$i];
				}
				elseif($i < 100 && $i != '')
				{
					if (substr($i, 0, 1) != "0")
					{
						$rettxt .= $tens[substr($i, 0, 1)];
					}

					if (substr($i, 1, 1) != "0")
					{
						$rettxt .= " " . $ones[substr($i, 1, 1)];
					}
				}
				else
				{
					if (substr($i, 0, 1) != "0")
					{
						$rettxt .= $ones[substr($i, 0, 1)] . " " . $hundreds[0];
					}

					if (substr($i, 1, 1) != "0")
					{
						$rettxt .= " " . $tens[substr($i, 1, 1)];
					}

					if (substr($i, 2, 1) != "0")
					{
						$rettxt .= " " . $ones[substr($i, 2, 1)];
					}
				}

				if ($key > 0)
				{
					$rettxt .= " " . $hundreds[$key] . " ";
				}
		}

		// Add currency name here
		$rettxt .= ' ' . $currencyName;

		if ((int) $decnum > 0)
		{
			$rettxt .= " and ";

			if ($decnum < 20)
			{
				$rettxt .= $ones[$decnum];
			}
			elseif ($decnum < 90)
			{
				$rettxt .= $tens[substr($decnum, 0, 1)];
			}
			else
			{
				$rettxt .= " " . $ones[substr($decnum, 1, 1)];
			}

			$rettxt .= ' ' . $decimalCurrencyName;
		}

		/* Result will like
		 * For e.g
		 * 10.25    - Ten and Twenty Five
		 * 100      - Hundread
		 * 1000     - One Thousand
		 * 100000   - One Lakh
		 * 10000000 - One crore
		*/

		return $rettxt;
	}
}
