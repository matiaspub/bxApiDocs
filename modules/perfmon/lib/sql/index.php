<?php
namespace Bitrix\Perfmon\Sql;
use Bitrix\Main\NotSupportedException;

class Index extends BaseObject
{
	public $unique = false;
	public $columns = array();

	/**
	 * @param string $name Index name.
	 * @param boolean $unique Uniqueness flag.
	 */
	public function __construct($name = '', $unique)
	{
		parent::__construct($name);
		$this->unique = (bool)$unique;
	}

	/**
	 * Adds column to the index definition.
	 *
	 * @param string $name Column name.
	 *
	 * @return Index
	 */
	public function addColumn($name)
	{
		$this->columns[] = trim($name);
		$this->setBody(implode(", ", $this->columns));
		return $this;
	}

	/**
	 * Creates index object from tokens.
	 * <p>
	 * If parameter $indexName is not passed then current position should point to the name of the index.
	 *
	 * @param Tokenizer $tokenizer Tokens collection.
	 * @param boolean $unique Uniqueness flag.
	 * @param string $indexName Optional name of the index.
	 *
	 * @return Index
	 * @throws NotSupportedException
	 */
	public static function create(Tokenizer $tokenizer, $unique = false, $indexName = '')
	{
		if (!$indexName)
		{
			if ($tokenizer->getCurrentToken()->text !== '(')
			{
				$indexName = $tokenizer->getCurrentToken()->text;
				$tokenizer->nextToken();
				$tokenizer->skipWhiteSpace();
			}
		}

		if ($tokenizer->testUpperText('ON'))
		{
			$tokenizer->skipWhiteSpace();
			/** @noinspection PhpUnusedLocalVariableInspection */
			$tableName = $tokenizer->getCurrentToken()->text;
			$tokenizer->nextToken();
			$tokenizer->skipWhiteSpace();
		}

		$index = new self($indexName, $unique);

		if ($tokenizer->testText('('))
		{
			$tokenizer->skipWhiteSpace();
			$token = $tokenizer->getCurrentToken();
			$level = $token->level;
			$column = '';
			do
			{
				if ($token->text === ',')
				{
					$index->addColumn($column);
					$column = '';
				}
				else
				{
					$column .= $token->text;
				}
				$token = $tokenizer->nextToken();
			}
			while (!$tokenizer->endOfInput() && $token->level >= $level);

			if ($column)
			{
				$index->addColumn($column);
			}

			if (!$tokenizer->testText(')'))
				throw new NotSupportedException("')' expected. line:".$tokenizer->getCurrentToken()->line);
		}
		else
		{
			throw new NotSupportedException("'(' expected. line:".$tokenizer->getCurrentToken()->line);
		}

		return $index;
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
		throw new NotSupportedException('Index: table name not found. line: '.$lineToken->line);
	}

	/**
	 * Return DDL for index creation.
	 *
	 * @param string $dbType Database type (MYSQL, ORACLE or MSSQL).
	 *
	 * @return array|string
	 */
	public function getCreateDdl($dbType = '')
	{
		return "CREATE ".($this->unique? "UNIQUE ": "")."INDEX ".$this->name." ON ".$this->parent->name."(".$this->body.")";
	}

	/**
	 * Return DDL for index destruction.
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
			return "DROP INDEX ".$this->name." ON ".$this->parent->name;
		case "MSSQL":
			return "DROP INDEX ".$this->name;
		case "ORACLE":
			return "DROP INDEX ".$this->name;
		default:
			return "// ".get_class($this).":getDropDdl for database type [".$dbType."] not implemented";
		}
	}

	/**
	 * Return DDL for index modification.
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