<?php
namespace Bitrix\Main\DB;

/**
 * Class Result is the abstract base class for representing
 * database query result.
 * <p>
 * It has ability to transform raw data populated from
 * the database into useful associative arrays with
 * some fields unserialized and some presented as Datetime
 * objects or other changes.
 * <p>
 * It also supports query debugging by providing {@link \Bitrix\Main\Diag\SqlTracker}
 * with timing information.
 *
 * @package Bitrix\Main\DB
 */
abstract class Result
{
	/** @var \Bitrix\Main\DB\Connection */
	protected $connection;
	/** @var resource */
	protected $resource;
	/** @var \Bitrix\Main\Diag\SqlTrackerQuery */
	protected $trackerQuery = null;

	/** @var callable[] */
	protected $converters = array();
	/** @var string[] */
	protected $serializedFields = array();
	/** @var string[] */
	protected $replacedAliases = array();
	/** @var callable[] */
	protected $fetchDataModifiers = array();

	/** @var int */
	protected $count;

	/**
	 * @param resource $result Database-specific query result.
	 * @param Connection $dbConnection Connection object.
	 * @param \Bitrix\Main\Diag\SqlTrackerQuery $trackerQuery Helps to collect debug information.
	 */
	public function __construct($result, Connection $dbConnection = null, \Bitrix\Main\Diag\SqlTrackerQuery $trackerQuery = null)
	{
		$this->resource = $result;
		$this->connection = $dbConnection;
		$this->trackerQuery = $trackerQuery;
		$resultFields = $this->getFields();
		if ($resultFields && $this->connection)
		{
			$helper = $this->connection->getSqlHelper();
			foreach ($resultFields as $key => $type)
			{
				$converter = $helper->getConverter($resultFields[$key]);
				if (is_callable($converter))
				{
					$this->converters[$key] = $converter;
				}
			}
		}
	}

	/**
	 * Returns database-specific resource of this result.
	 *
	 * @return null|resource
	 */
	
	/**
	* <p>Нестатический метод возвращает специфичные ресурсы запроса.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return mixed 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/result/getresource.php
	* @author Bitrix
	*/
	public function getResource()
	{
		return $this->resource;
	}

	/**
	 * Sets list of aliased columns.
	 * This allows to overcome database limits on length of the column names.
	 *
	 * @param array[string]string $replacedAliases Aliases map from tech to human.
	 *
	 * @return void
	 * @see \Bitrix\Main\Db\Result::addReplacedAliases
	 */
	
	/**
	* <p>Нестатический метод устанавливает список колонок с алиасами. Это позволяет обойти ограничение базы данных на длину имён колонок.</p>
	*
	*
	* @param mixed $Bitrix  Карта алиасов с технического на человекопонятные названия.
	*
	* @param Bitri $Main  
	*
	* @param Mai $MaiDB  
	*
	* @param D $array  
	*
	* @param arra $string  
	*
	* @param string $replacedAliases  
	*
	* @return void 
	*
	* <h4>See Also</h4> 
	* <ul> <li><a
	* href="http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/result/addreplacedaliases.php">\Bitrix\Main\Db\Result::addReplacedAliases</a></li>
	* </ul><a name="example"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/result/setreplacedaliases.php
	* @author Bitrix
	*/
	public function setReplacedAliases(array $replacedAliases)
	{
		$this->replacedAliases = $replacedAliases;
	}

	/**
	 * Extends list of aliased columns.
	 *
	 * @param array[string]string $replacedAliases Aliases map from tech to human.
	 *
	 * @return void
	 * @see \Bitrix\Main\Db\Result::setReplacedAliases
	 */
	
	/**
	* <p>Нестатический метод расширяет список колонок с алиасами.</p>
	*
	*
	* @param mixed $Bitrix  Карта алиасов от технических названий к человекопонятным.
	*
	* @param Bitri $Main  
	*
	* @param Mai $MaiDB  
	*
	* @param D $array  
	*
	* @param arra $string  
	*
	* @param string $replacedAliases  
	*
	* @return void 
	*
	* <h4>See Also</h4> 
	* <ul> <li><a
	* href="http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/result/setreplacedaliases.php">\Bitrix\Main\Db\Result::setReplacedAliases</a></li>
	* </ul><a name="example"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/result/addreplacedaliases.php
	* @author Bitrix
	*/
	public function addReplacedAliases(array $replacedAliases)
	{
		$this->replacedAliases = array_merge($this->replacedAliases, $replacedAliases);
	}

