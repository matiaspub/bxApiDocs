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
	 * @param array $values Array of values to be checked.
	 * @param bool $isReturnChangedOnly If true - return values only if it changed by some auditors, otherwise return null.
	 * @return array|null
	 */
	
	/**
	* <p4>Нестатический метод возвращает отфильтрованные значения. Если ни один из аудиторов не сработал, то возвращает пустое значение.
	*
	*
	* @param array $values  Значения.
	*
	* @param boolean $isReturnChangedOnly = true Если значение параметра равно <code>true</code>, то возвращает значения,
	* если они были изменены контроллерами. В противном случае
	* возвращает пустое значение.
	*
	* @return mixed 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/security/filter/server/filter.php
	* @author Bitrix
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