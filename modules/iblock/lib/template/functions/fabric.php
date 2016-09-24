<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage iblock
 */
namespace Bitrix\Iblock\Template\Functions;
/**
 * Class Fabric
 * Provides function object instance by it's name.
 * Has some builtin function such as: upper, lower, concat and limit.
 * Fires event OnTemplateGetFunctionClass. Handler of the event has to return acclass name not an instance.
 *
 * @package Bitrix\Iblock\Template\Functions
 */
class Fabric
{
	protected static $functionMap = array();
	/**
	 * Instantiates an function object by function name.
	 *
	 * @param string $functionName Name of the function in the lower case.
	 * @param mixed $data Additional data for function instance.
	 *
	 * @return FunctionBase
	 */
	public static function createInstance($functionName, $data = null) //todo rename createInstance
	{
		if ($functionName === "upper")
			return new FunctionUpper($data);
		elseif ($functionName === "lower")
			return new FunctionLower($data);
		elseif ($functionName === "translit")
			return new FunctionTranslit($data);
		elseif ($functionName === "concat")
			return new FunctionConcat($data);
		elseif ($functionName === "limit")
			return new FunctionLimit($data);
		elseif ($functionName === "contrast")
			return new FunctionContrast($data);
		elseif ($functionName === "min")
			return new FunctionMin($data);
		elseif ($functionName === "max")
			return new FunctionMax($data);
		elseif ($functionName === "distinct")
			return new FunctionDistinct($data);
		elseif (isset(self::$functionMap[$functionName]))
		{
			$functionClass = self::$functionMap[$functionName];
			return new $functionClass($data);
		}
		else
		{
			$event = new \Bitrix\Main\Event("iblock", "OnTemplateGetFunctionClass", array($functionName));
			$event->send();
			if ($event->getResults())
			{
				foreach($event->getResults() as $evenResult)
				{
					if($evenResult->getType() == \Bitrix\Main\EventResult::SUCCESS)
					{
						$functionClass = $evenResult->getParameters();
						if (is_string($functionClass) && class_exists($functionClass))
						{
							self::$functionMap[$functionName] = $functionClass;
						}
						break;
					}
				}
			}
			if (isset(self::$functionMap[$functionName]))
			{
				$functionClass = self::$functionMap[$functionName];
				return new $functionClass($data);
			}
		}
		return new FunctionBase($data);
	}
}

/**
 * Class FunctionBase
 * Base class for all function objects processed by engine.
 *
 * @package Bitrix\Iblock\Template\Functions
 */
class FunctionBase
{
	protected $data = null;

	/**
	 * @param mixed|null $data Additional data for function instance.
	 */
	public function __construct($data = null)
	{
		$this->data = $data;
	}
	/**
	 * Called before calculation. It's result will be passed to the calculate method.
	 *
	 * @param \Bitrix\Iblock\Template\Entity\Base $entity This object.
	 * @param array[]\Bitrix\Iblock\Template\NodeBase $parameters Parsed and prepared list of parameters.
	 *
	 * @return array
	 */
	static public function onPrepareParameters(\Bitrix\Iblock\Template\Entity\Base $entity, array $parameters)
	{
		$arguments = array();
		/** @var \Bitrix\Iblock\Template\NodeBase $parameter */
		foreach ($parameters as $parameter)
		{
			$arguments[] = $parameter->process($entity);
		}
		return $arguments;
	}

	/**
	 * Called by engine to process function call.
	 *
	 * @param array $parameters Function parameters.
	 *
	 * @return string
	 */
	static public function calculate(array $parameters)
	{
		return "";
	}

	/**
	 * Helper function. Concatenates all the parameters into a string.
	 *
	 * @param array $parameters Function parameters.
	 *
	 * @return string
	 */
	protected function parametersToString(array $parameters)
	{
		$result = array();
		foreach ($parameters as $param)
		{
			if (is_array($param))
				$result[] = implode(" ", $param);
			elseif ($param != "")
				$result[] = $param;
		}
		return implode(" ", $result);
	}

	/**
	 * Helper function. Gathers all the parameters into an flat array.
	 *
	 * @param array $parameters Function parameters.
	 *
	 * @return array
	 */
	protected function parametersToArray(array $parameters)
	{
		$result = array();
		foreach ($parameters as $param)
		{
			if (is_array($param))
			{
				foreach ($param as $p)
					$result[] = $p;
			}
			elseif ($param != "")
			{
				$result[] = $param;
			}
		}
		return $result;
	}
}

/**
 * Class FunctionUpper
 * Represents upper function {=upper this.name}.
 *
 * @package Bitrix\Iblock\Template\Functions
 */
class FunctionUpper extends FunctionBase
{
	/**
	 * Called by engine to process function call.
	 *
	 * @param array $parameters Function parameters.
	 *
	 * @return string
	 */
	public function calculate(array $parameters)
	{
		return toUpper($this->parametersToString($parameters));
	}
}

/**
 * Class FunctionLower
 * Represents lower function {=lower this.name}.
 *
 * @package Bitrix\Iblock\Template\Functions
 */
class FunctionLower extends FunctionBase
{
	/**
	 * Called by engine to process function call.
	 *
	 * @param array $parameters Function parameters.
	 *
	 * @return string
	 */
	public function calculate(array $parameters)
	{
		return toLower($this->parametersToString($parameters));
	}
}

/**
 * Class FunctionTranslit
 * Transliterates it's input {=translit this.name}.
 *
 * @package Bitrix\Iblock\Template\Functions
 */
