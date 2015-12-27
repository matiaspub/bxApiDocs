<?php
namespace Bitrix\Perfmon\Sql;
use Bitrix\Main\NotSupportedException;

class Column extends BaseObject
{
	public $type = '';
	public $length = '';
	public $nullable = true;
	public $default = null;

	protected static $types = array(
		'INT' => true,
		'INTEGER' => true,
		'TINYINT' => true,
		'NUMERIC' => true,
		'NUMBER' => true,
		'FLOAT' => true,
		'DOUBLE' => true,
		'DECIMAL' => true,
		'BIGINT' => true,
		'SMALLINT' => true,
		'MEDIUMINT' => true,
		'VARCHAR' => true,
		'VARCHAR2' => true,
		'CHAR' => true,
		'TIMESTAMP' => true,
		'DATETIME' => true,
		'DATE' => true,
		'TIME' => true,
		'TEXT' => true,
		'LONGTEXT' => true,
		'MEDIUMTEXT' => true,
		'CLOB' => true,
		'BLOB' => true,
		'MEDIUMBLOB' => true,
		'LONGBLOB' => true,
		'VARBINARY' => true,
		'IMAGE' => true,
		'ENUM' => true,
	);

	/**
	 * Checks the $type against type list:
	 * - INT
	 * - INTEGER
	 * - TINYINT
	 * - NUMERIC
	 * - NUMBER
	 * - FLOAT
	 * - DOUBLE
	 * - DECIMAL
	 * - BIGINT
	 * - SMALLINT
	 * - MEDIUMINT
	 * - VARCHAR
	 * - VARCHAR2
	 * - CHAR
	 * - TIMESTAMP
	 * - DATETIME
	 * - DATE
	 * - TIME
	 * - TEXT
	 * - LONGTEXT
	 * - MEDIUMTEXT
	 * - CLOB
	 * - BLOB
	 * - MEDIUMBLOB
	 * - LONGBLOB
	 * - VARBINARY
	 * - IMAGE
	 * - ENUM
	 *
	 * @param string $type Type of a column.
	 *
	 * @return boolean
	 */
	public static function checkType($type)
	{
		return isset(self::$types[$type]);
	}

	/**
	 * Creates column object from tokens.
	 * <p>
	 * Current position should point to the name of the column.
	 *
	 * @param Tokenizer $tokenizer Tokens collection.
	 *
	 * @return Column
	 * @throws NotSupportedException
	 */
	public static function create(Tokenizer $tokenizer)
	{
		$columnName = $tokenizer->getCurrentToken()->text;

		$tokenizer->nextToken();
		$tokenizer->skipWhiteSpace();
		$token = $tokenizer->getCurrentToken();

		$columnType = $token->upper;
		if (!self::checkType($columnType))
		{
			throw new NotSupportedException("column type expected but [".$tokenizer->getCurrentToken()->text."] found. line: ".$tokenizer->getCurrentToken()->line);
		}

		$column = new self($columnName);
		$column->type = $columnType;

		$level = $token->level;
		$lengthLevel = -1;
		$columnDefinition = '';
		do
		{
			if ($token->level == $level && $token->text == ',')
				break;
			if ($token->level < $level && $token->text == ')')
				break;
			
			$columnDefinition .= $token->text;

			if ($token->upper === 'NOT')
				$column->nullable = false;
			elseif ($token->upper === 'DEFAULT')
				$column->default = false;
			elseif ($column->default === false)
			{
				if ($token->type !== Token::T_WHITESPACE && $token->type !== Token::T_COMMENT)
				{
					$column->default = $token->text;
				}
			}

			$token = $tokenizer->nextToken();

			//parentheses after type
			if ($lengthLevel == -1)
			{
				if ($token->text == '(')
				{
					$lengthLevel = $token->level;
					$column->length = '';
					while (!$tokenizer->endOfInput())
					{
						$columnDefinition .= $token->text;

						$token = $tokenizer->nextToken();

						if ($token->level == $lengthLevel && $token->text == ')')
							break;
							
						$column->length .= $token->text;
					}
				}
				elseif ($token->type !== Token::T_WHITESPACE && $token->type !== Token::T_COMMENT)
				{
					$lengthLevel = 0;
				}
			}
		}
		while (!$tokenizer->endOfInput());

		$column->setBody($columnDefinition);

		return $column;
	}

