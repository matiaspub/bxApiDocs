<?php

namespace Bitrix\Main\UrlPreview;

use Bitrix\Main\Application;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Web\Uri;

class Router
{
	const CACHE_ID = 'UrlPreviewRouteCache';
	const CACHE_TTL = 315360000;

	/** @var array
	 * Allowed keys: ID, MODULE, CLASS, BUILD_METHOD, CHECK_METHOD, PARAMETERS
	 */
	protected static $routeTable = array();

	/** @var \Bitrix\Main\Data\ManagedCache */
	protected static $managedCache;

	/** @var bool */
	protected static $initialized = false;

	/**
	 * Adds, or, if route already exists, changes route handling method.
	 * @param string $route Route URL template.
	 * Route parameters should be enclosed in hash symbols, like '/user/#userId#/'.
	 * @param string $handlerModule Route handler module.
	 * @param string $handlerClass Route handler class should implement methods:
	 * <ul>
	 * 		<li>buildPreview($params): string. Method must accept array of parameters and return rendered preview
	 * 		<li>checkUserReadAccess($params): boolean. Method must accept array of parameters. Method must return true if
	 * 			currently logged in user has read access to the entity; false otherwise.
	 * 		<li>getCacheTag(): string. Method must return cache tag for the entity.
	 * </ul>.
	 * @param array $handlerParameters Array of parameters, passed to the handler methods.
	 * Will be passed as the argument when calling handler's method for building preview or checking access.
	 * Array values may contain variables referencing route parameters.
	 * e.g. ['userId' => '$userId'].
	 * @return void
	 * @throws ArgumentException
	 */
	
	/**
	* <p>Статический метод добавляет или, если маршрут существует, изменяет метод обработки маршрута.</p>
	*
	*
	* @param string $route  Шаблон URL маршрута. Параметры маршрута следует заключать в хеш
	* символы, пример: <code>/user/#userId#/</code>.
	*
	* @param string $handlerModule  Модуль обработчика маршрута.
	*
	* @param string $handlerClass  Класс обработчика маршрута должен применять методы:<br><ul> <li>
	* <code>buildPreview($params): string</code> Метод должен принимать массив параметров
	* и возвращать готовую "богатую ссылку". </li> <li> <code>checkUserReadAccess($params):
	* boolean</code>. Метод должен принимать массив параметров и возвращать
	* <i>true</i>, если зарегистрированный пользователь успешно прочитал
	* сущность и <i>false</i> в противном случае. </li> <li> <code>getCacheTag(): string</code>.
	* Метод должен возвращать тег кеша для сущности. </li> </ul>
	*
	* @param array $handlerParameters  Массив параметров отправляемых методом в обработчик. Массив
	* должен быть передан как аргумент когда вызывается метод
	* обработчика для создания "богатой ссылки" или проверки доступа.
	* Массив значений должен содержать переменные, ссылающиеся на
	* параметры маршрута, например: <code>['userId' =&gt; '$userId']</code>.
	*
	* @return void 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/urlpreview/router/setroutehandler.php
	* @author Bitrix
	*/
	public static function setRouteHandler($route, $handlerModule, $handlerClass, array $handlerParameters)
	{
		static::init();

		if(!is_string($route) || strlen($route) == 0)
			throw new ArgumentException('Route could not be empty', '$route');
		if(!is_string($handlerModule) || strlen($handlerModule) == 0)
			throw new ArgumentException('Handler module could not be empty', '$handler');
		if(!is_string($handlerClass) || strlen($handlerClass) == 0)
			throw new ArgumentException('Handler class could not be empty', '$handler');

		$newRoute = true;
		if(isset(static::$routeTable[$route]))
		{
			if (   $handlerModule === static::$routeTable[$route]['MODULE']
				&& $handlerClass === static::$routeTable[$route]['CLASS']
				&& $handlerParameters == static::$routeTable[$route]['PARAMETERS']
			)
			{
				return;
			}
			$newRoute = false;
		}

		static::$routeTable[$route]['ROUTE'] = $route;
		static::$routeTable[$route]['REGEXP'] = static::convertRouteToRegexp($route);
		static::$routeTable[$route]['MODULE'] = $handlerModule;
		static::$routeTable[$route]['CLASS'] = $handlerClass;
		static::$routeTable[$route]['PARAMETERS'] = $handlerParameters;

		static::persistRoute($route, $newRoute);
	}

	/**
	 * Returns handler for the url
	 *
	 * @param Uri $uri Absolute or relative URL.
	 * @return array|false Handler for this URL if found, false otherwise.
	 */
	