class FunctionTranslit extends FunctionBase
{
	/**
	 * Called by engine to process function call.
	 *
	 * @param array $parameters Function parameters.
	 *
	 * @return string
	 */
	public function calculate(array $parameters)
	{
		$changeCase = false;
		$replaceChar = "";

		if (
			isset($this->data)
			&& isset($this->data["replace_space"])
			&& $this->data["replace_space"] != ""
		)
		{
			$changeCase = isset($this->data[""])? $this->data["change_case"]: false;
			$replaceChar = $this->data["replace_space"];
		}

		if (
			isset($this->data)
			&& isset($this->data["change_case"])
			&& $this->data["change_case"] != ""
		)
		{
			$changeCase = $this->data["change_case"];
		}


		return \CUtil::translit($this->parametersToString($parameters), LANGUAGE_ID, array(
			//"max_len" => 50,
			"change_case" => $changeCase, // 'L' - toLower, 'U' - toUpper, false - do not change
			"replace_space" => $replaceChar,
			"replace_other" => $replaceChar,
			"delete_repeat_replace" => true,
		));
	}
}

/**
 * Class FunctionConcat
 * Represents concatenation function {=concat iblock.name sections.name this.property.cml2_link.property.articul " / "}.
 *
 * @package Bitrix\Iblock\Template\Functions
 */
class FunctionConcat extends FunctionBase
{
	/**
	 * Called by engine to process function call.
	 *
	 * @param array $parameters Function parameters.
	 *
	 * @return string
	 */
	public function calculate(array $parameters)
	{
		$result = $this->parametersToArray($parameters);
		$delimiter = array_pop($result);

		return implode($delimiter, $result);
	}
}

/**
 * Class FunctionLimit
 * Explodes list elements by delimiter and returns no more than limit elements {=limit this.previewText this.DetailText  " \t\n\r" 10}.
 *
 * @package Bitrix\Iblock\Template\Functions
 */
class FunctionLimit extends FunctionBase
{
	/**
	 * Called by engine to process function call.
	 *
	 * @param array $parameters Function parameters.
	 *
	 * @return string
	 */
	public function calculate(array $parameters)
	{
		$result = $this->parametersToArray($parameters);
		$limit = array_pop($result);
		$delimiter = array_pop($result);
		$text = implode(" ", $result);

		$result = preg_split("/([".preg_quote($delimiter, "/")."]+)/", $text);
		return array_slice($result, 0, $limit);
	}
}

/**
 * Class FunctionContrast
 * Explodes list elements by delimiter and returns no more than limit most contrast words {=contrast this.previewText this.DetailText  " \t\n\r" 10}.
 *
 * @package Bitrix\Iblock\Template\Functions
 */
class FunctionContrast extends FunctionBase
{
	/**
	 * Called by engine to process function call.
	 *
	 * @param array $parameters Function parameters.
	 *
	 * @return string
	 */
	public function calculate(array $parameters)
	{
		$result = $this->parametersToArray($parameters);
		$limit = array_pop($result);
		$delimiter = array_pop($result);
		$text = strip_tags(implode(" ", $result));

		$words = array();
		$result = preg_split("/([".preg_quote($delimiter, "/")."]+)/", $text);
		if ($result)
		{
			foreach ($result as $word)
			{
				if (strlen($word) > 1)
					$words[$word]++;
			}
			$len = log(max(20, array_sum($words)));
			foreach ($words as $word => $count)
			{
				$words[$word] = log($count + 1) / $len;
			}
			arsort($words);
		}
		return array_keys(array_slice($words, 0, $limit));
	}
}

/**
 * Class FunctionMin
 * Returns minimum value of given {=min  this.catalog.sku.price.base}.
 *
 * @package Bitrix\Iblock\Template\Functions
 */
class FunctionMin extends FunctionBase
{
	/**
	 * Called by engine to process function call.
	 *
	 * @param array $parameters Function parameters.
	 *
	 * @return string
	 */
	public function calculate(array $parameters)
	{
		$result = $this->parametersToArray($parameters);
		$asFloat = array();
		foreach ($result as $value)
		{
			if (!isset($asFloat[$value]))
			{
				$floatFalue = doubleval(preg_replace("/[^0-9.]+/", "", $value));
				$asFloat[$value] = $floatFalue;
			}
		}
		if (empty($asFloat))
		{
			return '';
		}
		elseif (count($asFloat) == 1)
		{
			return end($result);
		}
		else
		{
			$min = min($asFloat);
			return array_search($min, $asFloat);
		}
	}
}

/**
 * Class FunctionMax
 * Returns maximum value of given {=min  this.catalog.sku.price.base}.
 *
 * @package Bitrix\Iblock\Template\Functions
 */
class FunctionMax extends FunctionBase
{
	/**
	 * Called by engine to process function call.
	 *
	 * @param array $parameters Function parameters.
	 *
	 * @return string
	 */
	public function calculate(array $parameters)
	{
		$result = $this->parametersToArray($parameters);
		$asFloat = array();
		foreach ($result as $value)
		{
			if (!isset($asFloat[$value]))
			{
				$floatFalue = doubleval(preg_replace("/[^0-9.]+/", "", $value));
				$asFloat[$value] = $floatFalue;
			}
		}
		if (empty($asFloat))
		{
			return '';
		}
		elseif (count($asFloat) == 1)
		{
			return end($result);
		}
		else
		{
			$max = max($asFloat);
			return array_search($max, $asFloat);
		}
	}
}

/**
 * Class FunctionDistinct
 * Returns maximum value of given {=min  this.catalog.sku.price.base}.
 *
 * @package Bitrix\Iblock\Template\Functions
 */
class FunctionDistinct extends FunctionBase
{
	/**
	 * Called by engine to process function call.
	 *
	 * @param array $parameters Function parameters.
	 *
	 * @return string
	 */
	public function calculate(array $parameters)
	{
		$result = array();
		foreach ($this->parametersToArray($parameters) as $value)
			$result[$value] = $value;
		return array_values($result);
	}
}
