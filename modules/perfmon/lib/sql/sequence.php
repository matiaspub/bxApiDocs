<?php
namespace Bitrix\Perfmon\Sql;

class Sequence extends BaseObject
{
	/**
	 * Creates sequence object from tokens.
	 * <p>
	 * Current position should point to the name of the sequence.
	 *
	 * @param Tokenizer $tokenizer Tokens collection.
	 *
	 * @return Sequence
	 */
	
	/**
	* <p>Статический метод создает объект последовательности из токенов. Текущая позиция должна указывать на название последовательности.</p> <br>
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
	* @return \Bitrix\Perfmon\Sql\Sequence 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/perfmon/sql/sequence/create.php
	* @author Bitrix
	*/
	public static function create(Tokenizer $tokenizer)
	{
		$name = $tokenizer->getCurrentToken()->text;
		$sequence = new self($name);

		$tokenizer->resetState();
		$definition = '';
		while (!$tokenizer->endOfInput())
		{
			$definition .= $tokenizer->getCurrentToken()->text;
			$tokenizer->nextToken();
		}

		$sequence->setBody($definition);

		return $sequence;
	}

	/**
	 * Return DDL for sequence creation.
	 *
	 * @param string $dbType Database type (ORACLE only).
	 *
	 * @return array|string
	 */
	
	/**
	* <p>Нестатический метод возвращает DDL для создания последовательности.</p>
	*
	*
	* @param string $dbType = '' Тип базы данных (только <i>ORACLE</i>).
	*
	* @return mixed 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/perfmon/sql/sequence/getcreateddl.php
	* @author Bitrix
	*/
	public function getCreateDdl($dbType = '')
	{
		switch ($dbType)
		{
		case "ORACLE":
			return $this->body;
		default:
			return "// ".get_class($this).":getDropDdl for database type [".$dbType."] not implemented";
		}
	}

	/**
	 * Return DDL for sequence destruction.
	 *
	 * @param string $dbType Database type (ORACLE only).
	 *
	 * @return array|string
	 */
	
	/**
	* <p>Нестатический метод возвращает DDL для удаления последовательности.</p>
	*
	*
	* @param string $dbType = '' Тип базы данных (только <i>ORACLE</i>).
	*
	* @return mixed 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/perfmon/sql/sequence/getdropddl.php
	* @author Bitrix
	*/
	public function getDropDdl($dbType = '')
	{
		switch ($dbType)
		{
		case "ORACLE":
			return "DROP SEQUENCE ".$this->name;
		default:
			return "// ".get_class($this).":getDropDdl for database type [".$dbType."] not implemented";
		}
	}

	/**
	 * Return DDL for sequence modification (drop with subsequent create).
	 *
	 * @param BaseObject $target Target object.
	 * @param string $dbType Database type (ORACLE only).
	 *
	 * @return array|string
	 */
	
	/**
	* <p>Нестатический метод возвращает DDL для модификации последовательности (сначала удаляется старая и затем создается новая версия).</p>
	*
	*
	* @param mixed $Bitrix  Целевой объект.
	*
	* @param Bitri $Perfmon  Тип базы данных (только <i>ORACLE</i>).
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
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/perfmon/sql/sequence/getmodifyddl.php
	* @author Bitrix
	*/
	public function getModifyDdl(BaseObject $target, $dbType = '')
	{
		switch ($dbType)
		{
		case "ORACLE":
			return array(
				$this->getDropDdl($dbType),
				$target->getCreateDdl($dbType),
			);
		default:
			return "// ".get_class($this).":getDropDdl for database type [".$dbType."] not implemented";
		}
	}
}