	/**
	* <p>Статический метод возвращает обработчик для URL.</p>
	*
	*
	* @param mixed $Bitrix  Абсолютный или относительный URL.
	*
	* @param Bitri $Main  
	*
	* @param Mai $Web  
	*
	* @param Uri $uri  
	*
	* @return mixed 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/urlpreview/router/dispatch.php
	* @author Bitrix
	*/
	public static function dispatch(Uri $uri)
	{
		static::init();

		$urlPath = $uri->getPath();
		//todo: replace cycle with compiled regexp for all routes
		foreach(static::$routeTable as $routeRecord)
		{
			if(preg_match($routeRecord['REGEXP'], $urlPath, $matches))
			{
				$result = $routeRecord;
				//replace parameters variables with values
				foreach($result['PARAMETERS'] as $parameterName => &$parameterValue)
				{
					if(strpos($parameterValue, '$') === 0)
					{
						$variableName = substr($parameterValue, 1);
						if(isset($matches[$variableName]))
						{
							$parameterValue = $matches[$variableName];
						}
					}
				}
				return $result;
			}
		}

		return false;
	}

	/**
	 * Initializes router and prepares routing table.
	 * @return void
	 */
	protected static function init()
	{
		if(static::$initialized)
			return;

		static::$managedCache = Application::getInstance()->getManagedCache();

		if(static::$managedCache->read(static::CACHE_TTL, static::CACHE_ID))
		{
			static::$routeTable = (array)static::$managedCache->get(static::CACHE_ID);
		}
		else
		{
			$queryResult = RouteTable::getList(array(
				'select' => array('*')
			));

			while($routeRecord = $queryResult->fetch())
			{
				$routeRecord['REGEXP'] = static::convertRouteToRegexp($routeRecord['ROUTE']);
				static::$routeTable[$routeRecord['ROUTE']] = $routeRecord;
			}

			uksort(static::$routeTable, function($a, $b)
			{
				$lengthOfA = strlen($a);
				$lengthOfB = strlen($b);
				if($lengthOfA > $lengthOfB)
					return -1;
				else if($lengthOfA == $lengthOfB)
					return 0;
				else
					return 1;
			});

			static::$managedCache->set(static::CACHE_ID, static::$routeTable);
		}

		static::$initialized = true;
	}

	/**
	 * Persists routing table record in database
	 *
	 * @param string $route Route URL template.
	 * @param bool $isNew True if handler record was not encountered in router cache.
	 * @return bool Returns true if route is successfully stored in the database table, and false otherwise.
	 */
	protected static function persistRoute($route, $isNew)
	{
		static::invalidateRouteCache();
		//Oracle does not support 'merge ... returning field into :field' clause, thus we can't merge clob fields into the table.
		$routeData = array(
			'ROUTE' => static::$routeTable[$route]['ROUTE'],
			'MODULE' => static::$routeTable[$route]['MODULE'],
			'CLASS' => static::$routeTable[$route]['CLASS'],
		);

		if($isNew)
		{
			$addResult = RouteTable::merge($routeData);
			if($addResult->isSuccess())
			{
				static::$routeTable[$route]['ID'] = $addResult->getId();
				RouteTable::update(
						static::$routeTable[$route]['ID'],
						array(
								'PARAMETERS' => static::$routeTable[$route]['PARAMETERS']
						)
				);
			}
			$result = $addResult->isSuccess();
		}
		else
		{
			$routeData['PARAMETERS'] = static::$routeTable[$route]['PARAMETERS'];
			$updateResult = RouteTable::update(static::$routeTable[$route]['ID'], $routeData);
			$result = $updateResult->isSuccess();
		}
		return $result;
	}

	/**
	 * Return regexp string for checking URL against route template.
	 * @param string $route Route URL template.
	 * @return string
	 */
	protected static function convertRouteToRegexp($route)
	{
		$result = preg_replace("/#(\w+)#/", "(?'\\1'[^/]+)", $route);
		$result = str_replace('/', '\/', $result);
		$result = '/^'.$result.'$/';

		return $result;
	}

	/**
	 * Resets router cache
	 * @return void
	 */
	
	/**
	* <p>Статический метод сбрасывает кеш маршрутизатора.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return void 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/urlpreview/router/invalidateroutecache.php
	* @author Bitrix
	*/
	public static function invalidateRouteCache()
	{
		Application::getInstance()->getManagedCache()->clean(static::CACHE_ID);
	}
}