	/**
	 * Sets internal list of fields which will be unserialized on fetch.
	 *
	 * @param array $serializedFields List of fields.
	 *
	 * @return void
	 */
	
	/**
	* <p>Нестатический метод устанавливает внутренний список полей, которые должны быть десериализированы при получении.</p>
	*
	*
	* @param array $serializedFields  Список полей
	*
	* @return void 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/result/setserializedfields.php
	* @author Bitrix
	*/
	public function setSerializedFields(array $serializedFields)
	{
		$this->serializedFields = $serializedFields;
	}

	/**
	 * Modifier should accept once fetched array as an argument, then modify by link or return new array:
	 * - function (&$data) { $data['AGE'] -= 7; }
	 * - function ($data) { $data['AGE'] -= 7; return $data; }
	 *
	 * @param callable $fetchDataModifier Valid callback.
	 *
	 * @return void
	 * @throws \Bitrix\Main\ArgumentException
	 */
	
	/**
	* <p>Нестатический метод. Модификатор получает извлечённый массив как аргумент и модифицирует его как ссылку или возвращает новый массив</p> <pre class="syntax">function (&amp;$data) { $data['AGE'] -= 7; }  function ($data) { $data['AGE'] -= 7; return $data; }</pre>
	*
	*
	* @param callable $fetchDataModifier  Валидный ответ.
	*
	* @return void 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/result/addfetchdatamodifier.php
	* @author Bitrix
	*/
	public function addFetchDataModifier($fetchDataModifier)
	{
		if (!is_callable($fetchDataModifier))
		{
			throw new \Bitrix\Main\ArgumentException('Data Modifier should be a callback');
		}

		$this->fetchDataModifiers[] = $fetchDataModifier;
	}

	/**
	 * Fetches one row of the query result and returns it in the associative array of raw DB data or false on empty data.
	 *
	 * @return array|false
	 */
	
	/**
	* <p>Нестатический метод получает одну строку из запроса и возвращает её в ассоциированном массиве с необработанными данными БД или возвращает <i>false</i> при пустых данных.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return mixed 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/result/fetchraw.php
	* @author Bitrix
	*/
	public function fetchRaw()
	{
		if ($this->trackerQuery != null)
		{
			$this->trackerQuery->restartQuery();
		}

		$data = $this->fetchRowInternal();

		if ($this->trackerQuery != null)
		{
			$this->trackerQuery->refinishQuery();
		}

		if (!$data)
		{
			return false;
		}

		return $data;
	}

	/**
	 * Fetches one row of the query result and returns it in the associative array of converted data or false on empty data.
	 *
	 * @param \Bitrix\Main\Text\Converter $converter Optional converter to encode data on fetching.
	 *
	 * @return array|false
	 */
	
	/**
	* <p>Нестатический метод получает строку из результата запроса и возвращает её в ассоциативном массиве с конвертированными данными или возвращает <i>false</i> при пустых данных.</p>
	*
	*
	* @param mixed $Bitrix  Конвертер для расшифровки данных при получении.
	*
	* @param Bitri $Main  
	*
	* @param Mai $Text  
	*
	* @param Converter $converter = null 
	*
	* @return mixed 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/result/fetch.php
	* @author Bitrix
	*/
	public function fetch(\Bitrix\Main\Text\Converter $converter = null)
	{
		$data = $this->fetchRaw();

		if (!$data)
		{
			return false;
		}

		if ($this->converters)
		{
			foreach ($this->converters as $field => $convertDataModifier)
			{
				$data[$field] = call_user_func_array($convertDataModifier, array($data[$field]));
			}
		}

		if ($this->serializedFields)
		{
			foreach ($this->serializedFields as $field)
			{
				if (isset($data[$field]))
					$data[$field] = unserialize($data[$field]);
			}
		}

		if ($this->replacedAliases)
		{
			foreach ($this->replacedAliases as $tech => $human)
			{
				$data[$human] = $data[$tech];
				unset($data[$tech]);
			}
		}

		if ($this->fetchDataModifiers)
		{
			foreach ($this->fetchDataModifiers as $fetchDataModifier)
			{
				$result = call_user_func_array($fetchDataModifier, array(&$data));

				if (is_array($result))
				{
					$data = $result;
				}
			}
		}

		if ($converter != null)
		{
			foreach ($data as $key => $val)
			{
				$data[$key] = $converter->encode(
					$val,
					(isset($data[$key."_TYPE"])? $data[$key."_TYPE"] : \Bitrix\Main\Text\Converter::TEXT)
				);
			}
		}

		return $data;
	}

