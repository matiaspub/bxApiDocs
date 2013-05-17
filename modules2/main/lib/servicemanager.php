<?php
namespace Bitrix\Main;

final class ServiceManager
	implements \ArrayAccess
{
	private $tools = array();

	const DB = "DB";

	public function __construct()
	{
	}

	static public function loadServices()
	{
		$this->register("cache", "CacheManager");
		// событие на регистрацию сервисов
	}

	static public function register($name, $tool)
	{
		if (!isset($this->tools[$name]))
			$this->tools[$name] = array();

		array_unshift($this->tools[$name], $tool);
	}

	static public function unregister($name)
	{
		if (!isset($this->tools[$name]))
			$this->tools[$name] = array();

		array_shift($this->tools[$name]);
	}

	static public function get($name)
	{
		if (isset($this->tools[$name]) && count($this->tools[$name]) > 0)
		{
			$obj = $this->tools[$name][0];
			if (is_object($obj))
			{
				return $obj;
			}
			elseif (is_string($obj) && class_exists($obj))
			{
				$this->tools[$name][0] = new $obj();
				return $this->tools[$name][0];
			}
			elseif (is_array($obj) && isset($obj[0]) && isset($obj[1]) && is_string($obj[0]) && is_string($obj[1]))
			{
				if (Loader::includeModule($obj[0]) && class_exists($obj[1]))
				{
					$this->tools[$name][0] = new $obj[1]();
					return $this->tools[$name][0];
				}
				throw new LoaderException(sprintf("Class name '%s' is not found in module '%s'", htmlspecialchars($obj[1]), htmlspecialchars($obj[0])));
			}

			throw new SystemException(sprintf("Tool '%s' is not found", htmlspecialchars($obj)));
		}
		return null;
	}

	static public function offsetExists($name)
	{
		return isset($this->tools[$name]) && count($this->tools[$name]) > 0;
	}

	static public function offsetGet($name)
	{
		return $this->get($name);
	}

	static public function offsetSet($name, $tool)
	{
		$this->register($name, $tool);
	}

	static public function offsetUnset($name)
	{
		$this->unregister($name);
	}

	static public function __set($name, $tool)
	{
		$this->register($name, $tool);
	}

	static public function __get($name)
	{
		return $this->get($name);
	}

	static public function getToolDb()
	{
		return $this->get("DB");
	}
}