	/**
	 * Return DDL for table column creation.
	 *
	 * @param string $dbType Database type (MYSQL, ORACLE or MSSQL).
	 *
	 * @return array|string
	 */
	public function getCreateDdl($dbType = '')
	{
		switch ($dbType)
		{
		case "MYSQL":
			return "ALTER TABLE ".$this->parent->name." ADD ".$this->name." ".$this->body;
		case "MSSQL":
			return "ALTER TABLE ".$this->parent->name." ADD ".$this->name." ".$this->body;
		case "ORACLE":
			return "ALTER TABLE ".$this->parent->name." ADD (".$this->name." ".$this->body.")";
		default:
			return "// ".get_class($this).":getCreateDdl for database type [".$dbType."] not implemented";
		}
	}

	/**
	 * Return DDL for column destruction.
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
			return "ALTER TABLE ".$this->parent->name." DROP ".$this->name;
		case "MSSQL":
			return "ALTER TABLE ".$this->parent->name." DROP COLUMN ".$this->name;
		case "ORACLE":
			return "ALTER TABLE ".$this->parent->name." DROP (".$this->name.")";
		default:
			return "// ".get_class($this).":getDropDdl for database type [".$dbType."] not implemented";
		}
	}

	/**
	 * Return DDL for object modification.
	 * <p>
	 * Implemented only for MySQL database. For Oracle or MS SQL returns commentary.
	 *
	 * @param Column $target Target object.
	 * @param string $dbType Database type (MYSQL, ORACLE or MSSQL).
	 *
	 * @return array|string
	 */
	public function getModifyDdl(Column $target, $dbType = '')
	{
		switch ($dbType)
		{
		case "MYSQL":
			return "ALTER TABLE ".$this->parent->name." CHANGE ".$this->name." ".$target->name." ".$target->body;
		case "MSSQL":
			if ($this->nullable !== $target->nullable)
			{
				$nullDdl = ($target->nullable? " NULL": " NOT NULL");
			}
			else
			{
				$nullDdl = "";
			}

			if (
				$this->type === $target->type
				&& $this->default === $target->default
				&& intval($this->length) < intval($target->length)
			)
			{
				return "ALTER TABLE ".$this->parent->name." ALTER COLUMN ".$this->name." ".$target->body.$nullDdl;
			}
			elseif (
				$this->type === $target->type
				&& $this->default === $target->default
				&& intval($this->length) === intval($target->length)
				&& $this->nullable !== $target->nullable
			)
			{
				return "ALTER TABLE ".$this->parent->name." ALTER COLUMN ".$this->name." ".$target->body;
			}
			else
			{
				return "// ".get_class($this).":getModifyDdl for database type [".$dbType."] not implemented. Change requested from [$this->body] to [$target->body].";
			}
		case "ORACLE":
			if (
				$this->type === $target->type
				&& $this->default === $target->default
				&& intval($this->length) < intval($target->length)
			)
			{
				return "ALTER TABLE ".$this->parent->name." MODIFY (".$this->name." ".$target->type."(".$target->length.")".")";
			}
			elseif (
				$this->type === $target->type
				&& $this->default === $target->default
				&& intval($this->length) === intval($target->length)
				&& $this->nullable !== $target->nullable
			)
			{
				return "
					declare
						l_nullable varchar2(1);
					begin
						select nullable into l_nullable
						from user_tab_columns
						where table_name = '".$this->parent->name."'
						and   column_name = '".$this->name."';
						if l_nullable = '".($target->nullable? "N": "Y")."' then
							execute immediate 'alter table ".$this->parent->name." modify (".$this->name." ".($target->nullable? "NULL": "NOT NULL").")';
						end if;
					end;
				";
			}
			else
			{
				return "// ".get_class($this).":getModifyDdl for database type [".$dbType."] not implemented. Change requested from [$this->body] to [$target->body].";
			}
		default:
			return "// ".get_class($this).":getModifyDdl for database type [".$dbType."] not implemented. Change requested from [$this->body] to [$target->body].";
		}
	}
}