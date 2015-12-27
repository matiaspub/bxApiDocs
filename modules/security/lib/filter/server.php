<?php
/**
* Bitrix Security Module
* @package Bitrix
* @subpackage Security
* @copyright 2001-2013 Bitrix
* @since File available since 14.0.0
*/
namespace Bitrix\Security\Filter;

use Bitrix\Main\Type\IRequestFilter;

/**
 * Filter for Server variables, such as $_SERVER["REQUEST_URI"]
 *
 * @package Bitrix\Security\Filter
 * @since 14.0.0
 */
class Server
	extends Request
	implements IRequestFilter
{

	protected $interestingKeys = array('REQUEST_URI', 'QUERY_STRING', 'SCRIPT_URL', 'SCRIPT_URI', 'PHP_SELF');

	/**
	 * Return filtered values or null if auditors not triggered (by default)
	 *
	 * @example tests/security/filter/serverfilter.php
	 * @param array $values
	 * @param bool $isReturnChangedOnly if true - return values only if it changed by some auditors, otherwise return null
	 * @return array|null
	 */
	public function filter(array $values, $isReturnChangedOnly = true)
	{
		$this->onFilterStarted();

		foreach ($values as $key => &$val)
		{
			if (
				substr($key, 0, 5) === 'HTTP_'
				|| in_array($key, $this->interestingKeys, true)
			)
			{
				$val = $this->filterVar('server', $val, '$_SERVER["'.$key.'"]');
			}

		}
		unset($val);

		$this->onFilterFinished();

		if (!$isReturnChangedOnly || isset($this->changedContext['server']))
			return $values;
		else
			return null;
	}
}