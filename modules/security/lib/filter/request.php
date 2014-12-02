<?php
/**
* Bitrix Security Module
* @package Bitrix
* @subpackage Security
* @copyright 2001-2013 Bitrix
* @since File available since 14.0.0
*/
namespace Bitrix\Security\Filter;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Type\IRequestFilter;

/**
 * Filter for Request variables, such as superglobals $_GET, $_POST etc
 *
 * @package Bitrix\Security\Filter
 * @since 14.0.0
 */
class Request
	implements IRequestFilter
{
	const ACTION_NONE = 'none';
	const ACTION_CLEAR = 'clear';
	const ACTION_FILTER = 'filter';

	/** @var Auditor\Base[] */
	protected $auditors = array();
	protected $changedContext = array();

	private $action = 'filter';
	private $doLog = false;
	private $changedVars = array();
	private $isAuditorsTriggered = false;
	private $filteringMap = array(
		'get' => array(
			'Name' => '$_GET',
		),
		'post' => array(
			'Name' => '$_POST',
			'SkipRegExp' => '#^File\d+_\d+$#'
		),
		'cookie' => array(
			'Name' => '$_COOKIE'
		)
	);
	private static $validActions = array(
		self::ACTION_NONE,
		self::ACTION_CLEAR,
		self::ACTION_FILTER,
	);

	public function __construct($customOptions = array())
	{
		if (isset($customOptions['action']))
		{
			$this->setAction($customOptions['action']);
		}
		else
		{
			$this->setAction(Option::get('security', 'filter_action'));
		}

		if (isset($customOptions['log']))
		{
			$this->setLog($customOptions['log']);
		}
		else
		{
			$this->setLog(Option::get('security', 'filter_log'));
		}
	}

	/**
	 * Set auditors for use in filtration
	 *
	 * @param Auditor\Base[] $auditors
	 * @return $this
	 */
	public function setAuditors(array $auditors)
	{
		$this->auditors = $auditors;
		return $this;
	}

	/**
	 * Return all changed variables, can be useful for logging
	 *
	 * @return array
	 */
	public function getChangedVars()
	{
		return $this->changedVars;
	}

	/**
	 * Return array with filtered values
	 *
	 * Simple example:
	 * <code>
	 * $ob = new Request();
	 * $ob->setAuditors([
	 *  'SQL' => new Auditor\Sql()
	 * ]);
	 * print_r(
	 *  $ob->filter([
	 *      'get' => ['safe bar'],
	 *      'post' => ['select * from foo']
	 *  ])
	 * );
	 * //output: Array ( [post] => Array ( [0] => sel ect * fr om foo ) )
	 *
	 * print_r(
	 *  $ob->filter([
	 *          'get' => ['safe bar'],
	 *          'post' => ['select * from foo']
	 *      ],
	 *      false
	 *  )
	 * );
	 * //output: Array ( [get] => Array ( [0] => safe bar ) [post] => Array ( [0] => sel ect * fr om foo ) )
	 * </code>
	 *
	 * @example tests/security/filter/requestfilter.php
	 * @param array $values array("get" => $_GET, "post" => $_POST, "files" => $_FILES, "cookie" => $_COOKIE)
	 * @param bool $isReturnChangedOnly if true - return values only if it changed by some auditors
	 * @return array
	 */
	public function filter(array $values, $isReturnChangedOnly = true)
	{
		$this->onFilterStarted();

		foreach ($values as $key => &$val)
		{
			if (!isset($this->filteringMap[$key]))
				continue;

			$val = $this->filterArray(
				$key,
				$val,
				$this->filteringMap[$key]['Name'],
				isset($this->filteringMap[$key]['SkipRegExp'])? $this->filteringMap[$key]['SkipRegExp']: ''
			);
		}
		unset($val);

		$this->onFilterFinished();

		if ($isReturnChangedOnly)
			return array_intersect_key($values, $this->changedContext);
		else
			return $values;
	}


	/**
	 * @since 14.0.3
	 * @return bool
	 */
	public function isAuditorsTriggered()
	{
		return $this->isAuditorsTriggered;
	}

	protected function onFilterStarted()
	{
		$this->changedContext = array();
		$this->changedVars = array();
		$this->isAuditorsTriggered = false;
	}

	protected function onFilterFinished()
	{

	}

	/**
	 * @param string $context
	 * @param string $value
	 * @param string $name
	 * @return string
	 */
	protected function filterVar($context, $value, $name)
	{
		if (preg_match('#^[A-Za-z0-9_.,-]*$#D', $value))
			return $value;

		self::adjustPcreBacktrackLimit($value);
		$filteredValue = \CSecurityHtmlEntity::decodeString($value);

		$isValueChanged = false;
		foreach($this->auditors as $auditName => $auditor)
		{
			if ($auditor->process($filteredValue))
			{
				$this->isAuditorsTriggered = true;

				if ($this->isLogNeeded())
				{
					$this->logVariable($value, $name, $auditName);
				}

				if ($this->isFilterAction())
				{
					$isValueChanged = true;
					$filteredValue = $auditor->getFilteredValue();
				}
				elseif ($this->isClearAction())
				{
					$isValueChanged = true;
					$filteredValue = '';
					break;
				}
			}
		}

		if ($isValueChanged)
		{
			$this->pushChangedVar($context, $value, $name);
			return $filteredValue;
		}
		else
		{
			return $value;
		}
	}

	/**
	 * @param string $context
	 * @param array $array
	 * @param string $name
	 * @param string $skipKeyPreg
	 * @return array
	 */
	protected function filterArray($context, array $array, $name, $skipKeyPreg = '')
	{
		if (!is_array($array))
			return $array;

		foreach($array as $key => $value)
		{
			if ($skipKeyPreg && preg_match($skipKeyPreg, $key))
				continue;

			$filteredKey =  $this->filterVar($context, $key, "{$name}['{$key}']");
			if ($filteredKey != $key)
			{
				unset($array[$key]);
				$key = $filteredKey;
			}

			if (is_array($value))
			{
				$array[$key] = $this->filterArray($context, $value, "{$name}['{$key}']", $skipKeyPreg);
			}
			else
			{
				$array[$key] = $this->filterVar($context, $value, "{$name}['{$key}']");
			}
		}
		return $array;
	}

	/**
	 * @param $action
	 * @return bool
	 */
	protected static function isActionValid($action)
	{
		return in_array($action, self::getValidActions());
	}

	/**
	 * @param string $value
	 * @param string $name
	 * @param string $auditorName
	 * @return bool
	 */
	protected static function logVariable($value, $name, $auditorName)
	{
		return \CSecurityEvent::getInstance()->doLog('SECURITY', 'SECURITY_FILTER_'.$auditorName, $name, $value);
	}

	/**
	 * @param $string
	 * @return bool
	 */
	protected static function adjustPcreBacktrackLimit($string)
	{
		if (!is_string($string))
			return false;

		$strlen = \CUtil::binStrlen($string) * 2;
		\CUtil::adjustPcreBacktrackLimit($strlen);
		return true;
	}

	/**
	 * @return array
	 */
	protected static function getValidActions()
	{
		return self::$validActions;
	}

	/**
	 * @param $action
	 * @return $this
	 */
	protected function setAction($action)
	{
		if (self::isActionValid($action))
		{
			$this->action = $action;
		}
		return $this;
	}

	/**
	 * @param $log
	 * @return $this
	 */
	protected function setLog($log)
	{
		$this->doLog = (is_string($log) && $log == 'Y');
		return $this;
	}

	/**
	 * @return bool
	 */
	protected function isFilterAction()
	{
		return ($this->action === self::ACTION_FILTER);
	}

	/**
	 * @return bool
	 */
	protected function isClearAction()
	{
		return ($this->action === self::ACTION_CLEAR);
	}

	/**
	 * @return bool
	 */
	protected function isLogNeeded()
	{
		return $this->doLog;
	}

	/**
	 * @param string $context
	 * @param string $value
	 * @param string $name
	 * @return $this
	 */
	protected function pushChangedVar($context, $value, $name)
	{
		$this->changedVars[$name] = $value;
		if (!isset($this->changedContext[$context]))
			$this->changedContext[$context] = 1;
		return $this;
	}
}