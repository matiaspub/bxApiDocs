<?php
namespace Bitrix\Perfmon\Sql;
use Bitrix\Main\NotSupportedException;

class Table extends BaseObject
{
	/** @var Collection */
	public $columns = null;
	/** @var Collection */
	public $indexes = null;
	/** @var Collection */
	public $constraints = null;
	/** @var Collection */
	public $triggers = null;

	/**
	 * @param string $name Index name.
	 */
	public function __construct($name = '')
	{
		parent::__construct($name);
		$this->columns = new Collection;
		$this->indexes = new Collection;
		$this->constraints = new Collection;
		$this->triggers = new Collection;
	}

	/**
	 * Creates trigger object from tokens.
	 * <p>
	 * And registers trigger in the table trigger registry.
	 *
	 * @param Tokenizer $tokenizer Tokens collection.
	 *
	 * @return Table
	 * @see Constraint::create
	 */
	
	/**
	* <p>Нестатический метод создает объект триггера из токенов. Также регистрирует триггер в таблице регистрации триггеров.</p>
	*
	*
	* @param mixed $Bitrix  Поток токенов.
	*
	* @param Bitri $Perfmon  
	*
	* @param Perfmo $Sql  
	*
	* @param Tokenizer $tokenizer  
	*
	* @return \Bitrix\Perfmon\Sql\Table 
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_d7/bitrix/perfmon/sql/constraint/create.php">Constraint::create</a>
	* (<code>\Bitrix\Perfmon\Sql\Constraint::create</code>)</li> </ul><a name="example"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/perfmon/sql/table/createtrigger.php
	* @author Bitrix
	*/
	public function createTrigger(Tokenizer $tokenizer)
	{
		$trigger = Trigger::create($tokenizer);
		$trigger->setParent($this);
		$this->triggers->add($trigger);
		return $this;
	}

	/**
	 * Creates constraint object from tokens.
	 * <p>
	 * And registers constraint in the table constraint registry.
	 *
	 * @param Tokenizer $tokenizer Tokens collection.
	 * @param string $constraintName Optional name of the constraint.
	 *
	 * @return Table
	 * @see Constraint::create
	 */
	
	/**
	* <p>Нестатический метод создает объект ограничения из токенов. Также регистрирует ограничение в таблице регистрации ограничений.</p> <br>
	*
	*
	* @param mixed $Bitrix  Поток токенов.
	*
	* @param Bitri $Perfmon  Имя ограничения.
	*
	* @param Perfmo $Sql  
	*
	* @param Tokenizer $tokenizer  
	*
	* @param string $constraintName = '' 
	*
	* @return \Bitrix\Perfmon\Sql\Table 
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_d7/bitrix/perfmon/sql/constraint/create.php">Constraint::create</a>
	* (<code>\Bitrix\Perfmon\Sql\Constraint::create</code>)</li> </ul><a name="example"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/perfmon/sql/table/createconstraint.php
	* @author Bitrix
	*/
	public function createConstraint(Tokenizer $tokenizer, $constraintName = '')
	{
		$constraint = Constraint::create($tokenizer, $constraintName);
		$constraint->setParent($this);
		$this->constraints->add($constraint);
		return $this;
	}

	/**
	 * Creates index object from tokens.
	 * <p>
	 * And registers index in the table index registry.
	 *
	 * @param Tokenizer $tokenizer Tokens collection.
	 * @param boolean $unique Uniqueness flag.
	 * @param string $indexName Optional name of the index.
	 *
	 * @return Table
	 * @see Index::create
	 */
	
	/**
	* <p>Нестатический момент создает объект индекса из токенов. Также регистрирует индекс в таблице регистраций индексов.</p> <br>
	*
	*
	* @param mixed $Bitrix  Поток токенов.
	*
	* @param Bitri $Perfmon  Флаг уникальности.
	*
	* @param Perfmo $Sql  Имя индекса.
	*
	* @param Tokenizer $tokenizer  
	*
	* @param boolean $unique = false 
	*
	* @param string $indexName = '' 
	*
	* @return \Bitrix\Perfmon\Sql\Table 
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_d7/bitrix/perfmon/sql/index/create.php">Index::create</a>
	* (<code>\Bitrix\Perfmon\Sql\Index::create</code>)</li> </ul><a name="example"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/perfmon/sql/table/createindex.php
	* @author Bitrix
	*/
	public function createIndex(Tokenizer $tokenizer, $unique = false, $indexName = '')
	{
		$index = Index::create($tokenizer, $unique, $indexName);
		$index->setParent($this);
		$this->indexes->add($index);
		return $this;
	}