	/**
	 * Fetches all the rows of the query result and returns it in the array of associative arrays.
	 * Returns an empty array if query has no data.
	 *
	 * @param \Bitrix\Main\Text\Converter $converter Optional converter to encode data on fetching.
	 *
	 * @return array
	 */
	
	/**
	* <p>Нестатический метод получает все строки запроса и возвращает ассоциированный массив. Если запрос пустой, возвращает пустой массив.</p>
	*
	*
	* @param mixed $Bitrix  Конвертер для расшифровки при получении.
	*
	* @param Bitri $Main  
	*
	* @param Mai $Text  
	*
	* @param Converter $converter = null 
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/result/fetchall.php
	* @author Bitrix
	*/
	public function fetchAll(\Bitrix\Main\Text\Converter $converter = null)
	{
		$res = array();
		while ($ar = $this->fetch($converter))
		{
			$res[] = $ar;
		}
		return $res;
	}

	/**
	 * Returns an array of fields according to columns in the result.
	 *
	 * @return @return \Bitrix\Main\Entity\ScalarField[]
	 */
	
	/**
	* <p>Нестатический абстрактный метод возвращает массив полей, связанный с колонками в результате запроса.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return \Bitrix\Main\DB\@return 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/result/getfields.php
	* @author Bitrix
	*/
	abstract public function getFields();

	/**
	 * Returns the number of rows in the result.
	 *
	 * @return int
	 */
	
	/**
	* <p>Нестатический абстрактный метод возвращает число строк в результате запроса.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return integer 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/result/getselectedrowscount.php
	* @author Bitrix
	*/
	abstract public function getSelectedRowsCount();

	/**
	 * Returns next result row or false.
	 *
	 * @return array|false
	 */
	abstract protected function fetchRowInternal();

	/**
	 * Returns current query tracker.
	 *
	 * @return \Bitrix\Main\Diag\SqlTrackerQuery|null
	 */
	
	/**
	* <p>Нестатический метод возвращает трекер текущего запроса.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return \Bitrix\Main\Diag\SqlTrackerQuery|null 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/result/gettrackerquery.php
	* @author Bitrix
	*/
	public function getTrackerQuery()
	{
		return $this->trackerQuery;
	}

	/**
	 * Sets record count.
	 * @param int $n
	 */
	
	/**
	* <p>Нестатический метод производит запись количества.</p>
	*
	*
	* @param mixed $integern  Записываемое количество
	*
	* @return public 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/result/setcount.php
	* @author Bitrix
	*/
	public function setCount($n)
	{
		$this->count = (int)$n;
	}

	/**
	 * Returns record count. It's required to set record count explicitly before.
	 * @return int
	 * @throws \Bitrix\Main\ObjectPropertyException
	 */
	
	/**
	* <p>Нестатический метод возвращает записанное количество. Необходимо чтобы запись была сделана ранее.</p> <p>Без параметров</p>
	*
	*
	* @return integer 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/result/getcount.php
	* @author Bitrix
	*/
	public function getCount()
	{
		if($this->count !== null)
		{
			return $this->count;
		}
		throw new \Bitrix\Main\ObjectPropertyException("count");
	}
}
