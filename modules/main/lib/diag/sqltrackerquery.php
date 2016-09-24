<?php
namespace Bitrix\Main\Diag;

class SqlTrackerQuery
	implements \ArrayAccess
{
	/** @var string */
	protected $sql = "";
	/** @var array|null */
	protected $binds = null;
	/** @var string */
	protected $state = "";
	/** @var string */
	protected $node = "";
	/** @var float */
	protected $startTime = 0.0;
	/** @var float */
	protected $finishTime = 0.0;
	/** @var float */
	protected $time = 0.0;
	/** @var array|null */
	protected $trace = null;
	/** @var SqlTracker */
	protected $tracker;

	/**
	 * @param SqlTracker $tracker This sql tracker.
	 */
	public function __construct(SqlTracker $tracker)
	{
		$this->tracker = $tracker;
	}

	/**
	 * Starts sql timer.
	 *
	 * @param string $sql Query text.
	 * @param array $binds Binded variables used with query.
	 *
	 * @return void
	 */
	
	/**
	* <p>Нестатический метод запускает sql таймер.</p>
	*
	*
	* @param string $sql  Текст запроса.
	*
	* @param array $binds = null Привязать переменные используемые в запросе.
	*
	* @return void 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/diag/sqltrackerquery/startquery.php
	* @author Bitrix
	*/
	public function startQuery($sql, array $binds = null)
	{
		$this->sql = $sql;
		$this->binds = $binds;
		$this->startTime = Helper::getCurrentMicrotime();
	}

	/**
	 * Ends sql timer.
	 *
	 * @param integer $skip How many backtrace skip. By default 3.
	 *
	 * @return void
	 */
	
	/**
	* <p>Нестатический метод завершает работу таймера sql.</p>
	*
	*
	* @param integer $skip = 3 Сколько трассировок пропустить. По умолчанию - 3.
	*
	* @return void 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/diag/sqltrackerquery/finishquery.php
	* @author Bitrix
	*/
	public function finishQuery($skip = 3)
	{
		$this->finishTime = Helper::getCurrentMicrotime();
		$this->time = $this->finishTime - $this->startTime;
		$this->trace = $this->filterTrace(Helper::getBackTrace(8, null, $skip));

		$this->tracker->addTime($this->time);
		$this->tracker->writeFileLog($this->sql, $this->time, "", 4);
	}

	/**
	 * Resets sql timer start.
	 * combined with refinishQuery allows additional time to be included into execution.
	 *
	 * @return void
	 * @see \Bitrix\Main\Diag\SqlTrackerQuery::refinishQuery
	 */
	
	/**
	* <p>Нестатический метод сбрасывает старт таймера sql запроса. В сочетании с <a href="http://dev.1c-bitrix.ru/api_d7/bitrix/main/diag/sqltrackerquery/refinishquery.php">refinishQuery</a> добавляет дополнительное время при выполнении запроса.</p> <p>Без параметров</p>
	*
	*
	* @return void 
	*
	* <h4>See Also</h4> 
	* <ul> <li><a
	* href="http://dev.1c-bitrix.ru/api_d7/bitrix/main/diag/sqltrackerquery/refinishquery.php">\Bitrix\Main\Diag\SqlTrackerQuery::refinishQuery</a></li>
	* </ul><a name="example"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/diag/sqltrackerquery/restartquery.php
	* @author Bitrix
	*/
	public function restartQuery()
	{
		$this->startTime = Helper::getCurrentMicrotime();
	}

	/**
	 * Finishes query timer one more time.
	 * Use with restartQuery.
	 *
	 * @return void
	 * @see \Bitrix\Main\Diag\SqlTrackerQuery::restartQuery
	 */
	
	/**
	* <p>Нестатический метод ещё раз завершает таймер запроса. Используется в паре с <a href="http://dev.1c-bitrix.ru/api_d7/bitrix/main/diag/sqltrackerquery/restartquery.php">restartQuery</a>.</p> <p>Без параметров</p>
	*
	*
	* @return void 
	*
	* <h4>See Also</h4> 
	* <ul> <li><a
	* href="http://dev.1c-bitrix.ru/api_d7/bitrix/main/diag/sqltrackerquery/restartquery.php">\Bitrix\Main\Diag\SqlTrackerQuery::restartQuery</a></li>
	* </ul><a name="example"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/diag/sqltrackerquery/refinishquery.php
	* @author Bitrix
	*/
	public function refinishQuery()
	{
		$this->finishTime = Helper::getCurrentMicrotime();
		$this->addTime($this->finishTime - $this->startTime);
	}

	/**
	 * Returns tracked sql text.
	 *
	 * @return string
	 */
	
	/**
	* <p>Нестатический метод возвращает текст отслеживаемого sql запроса.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/diag/sqltrackerquery/getsql.php
	* @author Bitrix
	*/
	public function getSql()
	{
		return $this->sql;
	}

	/**
	 * Sets tracked sql text.
	 * Returns the object for call chaining.
	 *
	 * @param string $sql Sql text.
	 *
	 * @return \Bitrix\Main\Diag\SqlTrackerQuery
	 */
	
	/**
	* <p>Нестатический метод устанавливает  текст отслеживаемого sql запроса.</p> <p>Возвращает объект для построения цепочки вызовов.</p>
	*
	*
	* @param string $sql  Текст sql.
	*
	* @return \Bitrix\Main\Diag\SqlTrackerQuery 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/diag/sqltrackerquery/setsql.php
	* @author Bitrix
	*/
	public function setSql($sql)
	{
		$this->sql = (string)$sql;
		return $this;
	}

	/**
	 * Returns sql binds used for query execution.
	 *
	 * @return array|null
	 */
	
	/**
	* <p>Нестатический метод возвращает список sql связей используемых для выполнения запроса.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return mixed 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/diag/sqltrackerquery/getbinds.php
	* @author Bitrix
	*/
	public function getBinds()
	{
		return $this->binds;
	}

	/**
	 * Sets tracked sql binds.
	 * Returns the object for call chaining.
	 *
	 * @param array $binds Sql binds.
	 *
	 * @return \Bitrix\Main\Diag\SqlTrackerQuery
	 */
	
	/**
	* <p>Нестатический метод устанавливает связи отслеживаемого sql запроса.</p> <p>Возвращает объект для построения цепочки вызовов.</p>
	*
	*
	* @param array $binds  Sql связи
	*
	* @return \Bitrix\Main\Diag\SqlTrackerQuery 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/diag/sqltrackerquery/setbinds.php
	* @author Bitrix
	*/
	public function setBinds(array $binds)
	{
		$this->binds = $binds;
		return $this;
	}

	/**
	 * Returns page state of the query.
	 *
	 * @return string
	 */
	
	/**
	* <p>Нестатический метод возвращает состояние страницы запроса.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/diag/sqltrackerquery/getstate.php
	* @author Bitrix
	*/
	public function getState()
	{
		return $this->state;
	}

	/**
	 * Sets tracked sql page state.
	 * Returns the object for call chaining.
	 *
	 * @param string $state Page state.
	 *
	 * @return \Bitrix\Main\Diag\SqlTrackerQuery
	 */
	
	/**
	* <p>Нестатический метод устанавливает состояние  отслеживаемой sql страницы.</p> <p>Возвращает объект для построения цепочки вызовов.</p>
	*
	*
	* @param string $state  Состояние страницы
	*
	* @return \Bitrix\Main\Diag\SqlTrackerQuery 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/diag/sqltrackerquery/setstate.php
	* @author Bitrix
	*/
	public function setState($state)
	{
		$this->state = (string)$state;
		return $this;
	}

	/**
	 * Returns sql connection node id of the query.
	 *
	 * @return string
	 */
	
	/**
	* <p>Нестатический метод возвращает ID ноды sql соединения запроса.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/diag/sqltrackerquery/getnode.php
	* @author Bitrix
	*/
	public function getNode()
	{
		return $this->node;
	}

	/**
	 * Sets tracked sql connection node id.
	 * Returns the object for call chaining.
	 *
	 * @param string $node Cluster node identifier.
	 *
	 * @return \Bitrix\Main\Diag\SqlTrackerQuery
	 */
	
	/**
	* <p>Нестатический метод устанавливает ID отслеживаемой ноды соединения.</p> <p>Возвращает объект для построения цепочки вызовов.</p>
	*
	*
	* @param string $node  Идентификатор кластера ноды.
	*
	* @return \Bitrix\Main\Diag\SqlTrackerQuery 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/diag/sqltrackerquery/setnode.php
	* @author Bitrix
	*/
	public function setNode($node)
	{
		$this->node = (string)$node;
		return $this;
	}

	/**
	 * Returns sql execution time.
	 *
	 * @return float
	 */
	
	/**
	* <p>Нестатический метод возвращает время выполнения запроса.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return float 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/diag/sqltrackerquery/gettime.php
	* @author Bitrix
	*/
	public function getTime()
	{
		return $this->time;
	}

	/**
	 * Sets tracked sql execution time.
	 * Returns the object for call chaining.
	 *
	 * @param float $time Sql execution time in seconds.
	 *
	 * @return \Bitrix\Main\Diag\SqlTrackerQuery
	 */
	
	/**
	* <p>Нестатический метод устанавливает времея выполнения отслеживаемого sql запроса.</p> <p>Возвращает объект для построения цепочки вызовов.</p>
	*
	*
	* @param float $time  Время выполнения запроса в секундах.
	*
	* @return \Bitrix\Main\Diag\SqlTrackerQuery 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/diag/sqltrackerquery/settime.php
	* @author Bitrix
	*/
	public function setTime($time)
	{
		$this->tracker->addTime(-$this->time);
		$this->time = (float)$time;
		$this->tracker->addTime($this->time);
		return $this;
	}

	/**
	 * Increments sql execution time.
	 *
	 * @param float $time Time in seconds to add.
	 *
	 * @return void
	 */
	
	/**
	* <p>Нестатический метод добавляет время для выполнения запроса.</p>
	*
	*
	* @param float $time  Время в секундах для выполнения.
	*
	* @return void 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/diag/sqltrackerquery/addtime.php
	* @author Bitrix
	*/
	public function addTime($time)
	{
		$time = (float)$time;
		$this->time += $time;
		$this->tracker->addTime($time);
	}

	/**
	 * Returns backtrace of the query.
	 *
	 * @return array|null
	 */
	
	/**
	* <p>Нестатический метод возвращает трассировку запроса.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return mixed 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/diag/sqltrackerquery/gettrace.php
	* @author Bitrix
	*/
	public function getTrace()
	{
		return $this->trace;
	}

	/**
	 * Sets tracked sql backtrace.
	 * Returns the object for call chaining.
	 *
	 * @param array $trace Query backtrace.
	 *
	 * @return \Bitrix\Main\Diag\SqlTrackerQuery
	 */
	
	/**
	* <p>Нестатический метод устанавливает трассировку отслеживаемого sql запроса.</p> <p>Возвращает объект для построения цепочки вызовов.</p>
	*
	*
	* @param array $trace  Трассировка запроса
	*
	* @return \Bitrix\Main\Diag\SqlTrackerQuery 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/diag/sqltrackerquery/settrace.php
	* @author Bitrix
	*/
	public function setTrace(array $trace)
	{
		$this->trace = $this->filterTrace($trace);
		return $this;
	}

	/**
	 * Whether a offset exists.
	 * Part of ArrayAccess implementation made for backward compatibility.
	 *
	 * @param mixed $offset Array key.
	 *
	 * @return boolean
	 */
	
	/**
	* <p>Нестатический метод проверяет существование смещения. Часть реализации <code>\ArrayAccess</code> сделана для обеспечения обратной совместимости.</p>
	*
	*
	* @param mixed $offset  Массив ключей.
	*
	* @return boolean 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/diag/sqltrackerquery/offsetexists.php
	* @author Bitrix
	*/
	static public function offsetExists($offset)
	{
		switch ((string)$offset)
		{
		case "BX_STATE":
			return true;
		case "TIME":
			return true;
		case "QUERY":
			return true;
		case "TRACE":
			return true;
		case "NODE_ID":
			return true;
		default:
			return false;
		}
	}

	/**
	 * Offset to retrieve.
	 * Part of ArrayAccess implementation made for backward compatibility.
	 *
	 * @param mixed $offset Array key.
	 *
	 * @return mixed
	 */
	
	/**
	* <p>Нестатический метод. Получаемое смещение.</p> <p>Часть реализации <code>\ArrayAccess</code> сделана для обеспечения обратной совместимости.</p>
	*
	*
	* @param mixed $offset  Массив ключей
	*
	* @return mixed 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/diag/sqltrackerquery/offsetget.php
	* @author Bitrix
	*/
	public function offsetGet($offset)
	{
		switch ($offset)
		{
		case "BX_STATE":
			return $this->state;
		case "TIME":
			return $this->time;
		case "QUERY":
			return $this->sql;
		case "TRACE":
			return $this->trace;
		case "NODE_ID":
			return $this->node;
		default:
			return false;
		}
	}

	/**
	 * Offset to set.
	 * Part of ArrayAccess implementation made for backward compatibility.
	 *
	 * @param mixed $offset Array key.
	 * @param mixed $value Array value.
	 *
	 * @return mixed
	 */
	
	/**
	* <p>Нестатический метод. Включаемое смещение. Часть реализации <code>\ArrayAccess</code> сделана для обеспечения обратной совместимости.</p>
	*
	*
	* @param mixed $offset  Массив ключей
	*
	* @param mixed $value  Массив значений.
	*
	* @return mixed 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/diag/sqltrackerquery/offsetset.php
	* @author Bitrix
	*/
	static public function offsetSet($offset, $value)
	{
	}

	/**
	 * Offset to unset.
	 * Part of ArrayAccess implementation made for backward compatibility.
	 *
	 * @param mixed $offset Array key.
	 *
	 * @return mixed
	 */
	
	/**
	* <p>Нестатический метод. Отключаемое смещение. Часть реализации <code>\ArrayAccess</code> сделана для обеспечения обратной совместимости.</p>
	*
	*
	* @param mixed $offset  Массив ключей.
	*
	* @return mixed 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/diag/sqltrackerquery/offsetunset.php
	* @author Bitrix
	*/
	static public function offsetUnset($offset)
	{
	}

	/**
	 * Removes and formats memory consuming function arguments in the backtrace.
	 *
	 * @param array $trace Backtrace.
	 *
	 * @return array
	 */
	protected function filterTrace($trace)
	{
		$filtered = array();
		foreach ($trace as $i => $tr)
		{
			$args = array();
			if (is_array($tr["args"]))
			{
				foreach ($tr["args"] as $k1 => $v1)
				{
					if (is_array($v1))
					{
						foreach ($v1 as $k2 => $v2)
						{
							if (is_scalar($v2))
								$args[$k1][$k2] = $v2;
							elseif (is_object($v2))
								$args[$k1][$k2] = get_class($v2);
							else
								$args[$k1][$k2] = gettype($v2);
						}
					}
					else
					{
						if (is_scalar($v1))
							$args[$k1] = $v1;
						elseif (is_object($v1))
							$args[$k1] = get_class($v1);
						else
							$args[$k1] = gettype($v1);
					}
				}
			}

			$filtered[] = array(
				"file" => $tr["file"],
				"line" => $tr["line"],
				"class" => $tr["class"],
				"type" => $tr["type"],
				"function" => $tr["function"],
				"args" => $args,
			);
		}
		return $filtered;
	}
}
