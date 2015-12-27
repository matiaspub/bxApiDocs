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
	public function getModifyDdl(BaseObject $target, $dbType = '')
	{
		return array(
			$this->getDropDdl($dbType),
			$target->getCreateDdl($dbType),
		);
	}
}