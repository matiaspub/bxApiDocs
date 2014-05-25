<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2013 Bitrix
 */

namespace Bitrix\Main\DB;

use Bitrix\Main\Application;

/**
 * Class description
 * @package bitrix
 * @subpackage main
 */ 
class SqlExpression
{
	/** @var string */
	protected $expression;

	/** @var array */
	protected $args = array();

	protected $pattern = '/([^\\\\]|^)(\?[#sif]?)/';

	protected $i;

	public function __construct()
	{
		$args = func_get_args();

		if (!isset($args[0]))
		{
			throw new \Exception('No pattern has been found for SqlExpression');
		}

		$this->expression = $args[0];

		for ($i = 1, $n = count($args); $i < $n; $i++)
		{
			$this->args[] = $args[$i];
		}
	}

	public function compile()
	{
		$this->i = -1;

		if (strpos($this->expression, '\\') === false)
		{
			// regular case
			return preg_replace_callback($this->pattern, array($this, 'execPlaceholders'), $this->expression);
		}
		else
		{
			// handle escaping \ and \\
			$parts = explode('\\\\', $this->expression);

			foreach ($parts as &$part)
			{
				if (!empty($part))
				{
					$part = preg_replace_callback($this->pattern, array($this, 'execPlaceholders'), $part);
				}
			}

			$parts = str_replace('\\?', '?', $parts);

			return join('\\', $parts);
		}
	}

	protected function execPlaceholders($matches)
	{
		$sqlHelper = Application::getConnection()->getSqlHelper();

		$this->i++;

		$pre = $matches[1];
		$ph = $matches[2];

		if (isset($this->args[$this->i]))
		{
			$value = $this->args[$this->i];

			if ($ph == '?' || $ph == '?s')
			{
				$value = "'" . $sqlHelper->forSql($value) . "'";
			}
			elseif ($ph == '?#')
			{
				$value = $sqlHelper->quote($value);
			}
			elseif ($ph == '?i')
			{
				$value = (int) $value;
			}
			elseif ($ph == '?f')
			{
				$value = (float) $value;
			}

			return $pre . $value;
		}

		return $matches[0];
	}
}
