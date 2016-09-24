<?php
namespace Bitrix\Perfmon\Sql;

class Procedure extends BaseObject
{
	public $type = '';

	/**
	 * @param string $name Name of stored procedure.
	 * @param string $type Type of stored procedure.
	 */
	public function __construct($name = '', $type = '')
	{
		parent::__construct($name);
		$this->type = (string)$type;
	}

	/**
	 * Creates stored procedure object from tokens.
	 * <p>
	 * Current position should point to the type of the stored procedure (PROCEDURE, FUNCTION or TYPE).
	 * <p>
	 * Name may consist of two parts divided by '.'.
	 *
	 * @param Tokenizer $tokenizer Tokens collection.
	 *
	 * @return Procedure
	 */
	
	/**
	* <p>Статический метод создает хранящуюся процедуру из токенов.</p> <p></p> <p> Текущая позиция должна указывать на тип хранящейся процедуры (<code>PROCEDURE</code>, <code>FUNCTION</code> или <code>TYPE</code>). Имя может состоять из двух частей, разделенных с помощью '.'.</p>
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
	* @return \Bitrix\Perfmon\Sql\Procedure 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/perfmon/sql/procedure/create.php
	* @author Bitrix
	*/
	public static function create(Tokenizer $tokenizer)
	{
		$type = $tokenizer->getCurrentToken()->text;
		$tokenizer->nextToken();
		$tokenizer->skipWhiteSpace();

		$name = $tokenizer->getCurrentToken()->text;
		$token = $tokenizer->nextToken();
		if ($token->text === '.')
		{
			$token = $tokenizer->nextToken();
			$name .= '.'.$token->text;
		}
		$procedure = new self($name, $type);

		$tokenizer->resetState();
		$definition = '';
		while (!$tokenizer->endOfInput())
		{
			$definition .= $tokenizer->getCurrentToken()->text;
			$tokenizer->nextToken();
		}

		$procedure->setBody($definition);

		return $procedure;
	}

	/**
	 * Return DDL for procedure creation.
	 *
	 * @param string $dbType Database type (MYSQL, ORACLE or MSSQL).
	 *
	 * @return array|string
	 */
	
	/**
	* <p>Нестатический метод возвращает DDL для создания процедуры.</p>
	*
	*
	* @param string $dbType = '' Тип базы данных (<i>MYSQL</i>, <i>ORACLE</i> или <i>MSSQL</i>).
	*
	* @return mixed 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/perfmon/sql/procedure/getcreateddl.php
	* @author Bitrix
	*/
	public function getCreateDdl($dbType = '')
	{
		return $this->body;
	}

	/**
	 * Return DDL for procedure destruction.
	 *
	 * @param string $dbType Database type (MYSQL, ORACLE or MSSQL).
	 *
	 * @return array|string
	 */
	
	/**
	* <p>Нестатический метод возвращает DDL для удаления процедуры.</p>
	*
	*
	* @param string $dbType = '' Тип базы данных (<i>MYSQL</i>, <i>ORACLE</i> или <i>MSSQL</i>).
	*
	* @return mixed 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/perfmon/sql/procedure/getdropddl.php
	* @author Bitrix
	*/
	public function getDropDdl($dbType = '')
	{
		return "DROP ".$this->type." ".$this->name;
	}

	/**
	 * Return DDL for procedure modification.
	 *
	 * @param BaseObject $target Target object.
	 * @param string $dbType Database type (MYSQL, ORACLE or MSSQL).
	 *
	 * @return array|string
	 */
	
	/**
	* <p>Нестатический метод возвращает  DDL для модификации процедуры.</p>
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
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/perfmon/sql/procedure/getmodifyddl.php
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