	/**
	 * Creates column object from tokens.
	 * <p>
	 * And registers column in the table column registry.
	 *
	 * @param Tokenizer $tokenizer Tokens collection.
	 *
	 * @return Table
	 * @see Column::create
	 */
	
	/**
	* <p>Нестатический метод создает колонку таблицы из токенов. Также регистрирует колонку в таблице регистрации колонок.</p> <br>
	*
	*
	* @param mixed $Bitrix  Поток токенов.
	*
	* @param Bitri $Perfmon  
	*
	* @param Perfmo $Sql  
	*
	* @param Tokenizer $tokenizer  
	*
	* @return \Bitrix\Perfmon\Sql\Table 
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_d7/bitrix/perfmon/sql/column/create.php">Column::create</a>
	* (<code>\Bitrix\Perfmon\Sql\Column::create</code>)</li> </ul><a name="example"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/perfmon/sql/table/createcolumn.php
	* @author Bitrix
	*/
	public function createColumn(Tokenizer $tokenizer)
	{
		$column = Column::create($tokenizer);
		$column->setParent($this);
		$this->columns->add($column);
		return $this;
	}

	/**
	 * Creates table object from tokens.
	 * <p>
	 * Current position should point to the name of the sequence or 'if not exists' clause.
	 *
	 * @param Tokenizer $tokenizer Tokens collection.
	 *
	 * @return Table
	 * @throws NotSupportedException
	 */
	
	/**
	* <p>Статический метод создает объект таблица из токенов. Текущая позиция должна указывать на название последовательности или на условие <code>'if not exists'</code>.</p> <br>
	*
	*
	* @param mixed $Bitrix  Поток токенов.
	*
	* @param Bitri $Perfmon  
	*
	* @param Perfmo $Sql  
	*
	* @param Tokenizer $tokenizer  
	*
	* @return \Bitrix\Perfmon\Sql\Table 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/perfmon/sql/table/create.php
	* @author Bitrix
	*/
	public static function create(Tokenizer $tokenizer)
	{
		$tokenizer->skipWhiteSpace();

		if ($tokenizer->testUpperText('IF'))
		{
			$tokenizer->skipWhiteSpace();

			if ($tokenizer->testUpperText('NOT'))
				$tokenizer->skipWhiteSpace();

			if ($tokenizer->testUpperText('EXISTS'))
				$tokenizer->skipWhiteSpace();
		}

		$table = new Table($tokenizer->getCurrentToken()->text);

		$tokenizer->nextToken();
		$tokenizer->skipWhiteSpace();

		if ($tokenizer->testText('('))
		{
			$tokenizer->skipWhiteSpace();

			$token = $tokenizer->getCurrentToken();
			$level = $token->level;
			do
			{
				if (
					$tokenizer->testUpperText('INDEX')
					|| $tokenizer->testUpperText('KEY')
				)
				{
					$tokenizer->skipWhiteSpace();
					$table->createIndex($tokenizer, false);
				}
				elseif ($tokenizer->testUpperText('UNIQUE'))
				{
					$tokenizer->skipWhiteSpace();
					if ($tokenizer->testUpperText('KEY'))
						$tokenizer->skipWhiteSpace();
					elseif ($tokenizer->testUpperText('INDEX'))
						$tokenizer->skipWhiteSpace();
					$table->createIndex($tokenizer, true);
				}
				elseif ($tokenizer->testUpperText('PRIMARY'))
				{
					$tokenizer->skipWhiteSpace();
					if (!$tokenizer->testUpperText('KEY'))
						throw new NotSupportedException("'KEY' expected. line:".$tokenizer->getCurrentToken()->line);

					$tokenizer->putBack(); //KEY
					$tokenizer->putBack(); //WS
					$tokenizer->putBack(); //PRIMARY
					$table->createConstraint($tokenizer, false);
				}
				elseif ($tokenizer->testUpperText('CONSTRAINT'))
				{
					$tokenizer->skipWhiteSpace();
					$constraintName = $tokenizer->getCurrentToken()->text;

					$tokenizer->nextToken();
					$tokenizer->skipWhiteSpace();

					if ($tokenizer->testUpperText('PRIMARY') || $tokenizer->testUpperText('UNIQUE'))
					{
						$tokenizer->putBack();
						$table->createConstraint($tokenizer, $constraintName);
					}
					elseif ($tokenizer->testUpperText('FOREIGN'))
					{
						$tokenizer->putBack();
						$table->createConstraint($tokenizer, $constraintName);
					}
					else
					{
						throw new NotSupportedException("'PRIMARY KEY' expected. line:".$tokenizer->getCurrentToken()->line);
					}
				}
				elseif ($tokenizer->testUpperText(')'))
				{
					break;
				}
				else
				{
					$table->createColumn($tokenizer);
				}

				$tokenizer->skipWhiteSpace();

				$token = $tokenizer->getCurrentToken();

				if ($token->level == $level && $token->text == ',')
				{
					$token = $tokenizer->nextToken();
				}
				elseif ($token->level < $level && $token->text == ')')
				{
					$tokenizer->nextToken();
					break;
				}
				else
				{
					throw new NotSupportedException("',' or ')' expected. line:".$token->line);
				}

				$tokenizer->skipWhiteSpace();
			}
			while (!$tokenizer->endOfInput() && $token->level >= $level);

			$suffix = '';
			while (!$tokenizer->endOfInput())
			{
				$suffix .= $tokenizer->getCurrentToken()->text;
				$tokenizer->nextToken();
			}
			if ($suffix)
				$table->setBody($suffix);
		}
		else
		{
			throw new NotSupportedException("'(' expected. line:".$tokenizer->getCurrentToken()->line);
		}

		return $table;
	}

