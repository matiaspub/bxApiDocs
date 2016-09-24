<?php
namespace Bitrix\Perfmon\Sql;

use Bitrix\Main\NotSupportedException;

class Trigger extends BaseObject
{
	/**
	 * Creates trigger object from tokens.
	 * <p>
	 * Current position should point to the name of the trigger.
	 *
	 * @param Tokenizer $tokenizer Tokens collection.
	 *
	 * @return Trigger
	 */
	
	/**
	* <p>Статический метод создает триггер из токенов. Текущая позиция должна указывать на имя триггера.</p> <br>
	*
	*
	* @param mixed $Bitrix  Набор токенов.
	*
	* @param Bitri $Perfmon  
	*
	* @param Perfmo $Sql  
	*
	* @param Tokenizer $tokenizer  
	*
	* @return \Bitrix\Perfmon\Sql\Trigger 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/perfmon/sql/trigger/create.php
	* @author Bitrix
	*/
	public static function create(Tokenizer $tokenizer)
	{
		$name = $tokenizer->getCurrentToken()->text;
		$trigger = new self($name);

		$tokenizer->resetState();
		$definition = '';
		while (!$tokenizer->endOfInput())
		{
			$definition .= $tokenizer->getCurrentToken()->text;
			$tokenizer->nextToken();
		}

		$trigger->setBody($definition);

		return $trigger;
	}

	/**
	 * Searches token collection for 'ON' keyword.
	 * <p>
	 * Advances current position on to next token skipping whitespace.
	 *
	 * @param Tokenizer $tokenizer Tokens collection.
	 *
	 * @return void
	 * @throws NotSupportedException
	 */
	
	/**
	* <p>Статический метод проверяет набор токенов на наличие ключевого слова <code>'ON'</code>. Текущая позиция продвигается к следующему токену, пропуская пробелы.</p> <br>
	*
	*
	* @param mixed $Bitrix  Набор токенов.
	*
	* @param Bitri $Perfmon  
	*
	* @param Perfmo $Sql  
	*
	* @param Tokenizer $tokenizer  
	*
	* @return void 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/perfmon/sql/trigger/searchtablename.php
	* @author Bitrix
	*/
	public static function searchTableName(Tokenizer $tokenizer)
	{
		$lineToken = $tokenizer->getCurrentToken();
		while (!$tokenizer->endOfInput())
		{
			if ($tokenizer->getCurrentToken()->upper === 'ON')
			{
				$tokenizer->nextToken();
				$tokenizer->skipWhiteSpace();
				return;
			}
			$tokenizer->nextToken();
		}
		throw new NotSupportedException('Trigger: table name not found. line: '.$lineToken->line);
	}

	/**
	 * Return DDL for trigger creation.
	 *
	 * @param string $dbType Database type (MYSQL, ORACLE or MSSQL).
	 *
	 * @return array|string
	 */
	
	/**
	* <p>Нестатический метод возвращает DDL для создания триггера.</p>
	*
	*
	* @param string $dbType = '' Тип базы данных (<i>MYSQL</i>, <i>ORACLE</i> или <i>MSSQL</i>).
	*
	* @return mixed 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/perfmon/sql/trigger/getcreateddl.php
	* @author Bitrix
	*/
	public function getCreateDdl($dbType = '')
	{
		return $this->body;
	}

	/**
	 * Return DDL for trigger destruction.
	 *
	 * @param string $dbType Database type (MYSQL, ORACLE or MSSQL).
	 *
	 * @return array|string
	 */
	
	/**
	* <p>Нестатический метод возвращает DDL для удаления триггера.</p>
	*
	*
	* @param string $dbType = '' Тип базы данных (<i>MYSQL</i>, <i>ORACLE</i> или <i>MSSQL</i>).
	*
	* @return mixed 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/perfmon/sql/trigger/getdropddl.php
	* @author Bitrix
	*/
	public function getDropDdl($dbType = '')
	{
		switch ($dbType)
		{
		case "MYSQL":
			return "// ".get_class($this).":getDropDdl for database type [".$dbType."] not implemented";
		case "MSSQL":
			return "DROP TRIGGER ".$this->name;
		case "ORACLE":
			return "DROP TRIGGER ".$this->name;
		default:
			return "// ".get_class($this).":getDropDdl for database type [".$dbType."] not implemented";
		}
	}

	/**
	 * Return DDL for trigger modification (drop with subsequent create).
	 *
	 * @param BaseObject $target Target object.
	 * @param string $dbType Database type (MYSQL, ORACLE or MSSQL).
	 *
	 * @return array|string
	 */
	
	/**
	* <p>Нестатический метод возвращает DDL для модификации триггера (сначала удаляется старая, затем создается новая версия).</p>
	*
	*
	* @param mixed $Bitrix  Целевой объект.
	*
	* @param Bitri $Perfmon  Тип базы данных (<i>MYSQL</i>, <i>ORACLE</i> или <i>MSSQL</i>).
	*
	* @param Perfmo $Sql  
	*
	* @param BaseObject $target  
	*
	* @param string $dbType = '' 
	*
	* @return mixed 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/perfmon/sql/trigger/getmodifyddl.php
	* @author Bitrix
	*/
	public function getModifyDdl(BaseObject $target, $dbType = '')
	{
		return array(
			$this->getDropDdl($dbType),
			$target->getCreateDdl($dbType),
		);
	}
}