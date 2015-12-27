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
	public function getModifyDdl(BaseObject $target, $dbType = '')
	{
		return array(
			$this->getDropDdl($dbType),
			$target->getCreateDdl($dbType),
		);
	}
}