	/**
	 * Return DDL for table creation.
	 *
	 * @param string $dbType Database type (MYSQL, ORACLE or MSSQL).
	 *
	 * @return array|string
	 */
	
	/**
	* <p>Нестатический метод возвращает DDL для создания таблицы.</p>
	*
	*
	* @param string $dbType = '' Тип базы данных (<i>MYSQL</i>, <i>ORACLE</i> или <i>MSSQL</i>).
	*
	* @return mixed 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/perfmon/sql/table/getcreateddl.php
	* @author Bitrix
	*/
	public function getCreateDdl($dbType = '')
	{
		$result = array();

		$items = array();
		/** @var Column $column */
		foreach ($this->columns->getList() as $column)
		{
			$items[] = $column->name." ".$column->body;
		}
		if ($dbType !== 'MSSQL')
		{
			/** @var Constraint $constraint */
			foreach ($this->constraints->getList() as $constraint)
			{
				if ($constraint->name === '')
					$items[] = $constraint->body;
				else
					$items[] = "CONSTRAINT ".$constraint->name." ".$constraint->body;
			}
		}
		$result[] = "CREATE TABLE ".$this->name."(\n\t".implode(",\n\t", $items)."\n)".$this->body;

		if ($dbType === 'MSSQL')
		{
			/** @var Constraint $constraint */
			foreach ($this->constraints->getList() as $constraint)
			{
				$result[] = $constraint->getCreateDdl($dbType);
			}
		}

		return $result;
	}

	/**
	 * Return DDL for table destruction.
	 *
	 * @param string $dbType Database type (MYSQL, ORACLE or MSSQL).
	 *
	 * @return array|string
	 */
	
	/**
	* <p>Нестатический метод возвращает DDL для удаления таблицы.</p>
	*
	*
	* @param string $dbType = '' Тип базы данных (<i>MYSQL</i>, <i>ORACLE</i> или <i>MSSQL</i>).
	*
	* @return mixed 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/perfmon/sql/table/getdropddl.php
	* @author Bitrix
	*/
	public function getDropDdl($dbType = '')
	{
		return "DROP TABLE ".$this->name;
	}
}